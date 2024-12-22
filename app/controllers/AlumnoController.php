<?php
include_once __DIR__ . '/../models/Alumno.php';

class AlumnoController
{
    public function crearAlumno($data)
    {
        if (empty($data->nombre) || empty($data->apellido_paterno) || empty($data->curp) || empty($data->municipio) || empty($data->nivel_escolar)) {
            echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
            http_response_code(400);
            exit();
        }

        $alumno = new Alumno();
        $result = $alumno->crear($data);

        if ($result) {
            echo json_encode(["success" => true, "message" => "Alumno creado exitosamente"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al crear el alumno"]);
            http_response_code(500);
        }
    }
}
