<?php
include_once __DIR__ . '/../../config/db.php';

class Auditoria
{
    private $conn;
    private $table = 'auditoria';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function registrar($id_usuario, $accion, $detalle)
    {
        $query = "INSERT INTO " . $this->table . " (id_usuario, accion, detalle) VALUES (:id_usuario, :accion, :detalle)";
        $stmt = $this->conn->prepare($query);


        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":accion", $accion);
        $stmt->bindParam(":detalle", $detalle);
        $stmt->execute();
    }

    public function obtenerAuditorias()
    {
        $query = "SELECT * FROM " . $this->table . " ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerUltimoIdAcceso($id_usuario)
    {
        $query = "SELECT id 
                  FROM auditoria 
                  WHERE id_usuario = :id_usuario 
                  ORDER BY fecha DESC 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return $resultado['id']; // Retorna el último id_acceso
        } else {
            throw new Exception("No se encontró un registro de acceso para el usuario.");
        }
    }
}
