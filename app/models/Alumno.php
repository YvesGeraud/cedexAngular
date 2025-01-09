<?php
include_once __DIR__ . '/../../config/db.php';

use Carbon\Carbon;
use Cmixin\BusinessDay;

class Alumno
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();

        Carbon::mixin(new BusinessDay());
        Carbon::setHolidaysRegion('mx');

        Carbon::addHolidays('mx', [
            '2024-04-01',
            '2024-04-15',
            '2024-07-15',
            '2024-08-01',
            '2024-12-24',
            '2024-12-31',
        ]);

        Carbon::setBusinessDayChecker(function (Carbon $date) {
            return !$date->isHoliday(); // Considerar hábil si no es festivo
        });
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
        $idCicloEscolar = $this->obtenerCicloEscolarActivo();
        if (!$idCicloEscolar) {
            throw new Exception("No se encontró un ciclo escolar activo.");
        }

        $fechaExamen = $this->calcularFechaExamen($data['fechaReg']);
        $anioExamen = Carbon::parse($fechaExamen)->year;
        $anioRegistro = Carbon::parse($data['fechaReg'])->year;

        if ($anioExamen > $anioRegistro) {
            $idCicloEscolar = $this->obtenerCicloEscolarPorAnio($anioExamen);
            if (!$idCicloEscolar) {
                throw new Exception("No se encontró un ciclo escolar para el año $anioExamen.");
            }
        }


        $query = "INSERT INTO escuelaAlumnoGradoMayores (id_escuelaAlumnoMayores, id_escuelaPlantel, nivel, grado, id_escuelaCicloEscolarMayores, intento, id_escuelaAlumnoStatus, fechaReg, fechaExa, id_acceso) 
                  VALUES (:id_escuelaAlumnoMayores, :id_escuelaPlantel, :nivel, :grado, :id_escuelaCicloEscolarMayores, 1, :id_escuelaAlumnoStatus, NOW(), :fechaExamen, :id_acceso)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_escuelaAlumnoMayores", $data['id_escuelaAlumnoMayores']);
        $stmt->bindParam(":id_escuelaPlantel", $data['id_escuelaPlantel']);
        $stmt->bindParam(":nivel", $data['nivel']);
        $stmt->bindParam(":grado", $data['grado']);
        $stmt->bindParam(":id_escuelaCicloEscolarMayores", $idCicloEscolar);
        $stmt->bindParam(":id_escuelaAlumnoStatus", $data['id_escuelaAlumnoStatus']);
        $stmt->bindParam(":fechaExamen", $fechaExamen);
        $stmt->bindParam(":id_acceso", $data['id_acceso']);

        return $stmt->execute();
    }

    public function puedeCapturarCalificaciones($id_escuelaAlumnoGradoMayores)
    {
        $query = "SELECT fechaReg, id_escuelaAlumnoGradoMayores FROM escuelaAlumnoGradoMayores WHERE id_escuelaAlumnoGradoMayores = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_escuelaAlumnoGradoMayores);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            throw new Exception("Registro no encontrado.");
        }
        $fechaRegistro = Carbon::parse($resultado['fechaReg']);
        if (!$fechaRegistro->isValid()) {
            throw new Exception("Fecha de registro no válida.");
        }

        $diasHabiles = $fechaRegistro->diffInBusinessDays(Carbon::now());

        return $diasHabiles >= 90;
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

    public function registrarAlumnoConAuditoria($data)
    {
        try {

            if (!$this->conn->inTransaction()) {
                $this->conn->beginTransaction();
            }
            // Verificar si la CURP ya existe antes de iniciar la transacción
            $alumnoExistente = $this->verificarCURP($data['mayores']['curp']);

            if ($alumnoExistente) {
                $estatusQuery = "SELECT id_escuelaAlumnoGradoMayores, id_escuelaAlumnoStatus, id_escuelaCicloEscolarMayores FROM escuelaAlumnoGradoMayores 
                WHERE id_escuelaAlumnoMayores = :id_escuelaAlumnoMayores 
                ORDER BY id_escuelaAlumnoGradoMayores DESC LIMIT 1";
                $stmt = $this->conn->prepare($estatusQuery);
                $stmt->bindParam(":id_escuelaAlumnoMayores", $alumnoExistente['id_escuelaAlumnoMayores']);
                $stmt->execute();
                $estatus = $stmt->fetch(PDO::FETCH_ASSOC);

                // Validar estatus y ciclo escolar
                if ($estatus['id_escuelaAlumnoStatus'] != 5) {
                    $idCicloEscolarActual = $this->obtenerCicloEscolarActivo();
                    if ($estatus['id_escuelaCicloEscolarMayores'] >= $idCicloEscolarActual) {
                        throw new Exception("El alumno ya está registrado en una escuela activa y en un ciclo escolar igual o superior.");
                    }
                }
            } else {
                // Registrar la auditoría
                $id_acceso = $this->registrarAuditoria(
                    $data['id_usuario'],
                    'Registro de alumno',
                    'Registro de alumno con CURP: ' . $data['mayores']['curp']
                );
            }

            // Continuar con el registro del alumno
            if (!$alumnoExistente) {
                // Insertar en escuelaAlumnoMayores si no existe
                $id_escuelaAlumnoMayores = $this->insertarAlumnoMayores(array_merge($data['mayores'], ['id_acceso' => $id_acceso]));
                if (!$id_escuelaAlumnoMayores) {
                    throw new Exception("Error al registrar en escuelaAlumnoMayores.");
                }
            } else {
                $id_escuelaAlumnoMayores = $alumnoExistente['id_escuelaAlumnoMayores'];
            }

            $id_acceso = $this->registrarAuditoria(
                $data['id_usuario'],
                'Registro de alumno Grado',
                'Registro de alumnoGrado con CURP: ' . $data['mayores']['curp'] . ', Nivel: ' . $data['grado']['nivel'] . ', Grado: ' . $data['grado']['grado']
            );

            $data['grado']['id_escuelaAlumnoMayores'] = $id_escuelaAlumnoMayores;
            $data['grado']['id_acceso'] = $id_acceso; // Asignar el id_acceso generado


            $resultadoGrado = $this->insertarAlumnoGradoMayores($data['grado']);
            if (!$resultadoGrado) {
                throw new Exception("Error al registrar en escuelaAlumnoGradoMayores.");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return $e->getMessage();
        }
    }

    public function verificarAprobacion($calificaciones)
    {
        // Calcula el promedio de las calificaciones
        $promedio = array_sum($calificaciones) / count($calificaciones);

        // Criterio de aprobación
        $promedioMinimo = 6; // Cambia esto según tus reglas
        return $promedio >= $promedioMinimo;
    }

    public function registrarSiguienteGrado($idEscuelaAlumnoGrado, $nivelActual, $gradoActual, $idAcceso)
    {
        $queryValidacion = "SELECT id_escuelaAlumnoGradoMayores 
        FROM escuelaAlumnoGradoMayores 
        WHERE id_escuelaAlumnoGradoMayores = :id";
        $stmtValidacion = $this->conn->prepare($queryValidacion);
        $stmtValidacion->bindParam(':id', $idEscuelaAlumnoGrado);
        $stmtValidacion->execute();

        if ($stmtValidacion->rowCount() === 0) {
            throw new Exception("El registro con ID proporcionado no existe.");
        }

        // Validar nivel y grado actuales
        if (empty($nivelActual) || empty($gradoActual)) {
            throw new Exception("Nivel o grado actual no proporcionado.");
        }

        $idCicloEscolar = $this->obtenerCicloEscolarActivo();
        if (!$idCicloEscolar) {
            throw new Exception("No se encontró un ciclo escolar activo.");
        }

        // Inicializar nuevo nivel, grado y estatus
        $nuevoNivel = $nivelActual;
        $nuevoGrado = $gradoActual;
        $nuevoEstatus = 1; // Por defecto: Acreditado

        // Determinar el siguiente grado y nivel basado en las reglas
        if ($nivelActual === 1 && $gradoActual < 2) {
            $nuevoGrado = $gradoActual + 1; // Primaria: Incrementar grado
        } elseif ($nivelActual === 1 && $gradoActual === 2) {
            $nuevoNivel = 2;
            $nuevoGrado = 3; // Fin de Primaria: Pasar a Secundaria
        } elseif ($nivelActual === 2 && $gradoActual < 5) {
            $nuevoGrado = $gradoActual + 1; // Secundaria: Incrementar grado
        } elseif ($nivelActual === 2 && $gradoActual === 5) {
            $nuevoEstatus = 3; // Fin de Secundaria: Certificado
        }

        $fechaExamen = Carbon::now()->addBusinessDay(90)->toDateString();

        // Validar datos antes de ejecutar la consulta
        if (empty($nuevoNivel) || empty($nuevoGrado) || empty($nuevoEstatus)) {
            throw new Exception("Datos insuficientes para registrar el siguiente grado.");
        }

        // Insertar el nuevo registro
        $query = "INSERT INTO escuelaAlumnoGradoMayores (id_escuelaAlumnoMayores, nivel, grado, id_escuelaCicloEscolarMayores, id_acceso, id_escuelaStatusAlumnoGrado, fechaReg, fechaExa)
              SELECT id_escuelaAlumnoMayores, :nivel, :grado, :idCicloEscolar, :idAcceso, :estatus NOW(), :fechaExamen
              FROM escuelaAlumnoGradoMayores
              WHERE id = :idEscuelaAlumnoGrado";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nivel', $nuevoNivel);
        $stmt->bindParam(':grado', $nuevoGrado);
        $stmt->bindParam(':idCicloEscolar', $idCicloEscolar);
        $stmt->bindParam(':idAcceso', $idAcceso);
        $stmt->bindParam(':estatus', $nuevoEstatus);
        $stmt->bindParam(':fechaExamen', $fechaExamen);
        $stmt->bindParam(':idEscuelaAlumnoGrado', $idEscuelaAlumnoGrado);


        // Ejecutar y validar la inserción
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Error al registrar el siguiente grado: " . json_encode($stmt->errorInfo()));
            return false;
        }
    }

    public function obtenerCicloEscolarActivo()
    {
        $query = "SELECT id_escuelaCicloEscolarMayores FROM escuelaCicloEscolarMayores WHERE activo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id_escuelaCicloEscolarMayores'] ?? null;
    }

    public function calcularFechaExamen($fechaRegistro)
    {
        $fechaRegistro = Carbon::parse($fechaRegistro);
        if (!$fechaRegistro->isValid()) {
            throw new Exception("Fecha de registro no válida.");
        }

        // Sumar los días hábiles
        $fechaExamen = $fechaRegistro->addBusinessDays(90);

        // Si cambia de año, verifica que el nuevo ciclo escolar esté correcto
        $anioRegistro = $fechaRegistro->year;
        $anioExamen = $fechaExamen->year;

        if ($anioExamen > $anioRegistro) {
            $nuevoCicloEscolar = $this->obtenerCicloEscolarPorAnio($anioExamen);
            if (!$nuevoCicloEscolar) {
                throw new Exception("No se encontró un ciclo escolar para el año $anioExamen.");
            }
        }

        return $fechaExamen->toDateString();
    }
    public function obtenerCicloEscolarPorAnio($anio)
    {
        $query = "SELECT id_escuelaCicloEscolarMayores FROM escuelaCicloEscolarMayores WHERE anio = :anio LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':anio', $anio);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id_escuelaCicloEscolarMayores'] ?? null;
    }

    public function registrarAuditoria($id_usuario, $accion, $detalle)
    {
        $query = "INSERT INTO auditoria (id_usuario, accion, detalle, fecha) VALUES (:id_usuario, :accion, :detalle, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":accion", $accion);
        $stmt->bindParam(":detalle", $detalle);

        if (!$stmt->execute()) {
            throw new Exception("Error al registrar auditoría.");
        }

        return $this->conn->lastInsertId();
    }
}
