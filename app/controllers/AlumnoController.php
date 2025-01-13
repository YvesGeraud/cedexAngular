<?php
require_once __DIR__ . '/../models/Alumno.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AlumnoController
{
    private $alumnoModel;
    private $auditoriaModel;

    public function __construct()
    {
        $this->alumnoModel = new Alumno();
        $this->auditoriaModel = new Auditoria();
    }

    private function obtenerIdAcceso()
    {
        $headers = apache_request_headers();

        if (!isset($headers['Authorization'])) {
            throw new Exception("Token no proporcionado.");
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $id_usuario = $this->validateJWT($token);

        // Validar y decodificar el token
        $payload = $this->validateJWT($token);
        error_log("Payload recibido en obtenerIdAcceso: " . json_encode($payload));

        $data = $payload['data'] ?? null;

        if (!$id_usuario) {
            throw new Exception("No se pudo obtener el ID del usuario.");
        }
        return $id_usuario;
    }

    public function registrarAlumno($data)
    {
        try {

            $id_acceso = $this->obtenerIdAcceso();

            if (!$id_acceso) {
                throw new Exception("No jalo" . $id_acceso);
            }

            // Validar los datos enviados
            if (empty($data->mayores->nombre) || empty($data->mayores->curp) || empty($data->grado->nivel) || empty($data->grado->grado)) {
                throw new Exception("Faltan datos obligatorios.");
            }

            // Preparar los datos para el modelo
            $dataToInsert = [
                'id_usuario' => $id_acceso,
                'accion' => 'Registro de alumno',
                'detalle' => 'Intento de registro de alumno con CURP: ' . $data->mayores->curp,
                'mayores' => [
                    'nombre' => $data->mayores->nombre,
                    'app' => $data->mayores->app,
                    'apm' => $data->mayores->apm,
                    'curp' => $data->mayores->curp,
                    'telefono' => $data->mayores->telefono ?? null,
                    'id_localidad' => $data->mayores->id_localidad ?? 0,
                    'cp' => $data->mayores->cp ?? ''
                ],
                'grado' => [
                    'id_escuelaPlantel' => $data->grado->id_escuelaPlantel,
                    'nivel' => $data->grado->nivel,
                    'grado' => $data->grado->grado,
                    'id_escuelaAlumnoStatus' => $data->grado->id_escuelaAlumnoStatus ?? 1
                ]
            ];

            // Llamar al modelo para registrar el alumno con auditoría
            $resultado = $this->alumnoModel->registrarAlumnoConAuditoria($dataToInsert);

            if ($resultado === true) {
                echo json_encode(["success" => true, "message" => "Alumno registrado correctamente"]);
                http_response_code(201);
            } else {
                throw new Exception($resultado);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            http_response_code(500);
        }
    }


    private function validateJWT($token)
    {
        try {
            // Decodificar el token usando la clave secreta
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            // Registrar el payload decodificado en el log
            error_log("Payload decodificado en registrarAlumno: " . json_encode($decoded));

            // Devolver el ID del usuario
            return $decoded->data->id_usuario;
        } catch (Exception $e) {
            throw new Exception("Token inválido: " . $e->getMessage());
        }
    }

    public function obtenerAlumnos($data)
    {
        try {
            // Validar y filtrar parámetros
            $filtros = [];
            if (!empty($data->id_ciclo_escolar)) {
                $filtros['id_ciclo_escolar'] = $data->id_ciclo_escolar;
            }
            if (!empty($data->id_escuela)) {
                $filtros['id_escuela'] = $data->id_escuela;
            }
            if (!empty($data->estatus)) {
                $filtros['estatus'] = $data->estatus;
            }

            // Llamar al modelo para obtener alumnos
            $alumnos = $this->alumnoModel->obtenerAlumnos($filtros);

            // Respuesta JSON
            echo json_encode(["success" => true, "data" => $alumnos]);
            http_response_code(200);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            http_response_code(500);
        }
    }

    public function darDeBajaAlumno($data)
    {
        /*header('Content-Type: application/json');
        echo json_encode([
            "debug" => true,
            "data" => $data,
        ]);
        exit;*/
        try {

            $id_acceso = $this->obtenerIdAcceso();

            if (!$id_acceso) {
                throw new Exception("No jalo" . $id_acceso);
            }

            if (empty($data->id_escuelaAlumnoGrado)) {
                echo json_encode(["success" => false, "message" => "Falta el ID del grado del alumno."]);
                http_response_code(400);
                return false;
            }

            $resultado = $this->alumnoModel->darDeBajaAlumno($data->id_escuelaAlumnoGrado, $id_acceso);

            if (!$resultado) {
                throw new Exception("No se pudo dar de baja al alumno.");
            }
        } catch (Exception $e) {
            error_log("Error en darDeBajaAlumno:" . $e->getMessage());
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            http_response_code(500);
        }
    }
}
