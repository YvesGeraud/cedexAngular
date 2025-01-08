<?php

include_once __DIR__ . '/../models/Usuario.php';
include_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/AuditoriaController.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UsuarioController
{
    public function login($data)
    {
        // Validar que los datos requeridos estén presentes
        if (!empty($data->usern) && !empty($data->passw)) {
            $usuario = new Usuario();

            // Llamar al modelo para validar credenciales
            $result = $usuario->login($data->usern, $data->passw);

            if ($result['success']) {
                // Generar el token JWT
                $token = $this->generateJWT($result['id_usuario']);

                // Registrar auditoría del login exitoso
                $auditoria = new Auditoria();
                $auditoria->registrar(
                    $result['id_usuario'],
                    'Login',
                    'El usuario inició sesión exitosamente.'
                );

                // Responder con éxito y el token
                echo json_encode([
                    "success" => true,
                    "message" => "Login exitoso",
                    "token" => $token
                ]);
            } else {
                // Responder con el mensaje de error desde el modelo
                echo json_encode([
                    "success" => false,
                    "message" => $result['message']
                ]);
            }
        } else {
            // Responder con error si faltan datos
            echo json_encode([
                "success" => false,
                "message" => "Faltan datos obligatorios (usern o passw)."
            ]);
        }
    }

    private function generateJWT($id_usuario)
    {
        // Configurar los tiempos del token
        $issuedAt = time();
        $expirationTime = $issuedAt + $_ENV['JWT_EXPIRATION_TIME']; // Tiempo de expiración desde archivo .env
        $payload = [
            'iat' => $issuedAt, // Tiempo de emisión
            'exp' => $expirationTime, // Tiempo de expiración
            'iss' => $_ENV['JWT_ISSUER'], // Emisor
            'aud' => $_ENV['JWT_AUDIENCE'], // Público
            'data' => [
                'id_usuario' => $id_usuario // ID del usuario autenticado
            ]
        ];

        // Generar el token usando Firebase JWT
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // Log de depuración
        error_log("Token generado: " . $jwt);

        return $jwt;
    }

    function validateJWT($token)
    {
        try {
            // Decodificar el token con la clave secreta
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            // Convertir el objeto decodificado a un arreglo asociativo
            return json_decode(json_encode($decoded), true); // Asegúrate de que esto esté aquí
        } catch (Exception $e) {
            throw new Exception("Token inválido: " . $e->getMessage());
        }
    }
}
