<?php
require_once __DIR__ . '/../models/Alumno.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CalificacionesController
{
    private $alumnoModel;

    public function __construct()
    {
        $this->alumnoModel = new Alumno();
    }

    private function obtenerIdAcceso()
    {
        $headers = apache_request_headers();

        if (!isset($headers['Authorization'])) {
            throw new Exception("No se proporcionó el token de autorización.");
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);

        // Validar y decodificar el token
        $payload = $this->validateJWT($token);
        error_log("Payload recibido en obtenerIdAcceso: " . json_encode($payload));

        $data = $payload['data'] ?? null;

        if (!$data || !isset($data['id_usuario'])) {
            throw new Exception("El token no contiene el campo 'id_usuario'. Payload: " . json_encode($payload));
        }

        $id_usuario = $data['id_usuario'];

        // Obtener el último id_acceso
        require_once __DIR__ . '/../models/Auditoria.php';
        $auditoriaModel = new Auditoria();
        return $auditoriaModel->obtenerUltimoIdAcceso($id_usuario);
    }

    function validateJWT($token)
    {
        try {
            // Decodificar el token usando la clave secreta
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            // Registrar el payload decodificado en el log
            error_log("Payload decodificado: " . json_encode($decoded));

            // Convertir el objeto a un arreglo asociativo
            return json_decode(json_encode($decoded), true);
        } catch (Exception $e) {
            // Manejar errores de decodificación
            error_log("Error al decodificar el token: " . $e->getMessage());
            throw new Exception("Token inválido: " . $e->getMessage());
        }
    }


    public function registrarCalificacion($data)
    {
        if (empty($data->id_escuelaAlumnoGradoMayores)) {
            echo json_encode(["success" => false, "message" => "Falta el ID del alumno."]);
            http_response_code(400);
            return;
        }

        if (empty($data->calificaciones) || !isset($data->calificaciones->espanol, $data->calificaciones->matematicas, $data->calificaciones->cienciasNaturales, $data->calificaciones->cienciasSociales)) {
            echo json_encode(["success" => false, "message" => "Faltan datos de calificaciones."]);
            http_response_code(400);
            return;
        }

        try {
            // Validar si puede capturar calificaciones
            $data->calificaciones = $this->ajustarCalificaciones($data->calificaciones);
            $id_acceso = $this->obtenerIdAcceso();
            $puedeCapturar = $this->alumnoModel->puedeCapturarCalificaciones($data->id_escuelaAlumnoGradoMayores);

            if (!$puedeCapturar) {
                echo json_encode(["success" => false, "message" => "No puedes capturar calificaciones hasta 3 meses después del registro."]);
                http_response_code(403);
                return;
            }

            // Preparar los datos para registrar calificaciones
            $datosCalificaciones = [
                'id_escuelaAlumnoGradoMayores' => $data->id_escuelaAlumnoGradoMayores,
                'espanol' => $data->calificaciones->espanol,
                'matematicas' => $data->calificaciones->matematicas,
                'cienciasNaturales' => $data->calificaciones->cienciasNaturales,
                'cienciasSociales' => $data->calificaciones->cienciasSociales,
                'id_acceso' => $id_acceso
            ];

            // Registrar las calificaciones
            $resultado = $this->alumnoModel->registrarCalificaciones($datosCalificaciones);

            if ($resultado) {
                echo json_encode(["success" => true, "message" => "Calificaciones registradas exitosamente."]);
                http_response_code(201);
            } else {
                echo json_encode(["success" => false, "message" => "Error al registrar las calificaciones."]);
                http_response_code(500);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            http_response_code(500);
        }
    }

    private function ajustarCalificaciones($calificaciones)
    {
        foreach ($calificaciones as $materia => $calificacion) {
            // Si la calificación es menor a 5, ajustarla a 5
            if ($calificacion < 5) {
                $calificaciones->$materia = 5;
            }
            // Si la calificación es mayor a 10, ajustarla a 10
            if ($calificacion > 10) {
                $calificaciones->$materia = 10;
            }
        }
        return $calificaciones;
    }
}
