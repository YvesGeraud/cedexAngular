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
            echo json_encode(["success" => false, "message" => "Falta el ID del grado del alumno."]);
            http_response_code(400);
            return;
        }

        if (empty($data->calificaciones) || !isset($data->calificaciones->espanol, $data->calificaciones->matematicas, $data->calificaciones->cienciasNaturales, $data->calificaciones->cienciasSociales)) {
            echo json_encode(["success" => false, "message" => "Faltan los datos de calificaciones."]);
            http_response_code(400);
            return;
        }

        try {
            // Validar que el alumno y su grado existan
            $alumnoGrado = $this->alumnoModel->obtenerAlumnoGrado($data->id_escuelaAlumnoGradoMayores);
            if (!$alumnoGrado) {
                echo json_encode(["success" => false, "message" => "El alumno no está registrado en el grado indicado."]);
                http_response_code(404);
                return;
            }

            // Ajustar las calificaciones dentro del rango permitido
            $data->calificaciones = $this->ajustarCalificaciones($data->calificaciones);

            // Validar el token del usuario y obtener el último acceso
            $id_acceso = $this->obtenerIdAcceso();

            // Validar si el alumno puede capturar calificaciones
            $puedeCapturar = $this->alumnoModel->puedeCapturarCalificaciones($data->id_escuelaAlumnoGradoMayores);
            if (!$puedeCapturar) {
                echo json_encode(["success" => false, "message" => "No puedes capturar calificaciones hasta 3 meses después del registro."]);
                http_response_code(403);
                return;
            }

            // Preparar los datos para registrar las calificaciones
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

            if (!$resultado) {
                throw new Exception("Ocurrió un error al registrar las calificaciones.");
            }

            // Verificar si el alumno aprobó
            $calificaciones = [
                $data->calificaciones->espanol,
                $data->calificaciones->matematicas,
                $data->calificaciones->cienciasNaturales,
                $data->calificaciones->cienciasSociales
            ];
            $aprobo = $this->alumnoModel->verificarAprobacion($calificaciones);

            if ($aprobo) {
                // Usar la función promoverAlumno para registrar el siguiente grado
                $this->promoverAlumno($data->id_escuelaAlumnoGradoMayores, $alumnoGrado, $id_acceso);

                echo json_encode([
                    "success" => true,
                    "message" => "Calificaciones registradas exitosamente y el alumno fue promovido al siguiente grado."
                ]);
                http_response_code(201);
                return;
            } else {
                $this->registrarIntentoAdicional($data->id_escuelaAlumnoGradoMayores, $id_acceso);
                echo json_encode([
                    "success" => true,
                    "message" => "Añadido intento adicional."
                ]);
                http_response_code(201);
            }

            // Respuesta de éxito solo con registro de calificaciones
            echo json_encode([
                "success" => true,
                "message" => "Calificaciones registradas exitosamente."
            ]);
            http_response_code(201);
        } catch (Exception $e) {
            error_log("Error en registrarCalificacion: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            http_response_code(500);
        }
    }

    private function promoverAlumno($idGrado, $gradoActual, $idAcceso)
    {
        try {
            $promovido = $this->alumnoModel->registrarSiguienteGrado(
                $idGrado,
                $gradoActual['id_escuelaPlantel'],
                $gradoActual['nivel'],
                $gradoActual['grado'],
                $idAcceso
            );

            if ($promovido) {
                error_log("El alumno fue promovido al siguiente grado/nivel.");
            } else {
                error_log("Error: No se pudo promover al alumno.");
            }
        } catch (Exception $e) {
            error_log("Error en promoverAlumno: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => "Ocurrió un error al intentar promover al alumno: " . $e->getMessage()
            ]);
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

    private function registrarIntentoAdicional($idGrado, $idAcceso)
    {
        try {
            $incrementado = $this->alumnoModel->incrementarIntento(
                $idGrado,
                $idAcceso
            );

            if ($incrementado) {
                error_log("Se registró un intento adicional para el alumno en el grado $idGrado.");
            } else {
                error_log("Error: No se pudo registrar un intento adicional para el alumno en el grado $idGrado.");
            }
        } catch (Exception $e) {
            error_log("Error en registrarIntentoAdicional: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => "Ocurrió un error al intentar registrar un intento adicional: " . $e->getMessage()
            ]);
            http_response_code(500);
        }
    }
}
