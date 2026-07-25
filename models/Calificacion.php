<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Calificacion
 *
 * SCRUM-130 — Refactor POO + preparación para SCRUM-142.
 *
 * No es una tabla nueva. Es una FACHADA (façade pattern) sobre las dos
 * fuentes de notas que ya existen en el proyecto:
 *
 *   1) entregas.nota + entregas.feedback   → nota por entrega individual
 *   2) inscripciones.nota_parcial1/2/talleres/final → consolidado por materia
 *
 * Centraliza toda la lógica de calificación que HOY está duplicada en:
 *   - api/v1/profesor.php (calificarEntrega, guardarNota)
 *   - api/v1/estudiante.php (notas)
 *   - api/v1/reportes.php (reporteNotas)
 *   - models/Materia.php (actualizarNota)
 *   - models/Entrega.php (calificar)
 *
 * Beneficios:
 *   - DRY: una sola fuente de verdad para promedios y pesos
 *   - Testeable: lógica de negocio aislada del transporte HTTP
 *   - Preparado para SCRUM-142: pesos configurables, autocálculo, consolidado directivo
 *
 * Convención SOLID:
 *   S — responsabilidad única: lógica de calificación
 *   O — abierto a extensión (pesos custom) sin modificar lo existente
 *   L — sustituible por Model en cualquier contexto
 *   I — interfaz mínima necesaria
 *   D — depende de PDO vía db(), no de SQL crudo en controllers
 */
class Calificacion extends Model {
    // Esta clase opera sobre múltiples tablas — usa entregas como default
    // para los métodos heredados (find, save, delete).
    protected string $table      = 'entregas';
    protected string $primaryKey = 'idEntrega';

    // Campos permitidos para guardar en inscripciones (whitelist anti-SQL injection)
    public const CAMPOS_CONSOLIDADO = [
        'nota_parcial1',
        'nota_parcial2',
        'nota_talleres',
        'nota_final',
    ];

    // Pesos por defecto para el cálculo del promedio ponderado.
    // SCRUM-142 los volverá configurables vía tabla `configuracion`.
    public const PESOS_DEFAULT = [
        'nota_parcial1' => 0.30,
        'nota_parcial2' => 0.30,
        'nota_talleres' => 0.40,
    ];

    public const NOTA_APROBATORIA = 3.0;
    public const NOTA_MIN         = 0.0;
    public const NOTA_MAX         = 5.0;

    // ═══════════════════════════════════════════════════════════════
    //  CALIFICACIÓN POR ENTREGA (entregas.nota + entregas.feedback)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Califica una entrega individual.
     * Aplica validación de rango 0.0-5.0 (clamp).
     *
     * Reemplaza la lógica duplicada en:
     *   - profesor.php → calificarEntrega()
     *   - Entrega.php  → calificar()
     */
    public function calificarEntrega(int $idEntrega, float $nota, string $feedback = ''): bool {
        $nota = $this->clampNota($nota);
        $stmt = $this->db->prepare(
            "UPDATE entregas SET nota = ?, feedback = ? WHERE idEntrega = ?"
        );
        return $stmt->execute([$nota, $feedback, $idEntrega]);
    }

