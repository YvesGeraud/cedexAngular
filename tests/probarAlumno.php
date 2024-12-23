<?php
require_once __DIR__ . '/../app/models/Alumno.php';

// Prueba 1: Registrar un nuevo alumno (CURP no existente)
echo "Prueba 1: Registrar un nuevo alumno\n";
$data1 = [
    'mayores' => [
        'nombre' => 'Carlos',
        'app' => 'Hernández',
        'apm' => 'López',
        'curp' => 'CARL123456789ABC',
        'telefono' => '9876543210',
        'id_localidad' => 1,
        'cp' => '90000',
        'id_acceso' => 1
    ],
    'grado' => [
        'id_escuelaPlantel' => 2,
        'nivel' => 1,
        'grado' => 1,
        'id_escuelaAlumnoStatus' => 1,
        'id_acceso' => 1
    ]
];

$alumno = new Alumno();
$resultado1 = $alumno->registrarAlumno($data1);
if ($resultado1 === true) {
    echo "Alumno registrado correctamente en ambas tablas.\n";
} else {
    echo "Error: $resultado1\n";
}

// Prueba 2: Registrar a un alumno con CURP existente y estatus de baja (5)
echo "\nPrueba 2: Registrar un nuevo grado para CURP existente con baja\n";
$data2 = [
    'mayores' => [
        'nombre' => 'Carlos',
        'app' => 'Hernández',
        'apm' => 'López',
        'curp' => 'CURP_EXISTENTE_BAJA', // Asegúrate de usar un CURP que ya tenga estatus de baja
        'telefono' => '9876543210',
        'id_localidad' => 1,
        'cp' => '90000',
        'id_acceso' => 1
    ],
    'grado' => [
        'id_escuelaPlantel' => 3,
        'nivel' => 2,
        'grado' => 1,
        'id_escuelaAlumnoStatus' => 1,
        'id_acceso' => 1
    ]
];

$resultado2 = $alumno->registrarAlumno($data2);
if ($resultado2 === true) {
    echo "Nuevo grado registrado correctamente para el CURP existente.\n";
} else {
    echo "Error: $resultado2\n";
}

// Prueba 3: Registrar a un alumno con CURP existente y estatus activo
echo "\nPrueba 3: Intentar registrar un CURP existente con estatus activo\n";
$data3 = [
    'mayores' => [
        'nombre' => 'Carlos',
        'app' => 'Hernández',
        'apm' => 'López',
        'curp' => 'CURP_EXISTENTE_ACTIVO', // Asegúrate de usar un CURP que ya tenga estatus activo
        'telefono' => '9876543210',
        'id_localidad' => 1,
        'cp' => '90000',
        'id_acceso' => 1
    ],
    'grado' => [
        'id_escuelaPlantel' => 3,
        'nivel' => 2,
        'grado' => 1,
        'id_escuelaAlumnoStatus' => 1,
        'id_acceso' => 1
    ]
];

$resultado3 = $alumno->registrarAlumno($data3);
if ($resultado3 === true) {
    echo "Error: No debería permitir registrar un CURP con estatus activo.\n";
} else {
    echo "Correctamente bloqueado: $resultado3\n";
}
