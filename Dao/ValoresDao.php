<?php
// Dao/ValoresDao.php
// Lector del archivo .xlsx de valores diarios (columnas: DISCO, FECHA, VALOR, RUTA)

class ValoresDao {
    private $conexion;

    const RUTA_XLSX = __DIR__ . '/../data/valores_diarios.xlsx';

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function archivoExiste() {
        clearstatcache(true, self::RUTA_XLSX . '.subido');
        $marca = self::RUTA_XLSX . '.subido';
        return is_file($marca);
    }

    public function fechaSubida() {
        return $this->archivoExiste() ? date('d/m/Y H:i', filemtime(self::RUTA_XLSX . '.subido')) : null;
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
        $stmt = $this->conexion->prepare(
            "SELECT id, disco, fecha, valor, ruta, pagado
             FROM obligacion_pago
             WHERE disco = :disco AND fecha = :fecha AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([':disco' => $discoNorm, ':fecha' => $fechaSql]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerValorHoy() {
        return $this->obtenerParaDiscoFechaISHoy();
    }

    public function obtenerDiscoHoy($disco) {
        return $this->obtenerParaDiscoFecha($disco, date('Y-m-d'));
    }

    public function obtenerFilasFiltradas($disco = '', $fecha = '', $valor = '', $ruta = '') {
        if (!$this->archivoExiste()) return [];
        $disco = trim((string)$disco);
        $fecha = trim((string)$fecha);
        $valor = trim((string)$valor);
        $ruta = trim((string)$ruta);
        $valorNumerico = str_replace(',', '.', $valor);

        $sql = "SELECT id, disco, fecha, valor, ruta, pagado
                FROM obligacion_pago
                WHERE activo = 1";
        $parametros = [];
        if ($disco !== '') {
            $sql .= " AND disco LIKE :disco";
            $parametros[':disco'] = '%' . $this->normalizarDisco($disco) . '%';
        }
        if ($fecha !== '') {
            $sql .= " AND fecha = :fecha";
            $parametros[':fecha'] = $fecha;
        }
        if ($valor !== '' && is_numeric($valorNumerico)) {
            $sql .= " AND valor = :valor";
            $parametros[':valor'] = (float)$valorNumerico;
        }
        if ($ruta !== '') {
            $sql .= " AND ruta LIKE :ruta";
            $parametros[':ruta'] = '%' . $ruta . '%';
        }
        $sql .= " ORDER BY fecha DESC, CAST(disco AS UNSIGNED), id DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public function firmaArchivo() {
        $stmt = $this->conexion->query(
            "SELECT COUNT(*) AS total, COALESCE(MAX(id), 0) AS ultimo,
                    COALESCE(SUM(pagado), 0) AS pagados
             FROM obligacion_pago WHERE activo = 1"
        );
        $firma = $stmt->fetch();
        return $firma['total'] . ':' . $firma['ultimo'] . ':' . $firma['pagados'] . ':' . (int)$this->archivoExiste() . ':' . $this->fechaSubida();
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

    public function sincronizarObligacionesDesdeFilas(array $filas) {
        $insertadas = 0;
        $omitidas = 0;
        $sql = "INSERT IGNORE INTO obligacion_pago (disco, fecha, valor, ruta, pagado, activo)
                VALUES (:disco, :fecha, :valor, :ruta, 0, 1)";
        $stmt = $this->conexion->prepare($sql);

        foreach ($filas as $fila) {
            if (empty($fila['disco']) || empty($fila['fecha']) || (float)$fila['valor'] <= 0) {
                continue;
            }
            $stmt->execute([
                ':disco' => $this->normalizarDisco($fila['disco']),
                ':fecha' => $fila['fecha'],
                ':valor' => $fila['valor'],
                ':ruta' => $fila['ruta'] ?: null
            ]);
            if ($stmt->rowCount() === 1) {
                $insertadas++;
            } else {
                $omitidas++;
            }
        }

        return ['insertadas' => $insertadas, 'omitidas' => $omitidas];
    }

    public function sincronizarObligacionesConArchivo() {
        return $this->sincronizarObligacionesDesdeFilas($this->leerFilas());
    }

    private function obtenerParaDiscoFechaISHoy() {
        return null;
    }

    public function normalizarDisco($valor) {
        $valor = trim($valor);
        if (is_numeric($valor)) {
            return str_pad((string)(int)$valor, 2, '0', STR_PAD_LEFT);
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
