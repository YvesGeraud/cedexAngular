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

        error_log("Consulta ejecutada para usuario: " . $usern);

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Resultado de la consulta: " . json_encode($row));

            if (password_verify($passw, $row['passw'])) {
                error_log("Contraseña verificada correctamente para usuario: " . $usern);

                if ($row['vigente'] === 'S') {
                    error_log("Usuario activo: " . $row['id_usuario']);
                    return ['success' => true, 'id_usuario' => $row['id_usuario']];
                } else {
                    error_log("Usuario no activo: " . $usern);
                    return ['success' => false, 'message' => 'Usuario no activo'];
                }
            } else {
                error_log("Contraseña incorrecta para usuario: " . $usern);
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
        } else {
            error_log("Usuario no encontrado: " . $usern);
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
    }
}
