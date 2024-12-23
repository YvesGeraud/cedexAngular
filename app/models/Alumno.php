<?php
include_once __DIR__ . '/../../config/db.php';

class Alumno
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function verificarCURP($curp)
    {
        $query = "SELECT id_escuelaAlumnoMayores, curp, id_acceso FROM escuelaAlumnoMayores WHERE curp = :curp";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":curp", $curp);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertarAlumnoMayores($data)
    {
        $query = "INSERT INTO escuelaAlumnoMayores (nombre, app, apm, curp, telefono, id_localidad, cp, fechareg, id_acceso)
                VALUES (:nombre, :app, :apm, :curp, :telefono, :id_localidad, :cp, NOW(), :id_acceso)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $data['nombre']);
        $stmt->bindParam(":app", $data['app']);
        $stmt->bindParam(":apm", $data['apm']);
        $stmt->bindParam(":curp", $data['curp']);
        $stmt->bindParam(":telefono", $data['telefono']);
        $stmt->bindParam(":id_localidad", $data['id_localidad']);
        $stmt->bindParam(":cp", $data['cp']);
        $stmt->bindParam(":id_acceso", $data['id_acceso']);

        return $stmt->execute() ? $this->conn->lastInsertId() : false;
    }

    public function insertarAlumnoGradoMayores($data)
    {
        $query = "INSERT INTO escuelaAlumnoGradoMayores (id_escuelaAlumnoMayores, id_escuelaPlantel, nivel, grado, intento, id_escuelaAlumnoStatus, fechaReg, id_acceso) 
                  VALUES (:id_escuelaAlumnoMayores, :id_escuelaPlantel, :nivel, :grado, 1, :id_escuelaAlumnoStatus, NOW(), :id_acceso)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_escuelaAlumnoMayores", $data['id_escuelaAlumnoMayores']);
        $stmt->bindParam(":id_escuelaPlantel", $data['id_escuelaPlantel']);
        $stmt->bindParam(":nivel", $data['nivel']);
        $stmt->bindParam(":grado", $data['grado']);
        $stmt->bindParam(":id_escuelaAlumnoStatus", $data['id_escuelaAlumnoStatus']);
        $stmt->bindParam(":id_acceso", $data['id_acceso']);

        return $stmt->execute();
    }

    public function puedeCapturarCalificaciones($id_escuelaAlumnoGradoMayores)
    {
        $query = "SELECT fechaReg FROM escuelaAlumnoGradoMayores WHERE id_escuelaAlumnoGradoMayores = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_escuelaAlumnoGradoMayores);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $fechaReg = $resultado['fechaReg']; // Fecha de registro
            $fechaLimite = date('Y-m-d', strtotime($fechaReg . ' +3 months')); // Fecha límite para capturar
            $hoy = date('Y-m-d'); // Fecha actual

            return $hoy >= $fechaLimite; // Retorna true si ya pasaron 3 meses
        } else {
            throw new Exception("No se encontró el registro del alumno.");
        }
    }

    public function obtenerUltimoIntento($id_escuelaAlumnoGradoMayores)
    {
        $query = "SELECT MAX(intento) as max_intento 
                  FROM escuelaCalificacionesMayores 
                  WHERE id_escuelaAlumnoGradoMayores = :id_escuelaAlumnoGradoMayores";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_escuelaAlumnoGradoMayores', $id_escuelaAlumnoGradoMayores);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? (int)$resultado['max_intento'] : 0;
    }

    public function registrarCalificaciones($data)
    {
        try {

            // Obtener el último intento registrado
            $ultimoIntento = $this->obtenerUltimoIntento($data['id_escuelaAlumnoGradoMayores']);
            $nuevoIntento = $ultimoIntento + 1;

            // Insertar la nueva calificación con el nuevo intento
            $query = "INSERT INTO escuelaCalificacionesMayores 
                      (id_escuelaAlumnoGradoMayores, intento, espanol, matematicas, cienciasNaturales, cienciasSociales, fechaReg, id_acceso) 
                      VALUES (:id_escuelaAlumnoGradoMayores, :intento, :espanol, :matematicas, :cienciasNaturales, :cienciasSociales, NOW(), :id_acceso)";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':id_escuelaAlumnoGradoMayores', $data['id_escuelaAlumnoGradoMayores']);
            $stmt->bindParam(':intento', $nuevoIntento);
            $stmt->bindParam(':espanol', $data['espanol']);
            $stmt->bindParam(':matematicas', $data['matematicas']);
            $stmt->bindParam(':cienciasNaturales', $data['cienciasNaturales']);
            $stmt->bindParam(':cienciasSociales', $data['cienciasSociales']);
            $stmt->bindParam(':id_acceso', $data['id_acceso']);

            if (!$stmt->execute()) {
                throw new Exception(implode(" | ", $stmt->errorInfo()));
            }

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function registrarAlumno($data)
    {
        try {
            $this->conn->beginTransaction();

            $alumnoExistente = $this->verificarCURP($data['mayores']['curp']);

            if ($alumnoExistente) {
                $estatusQuery = "SELECT id_escuelaAlumnoStatus FROM escuelaAlumnoGradoMayores 
                WHERE id_escuelaAlumnoMayores = :id_escuelaAlumnoMayores 
                ORDER BY fechaReg DESC LIMIT 1";
                $stmt = $this->conn->prepare($estatusQuery);
                $stmt->bindParam(":id_escuelaAlumnoMayores", $alumnoExistente['id_escuelaAlumnoMayores']);
                $stmt->execute();
                $estatus = $stmt->fetch(PDO::FETCH_ASSOC)['id_escuelaAlumnoStatus'];

                if ($estatus == 5) {
                    $data['grado']['id_escuelaAlumnoMayores'] = $alumnoExistente['id_escuelaAlumnoMayores'];
                    $resultadoGrado = $this->insertarAlumnoGradoMayores($data['grado']);
                    if (!$resultadoGrado) {
                        throw new Exception("Error al registrar en escuelaAlumnoGradoMayores");
                    }
                } else {
                    throw new Exception("El alumno ya está registrado en una escuela activa.");
                }
            } else {
                $id_escuelaAlumnoMayores = $this->insertarAlumnoMayores($data['mayores']);
                if (!$id_escuelaAlumnoMayores) {
                    throw new Exception("Error al registrar en escuelaAlumnoMayores");
                }

                $data['grado']['id_escuelaAlumnoMayores'] = $id_escuelaAlumnoMayores;
                $resultadoGrado = $this->insertarAlumnoGradoMayores($data['grado']);
                if (!$resultadoGrado) {
                    throw new Exception("Error al registrar en escuelaAlumnoGradoMayores");
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return $e->getMessage();
        }
    }
}
