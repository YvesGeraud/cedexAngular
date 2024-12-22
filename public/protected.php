<?php
require_once __DIR__ . '/../config/auth.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Origin: *");

// Obtener el token del encabezado de la solicitud
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    echo json_encode(["success" => false, "message" => "Token no proporcionado"]);
    exit();
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$id_usuario = validateJWT($token);

echo json_encode(["success" => true, "message" => "Acceso autorizado", "id_usuario" => $id_usuario]);
