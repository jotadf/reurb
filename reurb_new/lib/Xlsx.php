<?php
/**
 * Gerador simples de planilha Excel (.xlsx), sem bibliotecas externas.
 * Precisa da extensão zip do PHP (classe ZipArchive).
 *
 * - Cabeçalho em duas linhas: grupos (células mescladas) e nomes das colunas.
 * - Números, datas e data/hora gravados como tipos do Excel (somam e filtram certo).
 * - Textos gravados como texto puro: nada vira fórmula.
 * - Linhas gravadas direto em arquivo temporário (não acumula tudo na memória).
 * - Primeiras linhas congeladas, filtro no cabeçalho e largura automática das colunas.
 */

declare(strict_types=1);

class Xlsx
{
    private const ESTILO_GRUPO = 1;
    private const ESTILO_CABECALHO = 2;
    private const ESTILO_DATA = 3;
    private const ESTILO_DATAHORA = 4;
    private const ESTILO_DECIMAL = 5;

    private string $nomeAba;
    private array $grupos = [];      // [['titulo'=>, 'qtd'=>], ...]
    private array $titulos = [];
    private array $larguras = [];
    private int $linha = 0;
    private string $tmp;
    /** @var resource */
    private $arquivo;

    public static function disponivel(): bool
    {
        return class_exists('ZipArchive');
    }

    public function __construct(string $nomeAba = 'Planilha')
    {
        // Nome de aba: até 31 caracteres, sem : \ / ? * [ ]
        $this->nomeAba = mb_substr(preg_replace('#[:\\\\/?*\[\]]#', ' ', $nomeAba), 0, 31);
        $this->tmp     = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->arquivo = fopen($this->tmp, 'wb');
    }

    public function __destruct()
    {
        if (is_resource($this->arquivo)) {
            fclose($this->arquivo);
        }
        if (is_file($this->tmp)) {
            @unlink($this->tmp);
        }
    }

    /**
     * @param array $grupos  [['titulo' => 'Lote', 'qtd' => 8], ...] (opcional)
     * @param array $titulos nomes das colunas
     */
    public function cabecalho(array $grupos, array $titulos): void
    {
        $this->grupos  = $grupos;
        $this->titulos = array_values($titulos);
        if ($grupos) {
            $cel = [];
            $col = 0;
            foreach ($grupos as $g) {
                $cel[] = $this->celTexto($col, (string) $g['titulo'], self::ESTILO_GRUPO);
                for ($i = 1; $i < $g['qtd']; $i++) {
                    $cel[] = '<c r="' . self::ref($col + $i, $this->linha + 1) . '" s="' . self::ESTILO_GRUPO . '"/>';
                }
                $col += $g['qtd'];
            }
            $this->gravarLinha($cel, 20);
        }
        $cel = [];
        foreach ($this->titulos as $i => $t) {
            $cel[] = $this->celTexto($i, (string) $t, self::ESTILO_CABECALHO);
            $this->medir($i, (string) $t, true);
        }
        $this->gravarLinha($cel, 32);
    }

    /**
     * @param array $valores [[tipo, valor], ...] tipo: texto | numero | decimal | data | datahora
     */
    public function linha(array $valores): void
    {
        $cel = [];
        foreach (array_values($valores) as $i => [$tipo, $v]) {
            if ($v === null || $v === '') {
                continue;
            }
            switch ($tipo) {
                case 'numero':
                case 'decimal':   // decimal = 2 casas com separador de milhar (ex.: valores em R$)
                    if (is_numeric($v)) {
                        $cel[] = '<c r="' . self::ref($i, $this->linha + 1) . '"' . ($tipo === 'decimal' ? ' s="' . self::ESTILO_DECIMAL . '"' : '')
                               . '><v>' . (0 + $v) . '</v></c>';
                        $this->medir($i, (string) $v);
                        continue 2;
                    }
                    break;
                case 'data':
                case 'datahora':
                    $serial = self::serial((string) $v, $tipo === 'datahora');
                    if ($serial !== null) {
                        $cel[] = '<c r="' . self::ref($i, $this->linha + 1) . '" s="'
                               . ($tipo === 'data' ? self::ESTILO_DATA : self::ESTILO_DATAHORA) . '"><v>' . $serial . '</v></c>';
                        $this->medir($i, $tipo === 'data' ? '00/00/0000' : '00/00/0000 00:00');
                        continue 2;
                    }
                    break;
            }
            $cel[] = $this->celTexto($i, (string) $v);
            $this->medir($i, (string) $v);
        }
        $this->gravarLinha($cel);
    }

    public function totalLinhas(): int
    {
        return $this->linha;
    }

