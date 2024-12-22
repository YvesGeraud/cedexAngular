<?php
require_once __DIR__ . '/../config/auth.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

try {
    // Obtener el token del encabezado
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception("Token no proporcionado.");
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = validateJWT($token); // Valida el token actual

    // Generar un nuevo token basado en el anterior
    $issuedAt = time();
    $expirationTime = $issuedAt + $_ENV['JWT_EXPIRATION_TIME'];
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'iss' => $_ENV['JWT_ISSUER'],
        'aud' => $_ENV['JWT_AUDIENCE'],
        'data' => $decoded // Reutiliza los datos del token anterior
    ];

    $newToken = Firebase\JWT\JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

    echo json_encode(["success" => true, "message" => "Token renovado", "token" => $newToken]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit();
}
