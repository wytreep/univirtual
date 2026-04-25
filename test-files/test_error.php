<?php
require 'config/config.php';
require 'models/Model.php';
$db = Model::getInstance()->getPDO();
$stmt = $db->prepare('SELECT e.idEntrega, e.nota, e.feedback, e.entregado_en, e.nombreOriginal, e.archivoEntrega, e.comentario, u.nombre AS estudiante, est.codigoEst, est.idEstudiante, act.titulo AS actividad, act.puntaje_max, act.idActividad FROM entregas e JOIN actividades act ON e.idActividad = act.idActividad JOIN aulas_virtuales av ON act.idAula = av.idAula JOIN estudiantes est ON e.idEstudiante = est.idEstudiante JOIN usuarios u ON est.idUsuario = u.idUsuario WHERE av.idMateria = 1 ORDER BY e.entregado_en DESC');
if (!$stmt) {
    print_r($db->errorInfo());
} else {
    echo "Query OK\n";
}
