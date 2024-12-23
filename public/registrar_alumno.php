<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/controllers/AlumnoController.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

try {
    // Validar el token JWT
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception("Token no proporcionado.");
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $id_usuario = validateJWT($token); // Valida el token y obtiene el ID del usuario

    // Obtener los datos enviados
    $data = json_decode(file_get_contents("php://input"));

    if (!$data) {
        echo json_encode(["success" => false, "message" => "Datos no proporcionados"]);
        http_response_code(400);
        exit();
    }

    // Instanciar el controlador y registrar al alumno
    $controller = new AlumnoController();
    $controller->registrarAlumno($data);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    http_response_code(401);
    exit();
}
