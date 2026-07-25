<?php
/**
 * XlsxWriter — Generador XLSX nativo para UNI-VIRTUAL
 * Sin dependencias externas. Usa ZipArchive + OOXML (ISO 29500)
 * Estructura validada contra la especificación Open XML.
 *
 * Uso:
 *   $w = new XlsxWriter();
 *   $w->writeSheetHeader('Hoja', ['Col1'=>'string','Col2'=>'price']);
 *   $w->writeSheetRow('Hoja', ['valor', 4.5]);
 *   $w->writeToStdOut();  // o writeToFile('ruta.xlsx')
 */
class XlsxWriter {

    // Índices de estilos (deben coincidir con cellXfs en buildStyles())
    const STYLE_DEFAULT   = 0;
    const STYLE_TITLE     = 1;  // navy bg, white bold 14, centrado
    const STYLE_SUBTITLE  = 2;  // blue bg, white bold 12, centrado
    const STYLE_HEADER    = 3;  // navy bg, white bold 10, centrado
    const STYLE_CELL_L    = 4;  // blanco, negro, alineado izquierda
    const STYLE_CELL_C    = 5;  // blanco, negro, centrado
    const STYLE_CELL_ALT  = 6;  // azul claro, negro, centrado
    const STYLE_NUM       = 7;  // blanco, negro, centrado, formato 0.00
    const STYLE_NUM_ALT   = 8;  // azul claro, negro, centrado, formato 0.00
    const STYLE_OK        = 9;  // verde claro bg, verde oscuro bold
    const STYLE_MAL       = 10; // rojo claro bg, rojo oscuro bold
    const STYLE_WARN      = 11; // dorado claro bg, dorado oscuro

    private array $sheets      = [];
    private array $sheetData   = [];
    private array $strings     = [];
    private array $strIndex    = [];

    public function writeSheetHeader(string $name, array $cols): void {
        if (!isset($this->sheetData[$name])) {
            $this->sheets[]         = $name;
            $this->sheetData[$name] = ['cols' => $cols, 'rows' => []];
        }
    }

    public function writeSheetRow(string $name, array $row): void {
        $this->sheetData[$name]['rows'][] = $row;
    }

    public function writeToStdOut(): void {
        echo $this->generate();
    }

    public function writeToFile(string $path): void {
        file_put_contents($path, $this->generate());
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function generate(): string {
        // Reset strings para cada generación
        $this->strings  = [];
        $this->strIndex = [];

        // Pre-construir hojas para acumular strings primero
        $sheetXmls = [];
        foreach ($this->sheets as $name) {
            $sheetXmls[$name] = $this->buildSheet($name);
        }

        // En XAMPP Windows, OVERWRITE puede fallar. Usamos CREATE con archivo temporal nuevo.
        $tmp = tempnam(sys_get_temp_dir(), 'uvxlsx_');
        unlink($tmp); // ZipArchive crea el archivo limpio
        $tmp .= '.xlsx';

        $zip = new ZipArchive();
        $result = $zip->open($tmp, ZipArchive::CREATE);
        if ($result !== true) {
            throw new RuntimeException("ZipArchive::open falló con código: {$result}");
        }

        $zip->addFromString('[Content_Types].xml',        $this->buildContentTypes());
        $zip->addFromString('_rels/.rels',                $this->buildRels());
        $zip->addFromString('xl/workbook.xml',            $this->buildWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRels());
        $zip->addFromString('xl/styles.xml',              $this->buildStyles());
        $zip->addFromString('xl/sharedStrings.xml',       $this->buildSharedStrings());

        foreach ($this->sheets as $i => $name) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($i + 1) . '.xml',
                $sheetXmls[$name]
            );
        }

