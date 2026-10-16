<?php
/**
 * FPDF Stub — Implementación mínima para UNI-VIRTUAL
 * Compatible con la API de FPDF 1.86
 * Para producción, reemplazar con la librería FPDF oficial: http://www.fpdf.org/
 */
if (!class_exists('FPDF')) {
class FPDF {
    protected $pages = [];
    protected $currentPage = 0;
    protected $fonts = [];
    protected $fontFamily = '';
    protected $fontSize = 10;
    protected $fontStyle = '';
    protected $fillColor = [255, 255, 255];
    protected $textColor = [0, 0, 0];
    protected $drawColor = [0, 0, 0];
    protected $x = 10;
    protected $y = 10;
    protected $w = 210;     // A4 ancho mm
    protected $h = 297;     // A4 alto mm
    protected $lMargin = 15;
    protected $rMargin = 15;
    protected $tMargin = 15;
    protected $bMargin = 20;
    protected $lineWidth = 0.2;
    protected $content = '';
    protected $orientation;
    protected $unit;

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4') {
        $this->orientation = $orientation;
        $this->unit        = $unit;
        if ($orientation === 'L') { $tmp=$this->w; $this->w=$this->h; $this->h=$tmp; }
    }

    public function AddPage($orientation = '') {
        $this->currentPage++;
        $this->pages[$this->currentPage] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }

    public function SetFont($family, $style = '', $size = 0) {
        $this->fontFamily = $family;
        $this->fontStyle  = $style;
        if ($size > 0) $this->fontSize = $size;
    }

    public function SetFontSize($size) { $this->fontSize = $size; }

    public function SetFillColor($r, $g = 0, $b = 0) {
        $this->fillColor = [$r, $g, $b];
    }

    public function SetTextColor($r, $g = 0, $b = 0) {
        $this->textColor = [$r, $g, $b];
    }

    public function SetDrawColor($r, $g = 0, $b = 0) {
        $this->drawColor = [$r, $g, $b];
    }

    public function SetLineWidth($w) { $this->lineWidth = $w; }
    public function SetMargins($l, $t, $r = -1) {
        $this->lMargin = $l; $this->tMargin = $t;
        $this->rMargin = ($r < 0) ? $l : $r;
    }
    public function SetLeftMargin($m)   { $this->lMargin = $m; }
    public function SetTopMargin($m)    { $this->tMargin = $m; }
    public function SetAutoPageBreak($auto, $margin = 0) { $this->bMargin = $margin; }
    public function GetPageWidth()  { return $this->w; }
    public function GetPageHeight() { return $this->h; }
    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }
    public function SetX($x) { $this->x = $x; }
    public function SetY($y) { $this->y = $y; $this->x = $this->lMargin; }
    public function SetXY($x, $y) { $this->x = $x; $this->y = $y; }

    public function Ln($h = null) {
        $this->x = $this->lMargin;
        $this->y += ($h ?? $this->fontSize * 0.4);
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0,
                         $align = '', $fill = false, $link = '') {
        // Acumular contenido de la página actual como texto plano
        if ($this->currentPage > 0) {
            $this->pages[$this->currentPage] .= $txt . ($ln ? "\n" : "\t");
        }
        if ($ln == 1) { $this->x = $this->lMargin; $this->y += $h; }
        else { $this->x += $w; }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        if ($this->currentPage > 0) {
            $this->pages[$this->currentPage] .= $txt . "\n";
        }
        $lines = ceil(strlen($txt) / max(1, ($w / ($this->fontSize * 0.5))));
        $this->y += $h * max(1, $lines);
        $this->x = $this->lMargin;
    }

    public function Write($h, $txt, $link = '') {
        if ($this->currentPage > 0) {
            $this->pages[$this->currentPage] .= $txt;
        }
        $this->x += strlen($txt) * $this->fontSize * 0.3;
    }

    public function Image($file, $x = null, $y = null, $w = 0, $h = 0) {}
    public function Line($x1, $y1, $x2, $y2) {}
    public function Rect($x, $y, $w, $h, $style = '') {}

    /**
     * Output — genera el PDF real como HTML imprimible si es posible,
     * o retorna los datos de texto plano empaquetados en un PDF mínimo.
     */
    public function Output($dest = 'I', $name = 'doc.pdf', $isUTF8 = false) {
        // Construir contenido de todas las páginas
        $allText = '';
        foreach ($this->pages as $pg => $content) {
            $allText .= "=== Página $pg ===\n$content\n";
        }

        switch (strtoupper($dest)) {
            case 'I': // Inline — enviar al navegador como HTML imprimible
                header('Content-Type: text/html; charset=utf-8');
                echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
                echo '<title>' . htmlspecialchars($name) . '</title>';
                echo '<style>body{font-family:Arial,sans-serif;font-size:12px;margin:20px;}';
                echo 'pre{white-space:pre-wrap;} @media print{button{display:none;}}</style></head><body>';
                echo '<button onclick="window.print()" style="margin-bottom:10px;padding:8px 16px;">🖨️ Imprimir / Guardar PDF</button>';
                echo '<pre>' . htmlspecialchars($allText) . '</pre></body></html>';
                break;
            case 'D': // Download
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $name . '"');
                // Generar PDF mínimo válido con el texto
                echo $this->_buildMinimalPDF($allText, $name);
                break;
            case 'S': // String
                return $this->_buildMinimalPDF($allText, $name);
            case 'F': // File
                file_put_contents($name, $this->_buildMinimalPDF($allText, $name));
                break;
        }
    }

    /**
     * Genera un PDF mínimo válido con el texto como contenido.
     * Suficiente para descargar y abrir en Adobe Reader.
     */
    private function _buildMinimalPDF(string $text, string $title): string {
        $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        $text = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $text);
        $lines = explode("\n", wordwrap($text, 90, "\n", true));
        $stream = "BT\n/F1 10 Tf\n1 0 0 1 40 800 Tm\n14 TL\n";
        foreach ($lines as $line) {
            $stream .= "(" . $line . ") Tj T*\n";
        }
        $stream .= "ET\n";
        $len = strlen($stream);
        $pdf  = "%PDF-1.4\n";
        $pdf .= "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n";
        $pdf .= "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n";
        $pdf .= "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]";
        $pdf .= "/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n";
        $pdf .= "4 0 obj<</Length $len>>\nstream\n$stream\nendstream\nendobj\n";
        $pdf .= "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n";
        $startxref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= "trailer<</Size 6/Root 1 0 R>>\nstartxref\n$startxref\n%%EOF";
        return $pdf;
    }
}
}
