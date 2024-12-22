<?php

include_once __DIR__ . '/../../config/db.php';

class Usuario
{
    private $conn;
    private $table = 'usuario';

    public $id_usuario;
    public $usern;
    public $passw;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($usern, $passw)
    {
        $query = "SELECT id_usuario, passw, vigente FROM " . $this->table . " WHERE usern = :usern LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usern", $usern);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($passw, $row['passw'])) {
                if ($row['vigente'] === 'S') {
                    return ['success' => true, 'id_usuario' => $row['id_usuario']];
                } else {
                    return ['seccess' => false, 'message' => 'Usuario no activo'];
                }
            } else {
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
        } else {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
    }
}
