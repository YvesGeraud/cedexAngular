<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/controllers/CalificacionesController.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

try {
    // Validar token JWT
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception("Token no proporcionado.");
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $id_usuario = validateJWT($token);

    // Obtener datos enviados
    $data = json_decode(file_get_contents("php://input"));

    if (!$data) {
        echo json_encode(["success" => false, "message" => "Datos no proporcionados."]);
        http_response_code(400);
        exit();
    }

    // Registrar calificación
    $controller = new CalificacionesController();
    $controller->registrarCalificacion($data);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    http_response_code(401);
    exit();
}
