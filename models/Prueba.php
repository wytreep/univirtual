<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Prueba
 * SCRUM-129 — Encapsula las queries de la tabla `pruebas`.
 * Refactor SCRUM-130: alineada a la convención POO del proyecto
 * (extends Model con $this->db, igual que Actividad, Entrega, etc.).
 *
 * Mantiene los métodos custom getLista/getResumen/insertar/limpiar
 * que ya usa PruebasController desde SCRUM-129.
 */
class Prueba extends Model {
    protected string $table      = 'pruebas';
    protected string $primaryKey = 'idPrueba';

    public int     $idPrueba;
    public string  $nombre_test;
    public string  $clase_test;
    public string  $estado;
    public float   $duracion_seg;
    public ?string $mensaje_error;
    public string  $ejecutado_en;

    /**
     * Últimas N pruebas ejecutadas (más recientes primero).
     */
    public function getLista(int $limite = 100): array {
        $stmt = $this->db->prepare(
            "SELECT idPrueba, nombre_test, clase_test,
                    estado, duracion_seg, mensaje_error, ejecutado_en
             FROM pruebas
             ORDER BY ejecutado_en DESC
             LIMIT ?"
        );
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    /**
     * Resumen agregado: total, passed, failed, error, duración total.
     */
    public function getResumen(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*)                                AS total,
                SUM(estado = 'PASSED')                  AS passed,
                SUM(estado = 'FAILED')                  AS failed,
                SUM(estado = 'ERROR')                   AS error,
                ROUND(SUM(duracion_seg), 3)             AS duracion_total,
                MAX(ejecutado_en)                       AS ultima_ejecucion
             FROM pruebas"
        );
        return $stmt->fetch();
    }

    /**
     * Inserta un nuevo resultado de prueba.
     * Retorna el idPrueba creado, o false si falló.
     */
    public function insertar(
        string  $nombre,
        string  $clase,
        string  $estado,
        float   $duracion,
        ?string $mensajeError
    ): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO pruebas
                (nombre_test, clase_test, estado, duracion_seg, mensaje_error)
             VALUES (?, ?, ?, ?, ?)"
        );
        $ok = $stmt->execute([$nombre, $clase, $estado, $duracion, $mensajeError]);
        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    /**
     * Limpia toda la tabla de pruebas (solo admin debería invocarlo).
     */
    public function limpiar(): void {
        $this->db->exec("DELETE FROM pruebas");
    }
}
