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

use SendGrid\Mail\Mail;
use SendGrid\Mail\Attachment;


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
                        $evento,
                        $registroInfo['codigo'],
                        $registroInfo['qr_path']
                    );
                    
                    // Success response
                    echo json_encode([
                        'success' => true,
                        'message' => 'Registro exitoso. ' . ($emailSent ? 'Se ha enviado un email de confirmación a su spam.' : 'No se pudo enviar el email de confirmación.')
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
        $config = require_once __DIR__ . '/../config/email.php';
        
        // Create a new SendGrid email
        $mail = new \SendGrid\Mail\Mail();
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addTo($email, $nombre);
        $mail->setSubject('Confirmación de asistencia: ' . $evento['nombre']);
        
        $fechaEvento = date('d/m/Y', strtotime($evento['fecha']));
        $horaEvento = $evento['hora'];
        
        // Plain text version
        $plainText = "¡Gracias por registrarte!\n\n" .
                    "Hola {$nombre},\n\n" .
                    "Tu registro para el evento {$evento['nombre']} ha sido confirmado.\n" .
                    "Fecha: {$fechaEvento} a las {$horaEvento}\n" .
                    "Duración: {$evento['duracion']} horas\n" .
                    "Código de acceso: {$codigo}\n\n" .
                    "Presenta este código al ingresar al evento.\n\n" .
                    "Si tienes alguna pregunta, no dudes en contactarnos.\n" .
                    "¡Esperamos verte pronto!";
        
        $mail->addContent("text/plain", $plainText);
        
        // HTML version
        $htmlContent = "
            <h1>¡Gracias por registrarte!</h1>
            <p>Hola {$nombre},</p>
            <p>Tu registro para el evento <strong>{$evento['nombre']}</strong> ha sido confirmado.</p>
            <p><strong>Fecha:</strong> {$fechaEvento} a las {$horaEvento}</p>
            <p><strong>Duración:</strong> {$evento['duracion']} horas</p>
            <p><strong>Código de acceso:</strong> {$codigo}</p>
        ";
        
        // Add QR code as attachment if it exists
        $qrFullPath = $_SERVER['DOCUMENT_ROOT'] . $qrPath;
        if (file_exists($qrFullPath)) {
            $attachment = new \SendGrid\Mail\Attachment();
            $attachment->setContent(base64_encode(file_get_contents($qrFullPath)));
            $attachment->setType('image/png');
            $attachment->setFilename('codigo_qr.png');
            $attachment->setDisposition('attachment');
            $mail->addAttachment($attachment);
            
            $htmlContent .= "<p>Presenta el código QR adjunto al ingresar al evento.</p>";
        } else {
            $htmlContent .= "<p>No se pudo generar el código QR.</p>";
            error_log("QR file not found: $qrFullPath");
        }
        
        $htmlContent .= "
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            <p>¡Esperamos verte pronto!</p>
        ";
        
        $mail->addContent("text/html", $htmlContent);
        
        // Send the email
        $sendgrid = new \SendGrid($config['api_key']);
        $response = $sendgrid->send($mail);
        
        if ($response->statusCode() == 202) {
            error_log("Email sent successfully to $email");
            return true;
        } else {
            error_log("Failed to send email. Status code: " . $response->statusCode());
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
        return false;
    }

}

