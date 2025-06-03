<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Configuración de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/asistencias_errors.log');
error_reporting(E_ALL);

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Eventos.php';
require_once __DIR__ . '/../models/Asistencias.php';
// At the top of your file, after the other requires
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $database = new Database();
    $db = $database->connect();
    $eventosModel = new Eventos($db);
    $asistenciasModel = new Asistencias($db);

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['evento_id'])) {
                $asistencias = $asistenciasModel->obtenerPorEvento($_GET['evento_id']);
                echo json_encode($asistencias);
            } else {
                throw new Exception("Se requiere el parámetro evento_id");
            }
            break;

        case 'POST':
            // Get input data - handle both form data and JSON
            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            
            if (strpos($contentType, 'application/json') !== false) {
                // Handle JSON input
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("JSON inválido");
                }
            } else {
                // Handle form data
                $data = $_POST;
            }
            
            // Check if data is empty
            if (empty($data)) {
                throw new Exception("No se recibieron datos");
            }
            
            // Log the received data for debugging
            error_log("Datos recibidos: " . print_r($data, true));
            
            // Validar campos
            $required = ['evento_id', 'nombre', 'email'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }

            // Validar formato email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El email no tiene un formato válido");
            }
            
            // Validar campos adicionales
            if (strlen($data['nombre']) < 5 || strlen($data['nombre']) > 100) {
                throw new Exception("El nombre debe ser completo");
            }
            
            // Registrar asistencia
            try {
                // Get event details first
                $evento = $eventosModel->obtenerPorId($data['evento_id']);
                if (!$evento) {
                    throw new Exception("El evento especificado no existe");
                }

                $registroInfo = $asistenciasModel->registrarAsistencia(
                    $data['evento_id'],
                    $data['nombre'],
                    $data['email']
                );

                if ($registroInfo) {
                    // Send confirmation email
                    $emailSent = enviarEmailConfirmacion(
                        $data['email'],
                        $data['nombre'],
                        $evento,  // Pass the evento variable here
                        $registroInfo['codigo'],
                        $registroInfo['qr_path']
                    );
                    
                    // Success response
                    echo json_encode([
                        'success' => true,
                        'message' => 'Registro exitoso. ' . ($emailSent ? 'Se ha enviado un email de confirmación.' : 'No se pudo enviar el email de confirmación.')
                    ]);
                } else {
                    // Get the last error from the model if available
                    $errorMsg = "Error al registrar la asistencia";
                    if (method_exists($asistenciasModel, 'getLastError')) {
                        $errorMsg .= ": " . $asistenciasModel->getLastError();
                    }
                    throw new Exception($errorMsg);
                }
            } catch (Exception $e) {
                error_log("Error en registrarAsistencia: " . $e->getMessage());
                throw $e;
            }


            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage(),
        'error_code' => 500
    ]);
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 400
    ]);
} finally {
    ob_end_flush();
}



/**
 * Send confirmation email with QR code
 */


function enviarEmailConfirmacion($email, $nombre, $evento, $codigo, $qrPath) {
    try {
        // Load email configuration
        $config = require __DIR__ . '/../config/email.php';
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = 0;                      // Disable debug output
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = ''; // Mailtrap SMTP server
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = '';      // Your Mailtrap username
        $mail->Password   = '';      // Your Mailtrap password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 2525;                  // Mailtrap port
        
        // Recipients
        $mail->setFrom('danielapatinocas707@gmail.com', 'Sistema de Eventos');
        $mail->addAddress($email, $nombre);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Confirmacion de asistencia: ' . $evento['nombre'];
        
        $fechaEvento = date('d/m/Y', strtotime($evento['fecha']));
        
        // Email body
        $mail->Body = "
            <h1>Gracias por registrarte!</h1>
            <p>Hola {$nombre},</p>
            <p>Tu registro para el evento <strong>{$evento['nombre']}</strong> ha sido confirmado.</p>
            <p><strong>Fecha:</strong> {$fechaEvento} a las {$evento['hora']}</p>
            <p><strong>Duracion:</strong> {$evento['duracion']} horas</p>
            <p><strong>Codigo de acceso:</strong> {$codigo}</p>
            <p><strong>Presenta este codigo QR al ingresar:</strong></p>
            <img src='cid:qr_cid' alt='Código QR'>
        ";
        
        // Add QR code as attachment
        $qrFullPath = $_SERVER['DOCUMENT_ROOT'] . $qrPath;
        if (file_exists($qrFullPath)) {
            $mail->addEmbeddedImage($qrFullPath, 'codigo_qr.png');
        }
        
        // Send email
        $mail->send();
        error_log("Email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}
