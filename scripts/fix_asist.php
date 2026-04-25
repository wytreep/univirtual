<?php
require_once 'c:/xampp/htdocs/univirtual/api/v1/db.php';
$db = db();
// Borrar duplicados manteniendo el registro más reciente
$db->query("DELETE a1 FROM asistencia a1 INNER JOIN asistencia a2 
            WHERE a1.idAsistencia < a2.idAsistencia 
            AND a1.idMateria = a2.idMateria 
            AND a1.idEstudiante = a2.idEstudiante 
            AND a1.fecha = a2.fecha");

// Añadir clave única
$db->query("ALTER TABLE asistencia ADD UNIQUE KEY unique_asist (idMateria, idEstudiante, fecha)");
echo "Done";
