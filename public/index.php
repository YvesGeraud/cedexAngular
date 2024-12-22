<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

include_once __DIR__ . '/../app/controllers/UsuarioController.php';

$data = json_decode(file_get_contents("php://input"));
$usuarioController = new UsuarioController();
$usuarioController->login($data);
