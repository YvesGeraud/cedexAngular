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
        if (!empty($data->usern) && !empty($data->passw)) {
            $usuario = new Usuario();
            $result = $usuario->login($data->usern, $data->passw);

            if ($result['success']) {
                $token = $this->generateJWT($result['id_usuario']);

                $auditoria = new Auditoria();
                $auditoria->registrar($result['id_usuario'], 'Login', 'El usuario inicio sesion exitosamente.');

                echo json_encode(["success" => true, "message" => "Login exitoso", "token" => $token]);
            } else {
                echo json_encode(["success" => false, "message" => $result['message']]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Faltan datos"]);
        }
    }

    private function generateJWT($id_usuario)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $_ENV['JWT_EXPIRATION_TIME'];
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $_ENV['JWT_ISSUER'],
            'aud' => $_ENV['JWT_AUDIENCE'],
            'data' => [
                'id_usuario' => $id_usuario
            ]
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
        return $jwt;
    }
}
