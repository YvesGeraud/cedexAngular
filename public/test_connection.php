<?php
require_once __DIR__ . '/../config/db.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "Conexión exitosa.";
} else {
    echo "Error en la conexión.";
}
