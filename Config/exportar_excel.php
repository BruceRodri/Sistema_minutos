<?php
// XLSX nativo sin dependencias externas; los textos siempre son celdas de texto (no fórmulas).
function crearExcelFiltrado(array $encabezados, array $filas): string {
    $xml = static fn($v) => htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)$v), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $columna = static function (int $n): string {
        $nombre = '';
        do { $nombre = chr(65 + $n % 26) . $nombre; $n = intdiv($n, 26) - 1; } while ($n >= 0);
        return $nombre;
    };
    $ruta = tempnam(sys_get_temp_dir(), 'excel_');
    $zip = new ZipArchive();
    if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('No se pudo crear el Excel.');
    try {
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Datos filtrados" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="4"><xf fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="14" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>';
        foreach ($encabezados as $i => $titulo) $sheet .= '<col min="'.($i+1).'" max="'.($i+1).'" width="'.(preg_match('/nombre|conductor|motivo|ruta|usuario/i', $titulo) ? 36 : 22).'" customWidth="1"/>';
        $sheet .= '</cols><sheetData>';
        foreach (array_merge([$encabezados], $filas) as $r => $fila) {
            $sheet .= '<row r="'.($r+1).'"'.($r === 0 ? ' ht="32" customHeight="1"' : '').'>';
            foreach (array_values($fila) as $c => $valor) {
                $ref = $columna($c).($r+1);
                if ($r > 0 && (is_int($valor) || is_float($valor))) {
                    $sheet .= '<c r="'.$ref.'" s="'.(is_float($valor) ? 2 : 0).'" t="n"><v>'.$valor.'</v></c>';
                } elseif ($r > 0 && is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valor) && strtotime($valor) !== false) {
                    $fecha = new DateTimeImmutable($valor, new DateTimeZone('UTC'));
                    $serial = (int)(new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC')))->diff($fecha)->format('%r%a');
                    $sheet .= '<c r="'.$ref.'" s="3" t="n"><v>'.$serial.'</v></c>';
                } else $sheet .= '<c r="'.$ref.'" s="'.($r === 0 ? 1 : 0).'" t="inlineStr"><is><t xml:space="preserve">'.$xml($valor ?? '').'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData><autoFilter ref="A1:'.$columna(count($encabezados)-1).(count($filas)+1).'"/></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        return $ruta;
    } catch (Throwable $e) { $zip->close(); @unlink($ruta); throw $e; }
}
function descargarExcel(string $nombre, array $encabezados, array $filas): never {
    $ruta = crearExcelFiltrado($encabezados, $filas);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$nombre.'-'.date('Y-m-d').'.xlsx"');
    header('Cache-Control: no-store');
    header('Content-Length: '.filesize($ruta));
    try { readfile($ruta); } finally { unlink($ruta); }
    exit;
}
