<?php
include_once __DIR__ . '/../../config/db.php';

class Alumno
{
    private $conn;
    private $table = 'alumnos';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear un nuevo alumno
    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . " (nombre, apellido_paterno, apellido_materno, curp, telefono, municipio, codigo_postal, nivel_escolar) 
                  VALUES (:nombre, :apellido_paterno, :apellido_materno, :curp, :telefono, :municipio, :codigo_postal, :nivel_escolar)";
        $stmt = $this->conn->prepare($query);

        // Vincular parámetros
        $stmt->bindParam(":nombre", $data->nombre);
        $stmt->bindParam(":apellido_paterno", $data->apellido_paterno);
        $stmt->bindParam(":apellido_materno", $data->apellido_materno);
        $stmt->bindParam(":curp", $data->curp);
        $stmt->bindParam(":telefono", $data->telefono);
        $stmt->bindParam(":municipio", $data->municipio);
        $stmt->bindParam(":codigo_postal", $data->codigo_postal);
        $stmt->bindParam(":nivel_escolar", $data->nivel_escolar);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
