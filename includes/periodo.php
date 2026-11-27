<?php
/**
 * Periodo académico dinámico — UV-31 / CP-12
 *
 * El periodo ya no está fijo en «2026-I». Se calcula a partir de la fecha
 * actual (zona horaria America/Bogota) con el calendario institucional:
 *
 *   - Primer semestre:  enero  – junio   → etiqueta AAAA-I
 *   - Segundo semestre: julio  – diciembre → etiqueta AAAA-II
 *   - Inicio de clases: tercer lunes de enero / julio
 *   - Duración:         20 semanas (clases + evaluaciones)
 *
 * Para pruebas deterministas se puede fijar una fecha de referencia con la
 * variable de entorno UNIVIRTUAL_FECHA (por ejemplo 2027-02-15). En producción
 * no se define y se usa la fecha real del servidor.
 */

if (!function_exists('periodoAcademico')) {

    /**
     * Devuelve los datos del periodo académico vigente.
     *
     * @return array{
     *   etiqueta:string, anio:int, semestre:int, nombreSemestre:string,
     *   inicio:string, fin:string, semanaActual:int, totalSemanas:int,
     *   activo:bool
     * }
     */
    function periodoAcademico(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $tz  = new DateTimeZone(date_default_timezone_get() ?: 'America/Bogota');
        $ref = getenv('UNIVIRTUAL_FECHA');
        $hoy = $ref
            ? new DateTimeImmutable($ref, $tz)
            : new DateTimeImmutable('now', $tz);
        $hoy = $hoy->setTime(0, 0);

        $anio     = (int)$hoy->format('Y');
        $mes      = (int)$hoy->format('n');
        $semestre = $mes <= 6 ? 1 : 2;
        $etiqueta = $anio . '-' . ($semestre === 1 ? 'I' : 'II');

        // Inicio institucional: tercer lunes de enero (S1) o de julio (S2).
        $inicio = periodoNesimoLunes($anio, $semestre === 1 ? 1 : 7, 3, $tz);

        // Si la tabla configuración guarda un inicio coherente con el periodo
        // calculado, se respeta (permite ajustes manuales del calendario).
        if (function_exists('db')) {
            try {
                $stmt = db()->query(
                    "SELECT clave, valor FROM configuracion
                     WHERE clave IN ('semestre_activo', 'fecha_inicio_semestre')"
                );
                $cfg = [];
                foreach ($stmt->fetchAll() as $row) {
                    $cfg[$row['clave']] = $row['valor'];
                }

                $inicioCfg = !empty($cfg['fecha_inicio_semestre'])
                    ? DateTimeImmutable::createFromFormat('!Y-m-d', $cfg['fecha_inicio_semestre'], $tz)
                    : null;

                $mesEsperado = $semestre === 1 ? 1 : 7;
                if (
                    ($cfg['semestre_activo'] ?? '') === $etiqueta
                    && $inicioCfg instanceof DateTimeImmutable
                    && (int)$inicioCfg->format('n') === $mesEsperado
                    && (int)$inicioCfg->format('Y') === $anio
                ) {
                    $inicio = $inicioCfg->setTime(0, 0);
                }
            } catch (Throwable $e) {
                // La configuración es opcional: si falla, se usa el cálculo.
            }
        }

        $totalSemanas = 20;
        $fin          = $inicio->modify('+' . ($totalSemanas * 7 - 1) . ' days');
        $dias         = (int)$inicio->diff($hoy)->format('%r%a');
        $semanaActual = $dias < 0 ? 0 : min($totalSemanas, intdiv($dias, 7) + 1);

        $cache = [
            'etiqueta'       => $etiqueta,
            'anio'           => $anio,
            'semestre'       => $semestre,
            'nombreSemestre' => $semestre === 1 ? 'Primer semestre' : 'Segundo semestre',
            'inicio'         => $inicio->format('Y-m-d'),
            'fin'            => $fin->format('Y-m-d'),
            'semanaActual'   => $semanaActual,
            'totalSemanas'   => $totalSemanas,
            'activo'         => $dias >= 0 && $hoy <= $fin,
        ];

        return $cache;
    }

    /**
     * Calcula el n-ésimo lunes de un mes.
     */
    function periodoNesimoLunes(int $anio, int $mes, int $n, ?DateTimeZone $tz = null): DateTimeImmutable
    {
        $tz = $tz ?: new DateTimeZone(date_default_timezone_get() ?: 'America/Bogota');
        $dia = new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes), $tz);
        $dow = (int)$dia->format('N');            // 1 = lunes … 7 = domingo
        $offset = (1 - $dow + 7) % 7;             // días hasta el primer lunes

        return $dia->modify('+' . ($offset + ($n - 1) * 7) . ' days');
    }

    /** Etiqueta del periodo vigente, por ejemplo «2026-II». */
    function periodoEtiqueta(): string
    {
        return periodoAcademico()['etiqueta'];
    }

    /** Año del periodo vigente. */
    function periodoAnio(): int
    {
        return periodoAcademico()['anio'];
    }

    /** Semestre del periodo vigente (1 o 2). */
    function periodoSemestre(): int
    {
        return periodoAcademico()['semestre'];
    }

    /** Fecha de inicio del periodo vigente (Y-m-d). */
    function periodoInicio(): string
    {
        return periodoAcademico()['inicio'];
    }

    /** Fecha de finalización del periodo vigente (Y-m-d). */
    function periodoFin(): string
    {
        return periodoAcademico()['fin'];
    }

    /** Semana actual del periodo (0 antes de iniciar, tope en totalSemanas). */
    function periodoSemanaActual(): int
    {
        return periodoAcademico()['semanaActual'];
    }

    /** Total de semanas del periodo. */
    function periodoSemanasTotales(): int
    {
        return periodoAcademico()['totalSemanas'];
    }
}
