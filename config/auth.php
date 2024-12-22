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
        // Validar que JWT_SECRET esté definida
        if (!isset($_ENV['JWT_SECRET']) || empty($_ENV['JWT_SECRET'])) {
            throw new Exception("La variable JWT_SECRET no está definida en el archivo .env.");
        }

        // Decodificar el token
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        return $decoded->data->id_usuario; // Devuelve el id del usuario si es válido
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
        exit();
    }
}
