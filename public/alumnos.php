<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/controllers/AlumnoController.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

try {

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception("Token no proporcionado.");
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $id_usuario = validateJWT($token); // Valida y obtiene el ID del usuario

    // Lógica para dar de alta a un alumno
    $data = json_decode(file_get_contents("php://input"));
    // Llamar al controlador
    $controller = new AlumnoController();
    $controller->obtenerAlumnos($data);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    http_response_code(401); // Error de autenticación
}
