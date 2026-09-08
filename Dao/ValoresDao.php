<?php
// Dao/ValoresDao.php
// Lector del archivo .xlsx de valores diarios (columnas: DISCO, FECHA, VALOR, RUTA)

class ValoresDao {
    private $conexion;

    const RUTA_XLSX = __DIR__ . '/../database/valores_diarios.xlsx';

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function archivoExiste() {
        return is_file(self::RUTA_XLSX);
    }

    public function fechaSubida() {
        return is_file(self::RUTA_XLSX) ? date('d/m/Y H:i', filemtime(self::RUTA_XLSX)) : null;
    }

    private function colIndex($letra) {
        $idx = 0;
        $len = strlen($letra);
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letra[$i]) - 64);
        }
        return $idx;
    }

    public function leerFilas() {
        if (!is_file(self::RUTA_XLSX)) return [];

        $zip = new ZipArchive();
        if ($zip->open(self::RUTA_XLSX) !== true) return [];

        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $xmlShared = new SimpleXMLElement($ss);
            foreach ($xmlShared->si as $si) {
                $texto = '';
                foreach ($si->t as $t) {
                    $texto .= (string)$t;
                }
                $shared[] = trim($texto);
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) return [];

        $xml = new SimpleXMLElement($sheet);
        $filasCrudas = [];

        foreach ($xml->sheetData->row as $row) {
            $fila = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                $letra = preg_replace('/[0-9]+/', '', $ref);
                $col = $this->colIndex($letra) - 1;
                $t = (string)$c['t'];
                $v = (string)$c->v;
                $valor = '';
                if ($t === 's') {
                    $valor = $shared[(int)$v] ?? '';
                } elseif ($t === 'inlineStr') {
                    $valor = (string)$c->is->t;
                } else {
                    $valor = $v;
                }
                $fila[$col] = trim($valor);
            }
            ksort($fila);
            $filasCrudas[] = $fila;
        }

        if (empty($filasCrudas)) return [];

        $cabeceras = array_shift($filasCrudas);
        $idx = ['disco' => null, 'fecha' => null, 'valor' => null, 'ruta' => null];
        foreach ($cabeceras as $col => $nombre) {
            $nombreKey = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $nombre));
            if (in_array($nombreKey, ['disco', 'dsc', 'n.disco', 'bus'])) $idx['disco'] = $col;
            if (in_array($nombreKey, ['fecha', 'dia', 'date'])) $idx['fecha'] = $col;
            if (in_array($nombreKey, ['valor', 'value', 'monto', 'costo'])) $idx['valor'] = $col;
            if (in_array($nombreKey, ['ruta', 'linea', 'recorrido'])) $idx['ruta'] = $col;
        }

        $filas = [];
        foreach ($filasCrudas as $fila) {
            $dc = $idx['disco'] !== null && isset($fila[$idx['disco']]) ? $fila[$idx['disco']] : '';
            $fc = $idx['fecha'] !== null && isset($fila[$idx['fecha']]) ? $fila[$idx['fecha']] : '';
            $vc = $idx['valor'] !== null && isset($fila[$idx['valor']]) ? $fila[$idx['valor']] : '';
            $rc = $idx['ruta'] !== null && isset($fila[$idx['ruta']]) ? $fila[$idx['ruta']] : '';

            if ($dc === '' && $fc === '') continue;

            $filas[] = [
                'disco' => $this->normalizarDisco($dc),
                'fecha' => $this->normalizarFecha($fc),
                'valor' => $this->normalizarValor($vc),
                'ruta' => $rc
            ];
        }

        return $filas;
    }

    public function obtenerParaDiscoFecha($disco, $fechaSql) {
        $discoNorm = $this->normalizarDisco($disco);
        $fechaSql = date('Y-m-d', strtotime($fechaSql));
        foreach ($this->leerFilas() as $fila) {
            if ($fila['disco'] === $discoNorm && $fila['fecha'] === $fechaSql) {
                return $fila;
            }
        }
        return null;
    }

    public function obtenerValorHoy() {
        return $this->obtenerParaDiscoFechaISHoy();
    }

    public function obtenerDiscoHoy($disco) {
        return $this->obtenerParaDiscoFecha($disco, date('Y-m-d'));
    }

    public function obtenerFilasFiltradas($disco = '', $fecha = '', $valor = '', $ruta = '') {
        $disco = trim((string)$disco);
        $fecha = trim((string)$fecha);
        $valor = trim((string)$valor);
        $ruta = trim((string)$ruta);
        $valorNumerico = str_replace(',', '.', $valor);

        return array_values(array_filter(
            $this->leerFilas(),
            static function ($fila) use ($disco, $fecha, $valor, $valorNumerico, $ruta) {
                if ($disco !== '' && stripos((string)$fila['disco'], $disco) === false) {
                    return false;
                }
                if ($fecha !== '' && (string)$fila['fecha'] !== $fecha) {
                    return false;
                }
                if ($valor !== '' && (!is_numeric($valorNumerico) || abs((float)$fila['valor'] - (float)$valorNumerico) > 0.00001)) {
                    return false;
                }
                if ($ruta !== '' && stripos((string)$fila['ruta'], $ruta) === false) {
                    return false;
                }
                return true;
            }
        ));
    }

    public function firmaArchivo() {
        clearstatcache(true, self::RUTA_XLSX);
        if (!is_file(self::RUTA_XLSX)) {
            return 'sin-archivo';
        }
        return filemtime(self::RUTA_XLSX) . ':' . filesize(self::RUTA_XLSX);
    }

    public function actualizarTurnosDesdeFilas(array $filas) {
        $actualizados = 0;

        foreach ($filas as $fila) {
            if (empty($fila['disco']) || empty($fila['fecha']) || (float)$fila['valor'] <= 0) {
                continue;
            }

            if (is_numeric($fila['disco'])) {
                $sql = "UPDATE turno t
                        INNER JOIN bus b ON t.bus_id = b.id
                        SET t.valor = :valor, t.ruta = :ruta
                        WHERE t.fecha = :fecha
                          AND t.pagado = 0
                          AND CAST(b.disco AS UNSIGNED) = :disco";
                $disco = (int)$fila['disco'];
            } else {
                $sql = "UPDATE turno t
                        INNER JOIN bus b ON t.bus_id = b.id
                        SET t.valor = :valor, t.ruta = :ruta
                        WHERE t.fecha = :fecha
                          AND t.pagado = 0
                          AND b.disco = :disco";
                $disco = $fila['disco'];
            }

            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':valor' => $fila['valor'],
                ':ruta' => $fila['ruta'] ?: null,
                ':fecha' => $fila['fecha'],
                ':disco' => $disco
            ]);
            $actualizados += $stmt->rowCount();
        }

        return $actualizados;
    }

    public function sincronizarTurnosConArchivo() {
        return $this->actualizarTurnosDesdeFilas($this->leerFilas());
    }

    private function obtenerParaDiscoFechaISHoy() {
        return null;
    }

    public function normalizarDisco($valor) {
        $valor = trim($valor);
        if (is_numeric($valor)) {
            return (string)(int)$valor;
        }
        $valorSinCero = ltrim($valor, '0');
        return $valorSinCero === '' ? '0' : $valorSinCero;
    }

    public function normalizarFecha($valor) {
        $valor = trim($valor);
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}/', $valor, $m)) {
            return date('Y-m-d', strtotime($m[0]));
        }
        if (is_numeric($valor)) {
            $unix = ($valor - 25569) * 86400;
            return gmdate('Y-m-d', (int)round($unix));
        }
        $ts = strtotime($valor);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    public function normalizarValor($valor) {
        $money = trim($valor);
        $money = str_replace(['$', ' '], '', $money);

        if (strpos($money, ',') !== false && strpos($money, '.') !== false) {
            $money = str_replace('.', '', $money);
            $money = str_replace(',', '.', $money);
        } elseif (strpos($money, ',') !== false) {
            $money = str_replace(',', '.', $money);
        } elseif (preg_match('/^\d+\.\d{3}$/', $money)) {
            $money = str_replace('.', '', $money);
        }

        return (float)$money;
    }
}
?>
