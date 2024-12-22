<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/models/Auditoria.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Origin: *");

try {
    // Validar el token
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception("Token no proporcionado.");
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $id_usuario = validateJWT($token);

    // Obtener los registros de auditoría
    $auditoria = new Auditoria();
    $registros = $auditoria->obtenerAuditorias();

    echo json_encode(["success" => true, "data" => $registros]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
