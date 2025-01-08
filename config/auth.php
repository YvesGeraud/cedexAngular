<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Cargar el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function validateJWT($token)
{
    try {
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

        // Verifica que el campo `data` y `id_usuario` existan en el payload
        if (!isset($decoded->data->id_usuario)) {
            throw new Exception("El token no contiene el campo 'id_usuario'. Payload: " . json_encode($decoded));
        }

        return $decoded->data->id_usuario; // Devuelve el id del usuario si es válido
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
        exit();
    }
}
