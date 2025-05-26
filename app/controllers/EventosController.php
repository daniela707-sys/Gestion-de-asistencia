<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Configuración de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/controller_errors.log');
error_reporting(E_ALL);

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir dependencias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Eventos.php';

try {
    $database = new Database();
    $db = $database->connect();
    $eventos = new eventos($db);

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $stmt = $eventos->obtenerTodos();
            $eventoss = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($eventoss);
            break;

        case 'POST':
            // Validar campos requeridos
            $required = ['nombre', 'descripcion', 'fecha', 'hora', 'duracion'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }

            // Manejo de imagen
            $imagen = '';
            // Dentro del case 'POST':
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Asistencia/public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetPath)) {
                    $imagen = $filename;
                    // Agrega este log para verificar
                    error_log("Imagen guardada en: " . $targetPath);
                    error_log("URL accesible: http://" . $_SERVER['HTTP_HOST'] . '/Asistencia/public/uploads/' . $filename);
                }
            }
            
            $result = $eventos->crear(
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['fecha'],
                $_POST['hora'],
                $_POST['duracion'],
                $imagen
            );
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'eventos creado' : 'Error al crear eventos'
            ]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido');
            }

            // Validar campos requeridos
            $required = ['id', 'nombre', 'descripcion', 'fecha', 'hora', 'duracion'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }

            $result = $eventos->actualizar(
                $data['id'],
                $data['nombre'],
                $data['descripcion'],
                $data['fecha'],
                $data['hora'],
                $data['duracion'],
                $data['imagen'] ?? null
            );
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'eventos actualizado' : 'Error al actualizar'
            ]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['id'])) {
                throw new Exception('ID del eventos es requerido');
            }

            $result = $eventos->eliminar($data['id']);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'eventos eliminado' : 'Error al eliminar'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    ob_end_flush();
}