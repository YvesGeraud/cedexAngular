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
}