    /**
     * Recupera los datos necesarios para notificar al estudiante
     * después de calificar su entrega.
     *
     * @return array{idUsuario:int, titulo:string}|null
     */
    public function getDatosNotificacionEntrega(int $idEntrega): ?array {
        $stmt = $this->db->prepare(
            "SELECT u.idUsuario, act.titulo
             FROM entregas e
             JOIN actividades act ON e.idActividad = act.idActividad
             JOIN estudiantes est ON e.idEstudiante = est.idEstudiante
             JOIN usuarios u      ON est.idUsuario  = u.idUsuario
             WHERE e.idEntrega = ?"
        );
        $stmt->execute([$idEntrega]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CALIFICACIÓN CONSOLIDADA (inscripciones.nota_parcial1/2/talleres/final)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Guarda una nota consolidada en inscripciones.
     * Acepta idInscripcion directamente, o lo resuelve desde
     * (idEstudiante + idMateria) si el primero no viene.
     *
     * @param string $campo Uno de CAMPOS_CONSOLIDADO (whitelist)
     * @return bool true si guardó, false si datos inválidos
     */
    public function guardarConsolidado(
        ?int   $idInscripcion,
        int    $idEstudiante,
        int    $idMateria,
        string $campo,
        float  $nota
    ): bool {
        // Validación de whitelist — bloquea SQL injection en $campo
        if (!in_array($campo, self::CAMPOS_CONSOLIDADO, true)) {
            return false;
        }

        // Resolver idInscripcion si solo vino el par (estudiante, materia)
        if (!$idInscripcion && $idEstudiante && $idMateria) {
            $idInscripcion = $this->resolverIdInscripcion($idEstudiante, $idMateria);
        }
        if (!$idInscripcion) {
            return false;
        }

        $nota = $this->clampNota($nota);
        $stmt = $this->db->prepare(
            "UPDATE inscripciones SET $campo = ? WHERE idInscripcion = ?"
        );
        return $stmt->execute([$nota, $idInscripcion]);
    }

    /**
     * Resuelve idInscripcion a partir de idEstudiante + idMateria.
     * Retorna 0 si no encuentra inscripción.
     */
    public function resolverIdInscripcion(int $idEstudiante, int $idMateria): int {
        $stmt = $this->db->prepare(
            "SELECT idInscripcion FROM inscripciones
             WHERE idEstudiante = ? AND idMateria = ?"
        );
        $stmt->execute([$idEstudiante, $idMateria]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    /**
     * Datos para notificar al estudiante tras guardar una nota consolidada.
     *
     * @return array{idUsuario:int, materia:string}|null
     */
    public function getDatosNotificacionConsolidado(int $idInscripcion): ?array {
        $stmt = $this->db->prepare(
            "SELECT u.idUsuario, m.nombre AS materia
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u      ON est.idUsuario  = u.idUsuario
             JOIN materias m      ON i.idMateria    = m.idMateria
             WHERE i.idInscripcion = ?"
        );
        $stmt->execute([$idInscripcion]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CONSULTAS (vista estudiante, profesor, directivo)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Todas las notas de un estudiante por materia.
     * Reemplaza la query duplicada en estudiante.php → notas() y Estudiante.php → getNotas().
     */
    public function getNotasEstudiante(int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT m.idMateria, m.nombre, m.color, m.codigo, m.creditos,
                    i.nota_parcial1, i.nota_parcial2,
                    i.nota_talleres, i.nota_final
             FROM inscripciones i
             JOIN materias m ON i.idMateria = m.idMateria
             WHERE i.idEstudiante = ?
             ORDER BY m.nombre"
        );
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchAll();
    }

    /**
     * Notas consolidadas de todos los estudiantes de una materia.
     * Para el panel del profesor (tab 'estudiantes').
     */
    public function getConsolidadoMateria(int $idMateria): array {
        $stmt = $this->db->prepare(
            "SELECT u.nombre, est.codigoEst, est.idEstudiante,
                    i.idInscripcion,
                    i.nota_parcial1, i.nota_parcial2,
                    i.nota_talleres, i.nota_final
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u      ON est.idUsuario  = u.idUsuario
             WHERE i.idMateria = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$idMateria]);
        return $stmt->fetchAll();
    }

    /**
     * Entregas de un aula con info del estudiante y la actividad.
     * Para el panel "Calificar" del profesor.
     */
    public function getEntregasPorAula(int $idAula): array {
        $stmt = $this->db->prepare(
            "SELECT e.idEntrega, e.idActividad, e.nota, e.feedback,
                    e.entregado_en, e.urlArchivo, e.nombreOriginal, e.comentario,
                    u.nombre   AS estudiante, est.idEstudiante, est.codigoEst,
                    a.titulo   AS actividad, a.puntaje_max, a.fechaEntrega
             FROM entregas e
             JOIN actividades a   ON e.idActividad = a.idActividad
             JOIN estudiantes est ON e.idEstudiante = est.idEstudiante
             JOIN usuarios u      ON est.idUsuario  = u.idUsuario
             WHERE a.idAula = ?
             ORDER BY a.fechaEntrega DESC, u.nombre"
        );
        $stmt->execute([$idAula]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════
    //  CÁLCULOS (promedio simple y ponderado)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Promedio simple (sin pesos) de las notas presentes.
     * Ignora valores null/vacíos.
     *
     * @param array $notas Asociativo o lista con valores numéricos o null
     */
    public function promedioSimple(array $notas): ?float {
        $vals = array_filter($notas, fn($v) => $v !== null && $v !== '');
        if (empty($vals)) return null;
        return round(array_sum($vals) / count($vals), 2);
    }

    /**
     * Promedio ponderado según los pesos por campo.
     * Solo cuenta notas NO null. Renormaliza si faltan algunas
     * (ej: si solo hay parcial1, su peso se vuelve 100%).
     *
     * @param array $notasPorCampo  ['nota_parcial1' => 4.0, 'nota_talleres' => null, ...]
     * @param array $pesos          Por defecto PESOS_DEFAULT
     */
    public function promedioPonderado(array $notasPorCampo, ?array $pesos = null): ?float {
        $pesos = $pesos ?? self::PESOS_DEFAULT;

        $sumaPesos      = 0.0;
        $sumaPonderada  = 0.0;
        foreach ($pesos as $campo => $peso) {
            $valor = $notasPorCampo[$campo] ?? null;
            if ($valor === null || $valor === '') continue;
            $sumaPonderada += (float)$valor * $peso;
            $sumaPesos     += $peso;
        }
        if ($sumaPesos == 0.0) return null;
        return round($sumaPonderada / $sumaPesos, 2);
    }

    /**
     * Determina el estado académico según la nota final o el promedio.
     * Reemplaza la lógica duplicada en estudiante.php → notas().
     *
     * @return string 'aprobado' | 'reprobando' | 'en_curso'
     */
    public function estadoAcademico(?float $notaFinal, ?float $promedio): string {
        $nota = $notaFinal ?? $promedio;
        if ($nota === null) return 'en_curso';
        return $nota >= self::NOTA_APROBATORIA ? 'aprobado' : 'reprobando';
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Recorta una nota al rango [NOTA_MIN, NOTA_MAX].
     * Defensa en profundidad: aunque el cliente envíe 99 o -3, se guarda válido.
     */
    public function clampNota(float $nota): float {
        return max(self::NOTA_MIN, min(self::NOTA_MAX, $nota));
    }

    /**
     * Conteo de entregas sin calificar de un aula (nota IS NULL).
     */
    public function contarSinCalificar(int $idAula): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM entregas e
             JOIN actividades a ON e.idActividad = a.idActividad
             WHERE a.idAula = ? AND e.nota IS NULL"
        );
        $stmt->execute([$idAula]);
        return (int)$stmt->fetchColumn();
    }
}
