<?php
require_once __DIR__ . '/../models/Alumno.php';

class CalificacionesController
{
    private $alumnoModel;

    public function __construct()
    {
        $this->alumnoModel = new Alumno();
    }

    public function registrarCalificacion($data)
    {
        if (empty($data->id_escuelaAlumnoGradoMayores)) {
            echo json_encode(["success" => false, "message" => "Falta el ID del alumno."]);
            http_response_code(400);
            return;
        }

        try {
            // Validar si puede capturar calificaciones
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
                'id_acceso' => $data->id_acceso
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
}
