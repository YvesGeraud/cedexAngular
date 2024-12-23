<?php
require_once __DIR__ . '/../models/Alumno.php';

class AlumnoController
{
    private $alumnoModel;

    public function __construct()
    {
        $this->alumnoModel = new Alumno();
    }

    // Registrar un nuevo alumno
    public function registrarAlumno($data)
    {
        // Validar datos requeridos
        if (empty($data->mayores->nombre) || empty($data->mayores->curp) || empty($data->grado->nivel) || empty($data->grado->grado)) {
            echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
            http_response_code(400);
            return;
        }

        // Preparar datos para el modelo
        $dataToInsert = [
            'mayores' => [
                'nombre' => $data->mayores->nombre,
                'app' => $data->mayores->app,
                'apm' => $data->mayores->apm,
                'curp' => $data->mayores->curp,
                'telefono' => $data->mayores->telefono ?? null,
                'id_localidad' => $data->mayores->id_localidad ?? 0,
                'cp' => $data->mayores->cp ?? '',
                'id_acceso' => $data->mayores->id_acceso ?? 1
            ],
            'grado' => [
                'id_escuelaPlantel' => $data->grado->id_escuelaPlantel,
                'nivel' => $data->grado->nivel,
                'grado' => $data->grado->grado,
                'id_escuelaAlumnoStatus' => $data->grado->id_escuelaAlumnoStatus ?? 1,
                'id_acceso' => $data->grado->id_acceso ?? 1
            ]
        ];

        // Llamar al modelo
        $resultado = $this->alumnoModel->registrarAlumno($dataToInsert);

        if ($resultado === true) {
            echo json_encode(["success" => true, "message" => "Alumno registrado correctamente"]);
            http_response_code(201);
        } else {
            echo json_encode(["success" => false, "message" => $resultado]);
            http_response_code(500);
        }
    }
}
