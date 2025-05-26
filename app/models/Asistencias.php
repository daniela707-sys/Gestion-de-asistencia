<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
class Asistencias {
    private $conn;
    private $table = "asistencias";

    public function __construct($db) {
        $this->conn = $db;
    }

    private $lastError = '';

    public function getLastError() {
        return $this->lastError;
    }

    public function registrarAsistencia($evento_id, $nombre, $email) {
        try {
            // Generate a unique code for attendance
            $codigo = substr(md5(uniqid()), 0, 10);
            
            // Generate QR code
            try {
                $qrPath = $this->generarQR($email, $codigo);
            } catch (Exception $e) {
                $this->lastError = "Error al generar QR: " . $e->getMessage();
                error_log($this->lastError);
                return false;
            }
            
            // Check if this email is already registered for this event
            $query = "SELECT COUNT(*) FROM " . $this->table . " 
                    WHERE evento_id = :evento_id AND email_participante = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':evento_id' => $evento_id,
                ':email' => $email
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                $this->lastError = "Este email ya está registrado para este evento";
                error_log($this->lastError);
                return false;
            }
            
            $query = "INSERT INTO " . $this->table . " 
                    (evento_id, nombre_participante, email_participante, codigo_asistencia, qr_path) 
                    VALUES (:evento_id, :nombre, :email, :codigo, :qr_path)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':evento_id' => $evento_id,
                ':nombre' => $nombre,
                ':email' => $email,
                ':codigo' => $codigo,
                ':qr_path' => $qrPath
            ]);

            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = "Error SQL: " . implode(", ", $errorInfo);
                error_log($this->lastError);
            }
            
            return [
                'codigo' => $codigo,
                'qr_path' => $qrPath
            ];
        } catch (PDOException $e) {
            $this->lastError = "Error de base de datos: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        } catch (Exception $e) {
            $this->lastError = "Error general: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }


    private function generarQR($email, $codigo) {
        try {
            $qrData = "EVENTO|{$email}|{$codigo}";
            $filename = 'qr_' . md5($qrData) . '.png';
            $path = '/Asistencia/public/assets/qrcodes/' . $filename;
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            
            error_log("Generando QR para: $qrData");
            error_log("Path completo: $fullPath");
            
            // Crear directorio si no existe
            $dirPath = dirname($fullPath);
            if (!file_exists($dirPath)) {
                if (!mkdir($dirPath, 0755, true)) {
                    throw new Exception("No se pudo crear el directorio para QR: $dirPath");
                }
            }
            
            // Verificar permisos de escritura
            if (!is_writable($dirPath)) {
                error_log("El directorio no tiene permisos de escritura: $dirPath");
                throw new Exception("El directorio no tiene permisos de escritura");
            }
            
            // Configuración moderna del QR Code
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrData)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->build();
            
            // Guardar el QR
            $result->saveToFile($fullPath);
            error_log("QR guardado exitosamente en: $fullPath");
            
            return $path;
        } catch (Exception $e) {
            error_log("Error en generarQR: " . $e->getMessage());
            throw $e;
        }
    }

    private function enviarEmailConfirmacion($email, $nombre, $evento_id, $codigo, $qrPath) {
        try {
            // Obtener detalles del evento
            $evento = $this->obtenerPorEvento($evento_id);
            if (!$evento) {
                throw new Exception("No se pudo obtener la información del evento");
            }

            // Crear una nueva instancia de PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.tudominio.com';  // Servidor SMTP
            $mail->SMTPAuth = true;
            $mail->Username = 'tucorreo@tudominio.com'; // Tu email
            $mail->Password = 'tucontraseña'; // Tu contraseña
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587; // Puerto SMTP

            // Remitente
            $mail->setFrom('no-reply@tudominio.com', 'Sistema de Asistencias');
            $mail->addAddress($email, $nombre); // Destinatario

            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Confirmación de asistencia: ' . $evento['nombre'];
            
            // Cuerpo del mensaje
            $mail->Body = "
                <h1>¡Gracias por registrarte, {$nombre}!</h1>
                <p>Tu registro para el evento <strong>{$evento['nombre']}</strong> ha sido confirmado.</p>
                <p><strong>Fecha:</strong> {$evento['fecha']} a las {$evento['hora']}</p>
                <p><strong>Lugar:</strong> {$evento['lugar']}</p>
                <p><strong>Código de acceso:</strong> {$codigo}</p>
                <p>Presenta este código QR al ingresar al evento:</p>
                <img src='cid:qr_code' alt='Código QR' width='200'>
                <p>¡Te esperamos!</p>
            ";
            
            // Adjuntar imagen QR
            $mail->addStringEmbeddedImage(
                file_get_contents($_SERVER['DOCUMENT_ROOT'] . $qrPath),
                'qr_code',
                'qr.png',
                'base64',
                'image/png'
            );

            $mail->send();
            error_log("Email enviado exitosamente a: {$email}");
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar email: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function obtenerPorEvento($evento_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE evento_id = :evento_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":evento_id" => $evento_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>