        $zip->close();
        $data = file_get_contents($tmp);
        unlink($tmp);
        return $data;
    }

    // ── Construir hoja ────────────────────────────────────────────────────────
    private function buildSheet(string $name): string {
        $sheet    = $this->sheetData[$name];
        $headers  = array_keys($sheet['cols']);
        $types    = array_values($sheet['cols']);
        $colCount = count($headers);
        $lastCol  = $this->colLetter($colCount);
        $isFirst  = ($name === $this->sheets[0]);

        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/sheet/2006/main">';

        // Freeze panes — Excel requiere <selection> cuando se define <pane>
        $xml .= '<sheetViews><sheetView workbookViewId="0">';
        $xml .= '<pane ySplit="3" topLeftCell="A4" state="frozen"/>';
        $xml .= '<selection pane="bottomLeft" activeCell="A4" sqref="A4"/>';
        $xml .= '</sheetView></sheetViews>';

        // Anchos de columna
        $widths = [28, 14, 6, 10, 10, 10, 10, 11, 12, 11, 13];
        $xml .= '<cols>';
        for ($i = 0; $i < $colCount; $i++) {
            $w = $widths[$i] ?? 12;
            $xml .= '<col min="' . ($i+1) . '" max="' . ($i+1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols>';

        $xml .= '<sheetData>';

        // ── Fila 1: Título institucional (navy) ───────────────────────────────
        $xml .= '<row r="1" ht="28" customHeight="1">';
        for ($c = 0; $c < $colCount; $c++) {
            $ref = $this->colLetter($c + 1) . '1';
            $val = ($c === 0) ? 'UNI-VIRTUAL — Institución Universitaria Antonio José Camacho' : '';
            $xml .= $this->cStr($ref, $val, self::STYLE_TITLE);
        }
        $xml .= '</row>';

        // ── Fila 2: Subtítulo del reporte (blue) ─────────────────────────────
        $subtitle = $this->getSubtitle($name);
        $xml .= '<row r="2" ht="20" customHeight="1">';
        for ($c = 0; $c < $colCount; $c++) {
            $ref = $this->colLetter($c + 1) . '2';
            $val = ($c === 0) ? $subtitle : '';
            $xml .= $this->cStr($ref, $val, self::STYLE_SUBTITLE);
        }
        $xml .= '</row>';

        // ── Fila 3: Headers de columnas (navy) ────────────────────────────────
        $xml .= '<row r="3" ht="20" customHeight="1">';
        foreach ($headers as $i => $hdr) {
            $ref = $this->colLetter($i + 1) . '3';
            $xml .= $this->cStr($ref, $hdr, self::STYLE_HEADER);
        }
        $xml .= '</row>';

        // ── Filas de datos ────────────────────────────────────────────────────
        foreach ($sheet['rows'] as $ri => $row) {
            $rowNum = $ri + 4;
            $isAlt  = ($ri % 2 === 1);
            $xml .= '<row r="' . $rowNum . '" ht="18" customHeight="1">';

            foreach ($row as $ci => $val) {
                $ref  = $this->colLetter($ci + 1) . $rowNum;
                $type = $types[$ci] ?? 'string';

                if ($type === 'price' || $type === 'float') {
                    $num = is_numeric($val) ? (float)$val : 0.0;
                    // Colorear notas según aprobación
                    if ($num > 0 && $num <= 5.0) {
                        $style = $num >= 3.0 ? self::STYLE_OK : self::STYLE_MAL;
                    } else {
                        $style = $isAlt ? self::STYLE_NUM_ALT : self::STYLE_NUM;
                    }
                    $xml .= $this->cNum($ref, $num, $style);

                } elseif ($type === 'integer') {
                    $num   = (int)(is_numeric($val) ? $val : 0);
                    $style = $isAlt ? self::STYLE_CELL_ALT : self::STYLE_CELL_C;
                    $xml .= $this->cNum($ref, $num, $style);

                } else {
                    // string — aplicar color especial para Estado
                    $str = (string)$val;
                    $style = match($str) {
                        'Aprobado', 'Cumple' => self::STYLE_OK,
                        'Reprobado', 'Riesgo' => self::STYLE_MAL,
                        'En curso'  => self::STYLE_WARN,
                        default     => ($ci === 0)
                            ? ($isAlt ? self::STYLE_CELL_ALT : self::STYLE_CELL_L)
                            : ($isAlt ? self::STYLE_CELL_ALT : self::STYLE_CELL_C),
                    };
                    $xml .= $this->cStr($ref, $str, $style);
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        // Merge de las dos filas de encabezado
        $xml .= '<mergeCells count="2">';
        $xml .= '<mergeCell ref="A1:' . $lastCol . '1"/>';
        $xml .= '<mergeCell ref="A2:' . $lastCol . '2"/>';
        $xml .= '</mergeCells>';

        $xml .= '</worksheet>';
        return $xml;
    }

    // ── Helpers de celdas ─────────────────────────────────────────────────────
    private function cStr(string $ref, string $val, int $s): string {
        $i = $this->strIdx($val);
        return "<c r=\"{$ref}\" t=\"s\" s=\"{$s}\"><v>{$i}</v></c>";
    }

    private function cNum(string $ref, float|int $val, int $s): string {
        return "<c r=\"{$ref}\" s=\"{$s}\"><v>{$val}</v></c>";
    }

    private function strIdx(string $s): int {
        if (!isset($this->strIndex[$s])) {
            $this->strIndex[$s] = count($this->strings);
            $this->strings[]    = $s;
        }
        return $this->strIndex[$s];
    }

    private function colLetter(int $n): string {
        $r = '';
        while ($n > 0) {
            $n--;
            $r = chr(65 + ($n % 26)) . $r;
            $n = intdiv($n, 26);
        }
        return $r;
    }

    private function getSubtitle(string $name): string {
        return match($name) {
            'Calificaciones' => 'Reporte de Calificaciones — Semestre 2026-I',
            'Asistencia'     => 'Reporte de Asistencia — Semestre 2026-I',
            'Métricas'       => 'Métricas del Grupo — Semestre 2026-I',
            default          => $name . ' — 2026-I',
        };
    }

    private function xe(string $s): string {
        return htmlspecialchars($s, ENT_XML1, 'UTF-8');
    }

    // ── Archivos OOXML ────────────────────────────────────────────────────────
    private function buildContentTypes(): string {
        $sheets = '';
        foreach ($this->sheets as $i => $_) {
            $n = $i + 1;
            $sheets .= "<Override PartName=\"/xl/worksheets/sheet{$n}.xml\""
                     . " ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
             . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
             . '<Default Extension="xml" ContentType="application/xml"/>'
             . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
             . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
             . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
             . $sheets . '</Types>';
    }

    private function buildRels(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
             . '</Relationships>';
    }

    private function buildWorkbook(): string {
        $sheets = '';
        foreach ($this->sheets as $i => $name) {
            $n   = $i + 1;
            $esc = $this->xe($name);
            $sheets .= "<sheet name=\"{$esc}\" sheetId=\"{$n}\" r:id=\"rId{$n}\"/>";
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/sheet/2006/main"'
             . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             . '<sheets>' . $sheets . '</sheets></workbook>';
    }

    private function buildWorkbookRels(): string {
        $r = '';
        foreach ($this->sheets as $i => $_) {
            $n = $i + 1;
            $r .= "<Relationship Id=\"rId{$n}\""
                . " Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\""
                . " Target=\"worksheets/sheet{$n}.xml\"/>";
        }
        $n1 = count($this->sheets) + 1;
        $n2 = $n1 + 1;
        $r .= "<Relationship Id=\"rId{$n1}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>";
        $r .= "<Relationship Id=\"rId{$n2}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings\" Target=\"sharedStrings.xml\"/>";
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . $r . '</Relationships>';
    }

    private function buildStyles(): string {
        // IMPORTANTE: el orden y los índices de cellXfs deben coincidir
        // exactamente con las constantes STYLE_* definidas arriba.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/sheet/2006/main">'

             . '<numFmts count="1">'
             . '<numFmt numFmtId="164" formatCode="0.00"/>'
             . '</numFmts>'

             . '<fonts count="6">'
             . '<font><sz val="10"/><name val="Arial"/></font>'                                              // 0 normal
             . '<font><sz val="10"/><name val="Arial"/><b/></font>'                                          // 1 bold
             . '<font><sz val="14"/><name val="Arial"/><b/><color rgb="FFFFFFFF"/></font>'                   // 2 white 14
             . '<font><sz val="12"/><name val="Arial"/><b/><color rgb="FFFFFFFF"/></font>'                   // 3 white 12
             . '<font><sz val="10"/><name val="Arial"/><b/><color rgb="FF1A7A48"/></font>'                   // 4 verde
             . '<font><sz val="10"/><name val="Arial"/><b/><color rgb="FFC0392B"/></font>'                   // 5 rojo
             . '</fonts>'

             . '<fills count="8">'
             . '<fill><patternFill patternType="none"/></fill>'                                               // 0
             . '<fill><patternFill patternType="gray125"/></fill>'                                            // 1
             . '<fill><patternFill patternType="solid"><fgColor rgb="FF0D1F4E"/><bgColor indexed="64"/></patternFill></fill>'  // 2 navy
             . '<fill><patternFill patternType="solid"><fgColor rgb="FF2462B0"/><bgColor indexed="64"/></patternFill></fill>'  // 3 blue
             . '<fill><patternFill patternType="solid"><fgColor rgb="FFF5F7FB"/><bgColor indexed="64"/></patternFill></fill>'  // 4 light alt
             . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F5EE"/><bgColor indexed="64"/></patternFill></fill>'  // 5 verde claro
             . '<fill><patternFill patternType="solid"><fgColor rgb="FFFDECEA"/><bgColor indexed="64"/></patternFill></fill>'  // 6 rojo claro
             . '<fill><patternFill patternType="solid"><fgColor rgb="FFFDF3E4"/><bgColor indexed="64"/></patternFill></fill>'  // 7 dorado claro
             . '</fills>'

             . '<borders count="2">'
             . '<border><left/><right/><top/><bottom/><diagonal/></border>'
             . '<border>'
             . '<left style="thin"><color rgb="FFDDDDDD"/></left>'
             . '<right style="thin"><color rgb="FFDDDDDD"/></right>'
             . '<top style="thin"><color rgb="FFDDDDDD"/></top>'
             . '<bottom style="thin"><color rgb="FFDDDDDD"/></bottom>'
             . '<diagonal/></border>'
             . '</borders>'

             . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'

             // cellXfs — índices 0-11, deben coincidir con constantes STYLE_*
             . '<cellXfs count="12">' // indices 0-11
             // 0 DEFAULT
             . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
             // 1 TITLE: navy bg, white 14 bold, centrado
             . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 2 SUBTITLE: blue bg, white 12 bold, centrado
             . '<xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 3 HEADER: navy bg, white bold 10, centrado, borde
             . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
             // 4 CELL_L: blanco, negro, izquierda
             . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="left" vertical="center"/></xf>'
             // 5 CELL_C: blanco, negro, centrado
             . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 6 CELL_ALT: azul claro, negro, centrado
             . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 7 NUM: blanco, negro, centrado, 0.00
             . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 8 NUM_ALT: azul claro, negro, centrado, 0.00
             . '<xf numFmtId="164" fontId="0" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 9 OK: verde claro bg, verde bold
             . '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 10 MAL: rojo claro bg, rojo bold
             . '<xf numFmtId="0" fontId="5" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             // 11 WARN: dorado claro bg
             . '<xf numFmtId="0" fontId="1" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
             . '<alignment horizontal="center" vertical="center"/></xf>'
             . '</cellXfs>'

             . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
             . '</styleSheet>';
    }

    private function buildSharedStrings(): string {
        $total = count($this->strings);
        $xml   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
               . "<sst xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/sheet/2006/main\""
               . " count=\"{$total}\" uniqueCount=\"{$total}\">";
        foreach ($this->strings as $s) {
            $xml .= '<si><t xml:space="preserve">' . $this->xe($s) . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }
}