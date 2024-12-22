<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/controllers/AlumnoController.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

try {
    // Validar el token
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception("Token no proporcionado.");
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $id_usuario = validateJWT($token); // Valida y obtiene el ID del usuario

    // Lógica para dar de alta a un alumno
    $data = json_decode(file_get_contents("php://input"));
    $controller = new AlumnoController();
    $controller->crearAlumno($data);

    echo json_encode(["success" => true, "message" => "Alumno creado exitosamente."]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