    /** Monta o .xlsx e devolve o caminho de um arquivo temporário (apague depois de usar). */
    public function salvar(): string
    {
        fclose($this->arquivo);
        $ncol   = max(1, count($this->titulos));
        $ultCol = self::coluna($ncol - 1);
        $linhaCab = $this->grupos ? 2 : 1;
        $aba = htmlspecialchars($this->nomeAba, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // Larguras
        $cols = '<cols>';
        for ($i = 0; $i < $ncol; $i++) {
            $w = min(60, max(8, ($this->larguras[$i] ?? 8) + 2));
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $cols .= '</cols>';

        $mescla = '';
        if ($this->grupos) {
            $col = 0;
            $partes = [];
            foreach ($this->grupos as $g) {
                if ($g['qtd'] > 1) {
                    $partes[] = '<mergeCell ref="' . self::ref($col, 1) . ':' . self::ref($col + $g['qtd'] - 1, 1) . '"/>';
                }
                $col += $g['qtd'];
            }
            if ($partes) {
                $mescla = '<mergeCells count="' . count($partes) . '">' . implode('', $partes) . '</mergeCells>';
            }
        }

        $ultimaLinha = max($this->linha, $linhaCab);
        $filtro = $ultCol . $ultimaLinha;
        $inicioPlanilha = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $filtro . '"/>'
            . '<sheetViews><sheetView workbookViewId="0" tabSelected="1">'
            . '<pane xSplit="1" ySplit="' . $linhaCab . '" topLeftCell="B' . ($linhaCab + 1) . '" activePane="bottomRight" state="frozen"/>'
            . '<selection pane="bottomRight" activeCell="B' . ($linhaCab + 1) . '" sqref="B' . ($linhaCab + 1) . '"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>' . $cols . '<sheetData>';
        $fimPlanilha = '</sheetData><autoFilter ref="A' . $linhaCab . ':' . $filtro . '"/>' . $mescla
            . '<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '<pageSetup orientation="landscape" paperSize="9"/></worksheet>';

        $planilha = tempnam(sys_get_temp_dir(), 'xlsx');
        $out = fopen($planilha, 'wb');
        fwrite($out, $inicioPlanilha);
        $in = fopen($this->tmp, 'rb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fwrite($out, $fimPlanilha);
        fclose($out);

        $destino = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($destino, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo Excel.');
        }
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>');
        $zip->addFromString('docProps/core.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . $aba . '</dc:title><dc:creator>REURB</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
            . '</cp:coreProperties>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $aba . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">'
            . "'" . str_replace("'", "''", $aba) . "'!\$A\$" . $linhaCab . ':$' . $ultCol . '$' . $ultimaLinha
            . '</definedName></definedNames></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', self::estilos());
        $zip->addFile($planilha, 'xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($planilha);
        @unlink($this->tmp);
        return $destino;
    }

    /* ------------------------------------------------------------------ */

    private function gravarLinha(array $celulas, ?int $altura = null): void
    {
        $this->linha++;
        fwrite($this->arquivo, '<row r="' . $this->linha . '"'
            . ($altura ? ' ht="' . $altura . '" customHeight="1"' : '') . '>' . implode('', $celulas) . '</row>');
    }

    private function celTexto(int $col, string $texto, int $estilo = 0): string
    {
        // Remove caracteres que o XML não aceita e respeita o limite do Excel por célula
        $texto = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $texto) ?? '';
        $texto = mb_substr($texto, 0, 32767);
        return '<c r="' . self::ref($col, $this->linha + 1) . '" t="inlineStr"' . ($estilo ? ' s="' . $estilo . '"' : '')
             . '><is><t xml:space="preserve">' . htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    }

    private function medir(int $col, string $texto, bool $cabecalho = false): void
    {
        $n = mb_strlen($texto);
        if ($cabecalho) {
            $n = (int) ceil($n / 2) + 2;   // cabeçalho quebra em duas linhas
        }
        $this->larguras[$col] = max($this->larguras[$col] ?? 0, min($n, 60));
    }

    private static function serial(string $v, bool $comHora): ?float
    {
        $d = DateTime::createFromFormat($comHora ? 'Y-m-d H:i:s' : '!Y-m-d', substr($v, 0, $comHora ? 19 : 10), new DateTimeZone('UTC'));
        if (!$d) {
            return null;
        }
        // Dias desde 30/12/1899 (padrão do Excel); com hora, a fração do dia
        return $comHora ? round($d->getTimestamp() / 86400 + 25569, 10) : (float) (intdiv($d->getTimestamp(), 86400) + 25569);
    }

    public static function coluna(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private static function ref(int $col, int $linha): string
    {
        return self::coluna($col) . $linha;
    }

    private static function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy hh:mm"/></numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF1F2D5A"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF4E73DF"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCE3F7"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFB7C3E6"/></left><right style="thin"><color rgb="FFB7C3E6"/></right>'
            . '<top style="thin"><color rgb="FFB7C3E6"/></top><bottom style="thin"><color rgb="FFB7C3E6"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
