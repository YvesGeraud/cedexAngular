<?php
require_once __DIR__ . '/../app/controllers/AlumnoController.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

try {
    // Obtener los datos enviados
    $data = json_decode(file_get_contents("php://input"));

    if (!$data) {
        echo json_encode(["success" => false, "message" => "Datos no proporcionados"]);
        http_response_code(400);
        exit();
    }

    // Instanciar el controlador y delegar la lógica al controlador
    $controller = new AlumnoController();
    $controller->registrarAlumno($data);
} catch (Exception $e) {
    // Manejo de errores
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    http_response_code(401);
    exit();
}
