<?
include_once __DIR__ . '/../models/Auditoria.php';

class AuditoriaController
{
    private $auditoria;
    public function __construct()
    {
        $this->auditoria = new Auditoria();
    }

    public function registrar($id_usuario, $accion, $detalle = null)
    {
        if ($this->auditoria->registrar($id_usuario, $accion, $detalle)) {
            return true;
        } else {
            return false;
        }
    }
}
