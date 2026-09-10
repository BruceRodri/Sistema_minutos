<?php

class PagoDao {
    private $conexion;
    private $ultimoCodigoDuplicado = null;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    /**
     * Normaliza el comprobante de un pago a un array de rutas relativas.
     * Soporta valores antiguos (ruta plana en varchar) y los nuevos (JSON array).
     */
    public static function normalizarComprobantes($valor): array {
        if ($valor === null || $valor === '') return [];
        $decodificado = json_decode((string)$valor, true);
        if (is_array($decodificado)) {
            return array_values(array_filter(array_map('strval', $decodificado), static fn($r) => trim($r) !== ''));
        }
        return [(string)$valor];
    }

    /** Serializa a JSON array (formato nuevo). */
    private static function serializarComprobantes($valor) {
        $lista = is_array($valor) ? $valor : self::normalizarComprobantes($valor);
        $lista = array_values(array_unique(array_filter($lista, static fn($r) => trim((string)$r) !== '')));
        if (!$lista) return null;
        return json_encode($lista, JSON_UNESCAPED_SLASHES);
    }

    private function encontrarPagoAnuladoReutilizable($usuarioId, array $ids, $claveDetalle) {
        sort($ids, SORT_NUMERIC);
        $stmt = $this->conexion->prepare(
            "SELECT id, detalle_pagos FROM pago
             WHERE usuario_id = ? AND estado = 'anulado' AND activo = 1
             ORDER BY id DESC FOR UPDATE"
        );
        $stmt->execute([(int)$usuarioId]);
        foreach ($stmt->fetchAll() as $pago) {
            $detalle = json_decode($pago['detalle_pagos'] ?? '', true);
            if (!is_array($detalle)) continue;
            $idsDetalle = [];
            foreach ($detalle as $fila) {
                if (!is_array($fila) || !isset($fila[$claveDetalle])) continue 2;
                $idsDetalle[] = (int)$fila[$claveDetalle];
            }
            sort($idsDetalle, SORT_NUMERIC);
            if ($idsDetalle === $ids) return (int)$pago['id'];
        }
        return null;
    }

    public function obtenerTurnoHoyConductor($usuarioId) {
        $sql = "SELECT t.id, t.fecha, t.hora_apertura, t.valor, t.ruta, t.pagado, b.disco
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                WHERE t.usuario_id = :usuario_id AND t.fecha = CURDATE()
                ORDER BY t.hora_apertura DESC
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetch();
    }

    public function obtenerTurnosPendientesConductor($usuarioId) {
        $sql = "SELECT t.id, t.fecha, t.valor, t.ruta, t.hora_apertura, b.disco
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                WHERE t.usuario_id = :usuario_id
                  AND t.fecha < CURDATE()
                  AND t.pagado = 0
                  AND t.valor > 0
                ORDER BY t.fecha ASC, t.hora_apertura ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function obtenerPagosConductor($usuarioId) {
        return $this->obtenerPagos($usuarioId);
    }

    public function obtenerTodosPagos() {
        return $this->obtenerPagos(null);
    }

    private function obtenerPagos($usuarioId = null) {
        $sql = "SELECT p.id, p.monto_total AS monto, p.fecha_pago, p.comprobante, p.estado,
                       p.motivo_rechazo, p.detalle_pagos
                FROM pago p
                WHERE p.activo = 1";
        $parametros = [];
        if ($usuarioId !== null) {
            $sql .= " AND p.usuario_id = :usuario_id";
            $parametros[':usuario_id'] = $usuarioId;
        }
        $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        $pagos = $stmt->fetchAll();

        // Un mismo conjunto de deudas conserva una sola tarjeta en la app.
        // Los registros antiguos duplicados quedan auditables en la BD y la vista usa el más reciente.
        $vistos = [];
        $pagos = array_values(array_filter($pagos, static function ($pago) use (&$vistos) {
            $detalle = json_decode($pago['detalle_pagos'] ?? '', true);
            if (!is_array($detalle) || !$detalle) return true;
            $claves = [];
            foreach ($detalle as $fila) {
                if (isset($fila['obligacion_id'])) $claves[] = 'o:' . (int)$fila['obligacion_id'];
                elseif (isset($fila['turno_id'])) $claves[] = 't:' . (int)$fila['turno_id'];
                else return true;
            }
            sort($claves, SORT_STRING);
            $clave = implode('|', $claves);
            if (isset($vistos[$clave])) return false;
            $vistos[$clave] = true;
            return true;
        }));

        $stmtFechas = $this->conexion->prepare(
            "SELECT fecha, disco FROM turno t
             INNER JOIN bus b ON t.bus_id = b.id
             WHERE t.pago_id = ?
             UNION
             SELECT fecha, disco FROM obligacion_pago WHERE pago_id = ?
             ORDER BY fecha"
        );
        foreach ($pagos as $i => $pago) {
            $detalle = $this->decodificarDetalle($pago['detalle_pagos'] ?? null);
            if ($detalle === null) {
                $stmtFechas->execute([$pago['id'], $pago['id']]);
                $filas = $stmtFechas->fetchAll();
                $pagos[$i]['fechas'] = array_map(static fn($fila) => $fila['fecha'], $filas);
                $pagos[$i]['discos'] = array_values(array_unique(array_map(static fn($fila) => $fila['disco'], $filas)));
            } else {
                $pagos[$i]['fechas'] = $detalle['fechas'];
                $pagos[$i]['discos'] = $detalle['discos'];
            }
            $pagos[$i]['dias'] = count($pagos[$i]['fechas']);
            $comprobantes = self::normalizarComprobantes($pago['comprobante'] ?? null);
            if (($pago['estado'] ?? '') === 'anulado') $comprobantes = [];
            $pagos[$i]['comprobantes'] = $comprobantes;
            $pagos[$i]['comprobante'] = $comprobantes[0] ?? null;
        }

        return $pagos;
    }

    public function obtenerTodosDiscos($q = '') {
        $sql = "SELECT DISTINCT discos.disco
                FROM (
                    SELECT IF(disco REGEXP '^[0-9]+$', LPAD(CAST(disco AS UNSIGNED), 2, '0'), disco) AS disco FROM bus WHERE activo = 1
                    UNION
                    SELECT IF(disco REGEXP '^[0-9]+$', LPAD(CAST(disco AS UNSIGNED), 2, '0'), disco) AS disco FROM obligacion_pago WHERE activo = 1
                ) discos
                WHERE 1 = 1";
        $parametros = [];
        $q = preg_replace('/\D+/', '', (string)$q);

        if ($q !== '') {
            $normalizado = ltrim($q, '0');
            $sql .= " AND CAST(discos.disco AS UNSIGNED) LIKE :q";
            $parametros[':q'] = ($normalizado === '' ? '0' : $normalizado) . '%';
        }

        $sql .= " ORDER BY CAST(discos.disco AS UNSIGNED), discos.disco";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        return array_map(static fn($fila) => $fila['disco'], $stmt->fetchAll());
    }

    public function obtenerObligacionesPendientes() {
        $sql = "SELECT id, disco, fecha, valor, ruta
                FROM obligacion_pago
                WHERE pagado = 0 AND pago_id IS NULL AND activo = 1 AND valor > 0
                ORDER BY fecha ASC, CAST(disco AS UNSIGNED), id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrarPagoObligaciones($usuarioId, array $obligacionesIds, $comprobante) {
        $obligacionesIds = array_values(array_unique(array_filter(array_map('intval', $obligacionesIds))));
        if (empty($obligacionesIds)) {
            return ['status' => 'sin_obligaciones'];
        }

        $manejaTransaccion = !$this->conexion->inTransaction();
        if ($manejaTransaccion) {
            $this->conexion->beginTransaction();
        }
        try {
            $placeholders = implode(',', array_fill(0, count($obligacionesIds), '?'));
            $stmt = $this->conexion->prepare(
                "SELECT id, valor, fecha, disco, ruta
                 FROM obligacion_pago
                 WHERE id IN ({$placeholders}) AND pagado = 0 AND pago_id IS NULL AND activo = 1 AND valor > 0
                 FOR UPDATE"
            );
            $stmt->execute($obligacionesIds);
            $obligaciones = $stmt->fetchAll();

            if (count($obligaciones) !== count($obligacionesIds)) {
                if ($manejaTransaccion) {
                    $this->conexion->rollBack();
                }
                return ['status' => 'obligaciones_invalidas'];
            }

            $montoTotal = array_reduce(
                $obligaciones,
                static fn($total, $obligacion) => $total + (float)$obligacion['valor'],
                0.0
            );

            $detallePagos = json_encode(
                array_map(
                    static fn($o) => [
                        'obligacion_id' => (int)$o['id'],
                        'fecha' => $o['fecha'],
                        'disco' => $o['disco'],
                        'ruta' => $o['ruta'] ?? null
                    ],
                    $obligaciones
                ),
                JSON_UNESCAPED_UNICODE
            );

            $comprobanteGuardado = self::serializarComprobantes($comprobante);

            $pagoId = $this->encontrarPagoAnuladoReutilizable($usuarioId, $obligacionesIds, 'obligacion_id');
            if ($pagoId) {
                $stmt = $this->conexion->prepare(
                    "UPDATE pago SET monto_total=:monto, fecha_pago=CURDATE(), comprobante=:comprobante,
                     estado='en_espera', motivo_rechazo=NULL, nro_comprobante=NULL, detalle_pagos=:detalle
                     WHERE id=:id"
                );
                $stmt->execute([':monto' => $montoTotal, ':comprobante' => $comprobanteGuardado, ':detalle' => $detallePagos, ':id' => $pagoId]);
            } else {
                $stmt = $this->conexion->prepare(
                    "INSERT INTO pago (usuario_id, monto_total, fecha_pago, comprobante, estado, detalle_pagos, activo)
                     VALUES (:usuario_id, :monto, CURDATE(), :comprobante, 'en_espera', :detalle, 1)"
                );
                $stmt->execute([':usuario_id' => $usuarioId, ':monto' => $montoTotal, ':comprobante' => $comprobanteGuardado, ':detalle' => $detallePagos]);
                $pagoId = (int)$this->conexion->lastInsertId();
            }

            $stmt = $this->conexion->prepare(
                "UPDATE obligacion_pago
                 SET pagado = 0, pago_id = :pago_id
                 WHERE id = :id AND pagado = 0 AND pago_id IS NULL"
            );
            foreach ($obligaciones as $obligacion) {
                $stmt->execute([':pago_id' => $pagoId, ':id' => $obligacion['id']]);
            }

            if ($manejaTransaccion) {
                $this->conexion->commit();
            }
            return [
                'status' => 'success',
                'pago_id' => $pagoId,
                'cantidad' => count($obligaciones),
                'monto' => $montoTotal
            ];
        } catch (Throwable $e) {
            if ($manejaTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['status' => 'error'];
        }
    }

    public function registrarPagoManual(array $datos, $comprobante) {
        $this->conexion->beginTransaction();
        try {
            $stmt = $this->conexion->prepare(
                "SELECT id FROM pago WHERE codigo_ingreso = :codigo AND activo = 1 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([':codigo' => $datos['codigo_ingreso']]);
            if ($stmt->fetch()) {
                throw new RuntimeException('codigo_duplicado');
            }

            $obligacionesIds = array_values(array_unique(array_filter(array_map('intval', $datos['obligaciones_ids'] ?? []))));
            if (empty($obligacionesIds)) {
                throw new RuntimeException('sin_obligaciones');
            }
            $placeholders = implode(',', array_fill(0, count($obligacionesIds), '?'));
            $stmt = $this->conexion->prepare(
                "SELECT id, valor, fecha, disco, ruta
                 FROM obligacion_pago
                 WHERE id IN ({$placeholders}) AND pagado = 0 AND pago_id IS NULL AND activo = 1 AND valor > 0
                 FOR UPDATE"
            );
            $stmt->execute($obligacionesIds);
            $obligaciones = $stmt->fetchAll();
            if (count($obligaciones) !== count($obligacionesIds)) {
                throw new RuntimeException('obligaciones_invalidas');
            }

            $discos = array_values(array_unique(array_map('intval', array_map(
                static fn($o) => (int)$o['disco'],
                $obligaciones
            ))));
            if (count($discos) !== 1) {
                throw new RuntimeException('obligaciones_invalidas');
            }
            $discosPlaceholders = implode(',', array_fill(0, count($discos), '?'));
            $stmt = $this->conexion->prepare(
                "SELECT DISTINCT u.id
                 FROM usuario u
                 INNER JOIN rol r ON u.rol_id = r.id AND r.nombre = 'conductor'
                 INNER JOIN usuario_bus ub ON ub.usuario_id = u.id AND ub.activo = 1
                 INNER JOIN bus b ON b.id = ub.bus_id
                 WHERE u.activo = 1 AND CAST(b.disco AS UNSIGNED) IN ({$discosPlaceholders})"
            );
            $stmt->execute($discos);
            $conductores = array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
            if (count($conductores) === 0) {
                throw new RuntimeException('conductor_no_encontrado');
            }
            if (count($conductores) > 1) {
                throw new RuntimeException('conductor_ambiguo');
            }
            $usuarioId = $conductores[0];

            $montoTotal = round(array_reduce(
                $obligaciones,
                static fn($total, $obligacion) => $total + (float)$obligacion['valor'],
                0.0
            ), 2);

            $detallePagos = json_encode(
                array_map(
                    static fn($o) => [
                        'obligacion_id' => (int)$o['id'],
                        'fecha' => $o['fecha'],
                        'disco' => $o['disco'],
                        'ruta' => $o['ruta'] ?? null
                    ],
                    $obligaciones
                ),
                JSON_UNESCAPED_UNICODE
            );

            $stmt = $this->conexion->prepare(
                "INSERT INTO pago (usuario_id, monto_total, fecha_pago, comprobante, estado, detalle_pagos, activo, tipo, codigo_ingreso)
                 VALUES (:usuario_id, :monto, CURDATE(), :comprobante, 'aprobado', :detalle, 1, 'manual', :codigo)"
            );
            $stmt->execute([
                ':usuario_id' => (int)$usuarioId,
                ':monto' => $montoTotal,
                ':comprobante' => self::serializarComprobantes($comprobante),
                ':detalle' => $detallePagos,
                ':codigo' => $datos['codigo_ingreso']
            ]);
            $pagoId = (int)$this->conexion->lastInsertId();

            $stmt = $this->conexion->prepare(
                "UPDATE obligacion_pago
                 SET pagado = 1, pago_id = :pago_id
                 WHERE id = :id AND pagado = 0 AND pago_id IS NULL"
            );
            foreach ($obligaciones as $obligacion) {
                $stmt->execute([':pago_id' => $pagoId, ':id' => $obligacion['id']]);
            }

            $this->conexion->commit();
            $fechas = array_values(array_unique(array_map(static fn($o) => $o['fecha'], $obligaciones)));
            $discosTexto = array_values(array_unique(array_map(static fn($o) => trim((string)$o['disco']), $obligaciones)));
            $rutas = array_values(array_filter(array_unique(array_map(
                static fn($o) => trim((string)$o['ruta']),
                $obligaciones
            )), static fn($r) => $r !== ''));
            return [
                'status' => 'success',
                'pago_id' => $pagoId,
                'monto' => $montoTotal,
                'disco' => $discosTexto[0] ?? '',
                'discos' => $discosTexto,
                'fechas' => $fechas,
                'rutas' => $rutas
            ];
        } catch (PDOException $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            return ['status' => (int)$e->getCode() === 23000 ? 'codigo_duplicado' : 'error'];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            return ['status' => $e instanceof RuntimeException ? $e->getMessage() : 'error'];
        }
    }

    public function obtenerPagosManuales($filtros = []) {
        $disco = trim((string)($filtros['disco'] ?? ''));
        $fechaDesde = (string)($filtros['fecha_desde'] ?? '');
        $fechaHasta = (string)($filtros['fecha_hasta'] ?? '');
        $ruta = trim((string)($filtros['ruta'] ?? ''));

        $sql = "SELECT p.id, p.usuario_id, p.monto_total, p.fecha_pago, p.comprobante, p.estado, p.motivo_rechazo,
                       p.codigo_ingreso, p.detalle_pagos
                FROM pago p
                WHERE p.activo = 1 AND p.tipo = 'manual'";
        $parametros = [];

        $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        $pagos = $stmt->fetchAll();

        $stmtDetalle = $this->conexion->prepare(
            "SELECT fecha, disco, ruta FROM turno t
             INNER JOIN bus b ON t.bus_id = b.id
             WHERE t.pago_id = ?
             UNION
             SELECT fecha, disco, ruta FROM obligacion_pago WHERE pago_id = ?
             ORDER BY fecha"
        );
        foreach ($pagos as $i => $pago) {
            $detalle = $this->decodificarDetalle($pago['detalle_pagos'] ?? null);
            if ($detalle === null) {
                $stmtDetalle->execute([$pago['id'], $pago['id']]);
                $filas = $stmtDetalle->fetchAll();
                $pagos[$i]['fechas'] = array_map(static fn($fila) => $fila['fecha'], $filas);
                $pagos[$i]['discos'] = array_values(array_unique(array_map(static fn($fila) => $fila['disco'], $filas)));
                $pagos[$i]['rutas'] = array_values(array_filter(array_unique(array_map(
                    static fn($fila) => trim((string)($fila['ruta'] ?? '')),
                    $filas
                )), static fn($ruta) => $ruta !== ''));
            } else {
                $pagos[$i]['fechas'] = $detalle['fechas'];
                $pagos[$i]['discos'] = $detalle['discos'];
                $pagos[$i]['rutas'] = $detalle['rutas'];
            }
            $pagos[$i]['dias'] = count($pagos[$i]['fechas']);
            $comprobantes = self::normalizarComprobantes($pago['comprobante'] ?? null);
            if (($pago['estado'] ?? '') === 'anulado') $comprobantes = [];
            $pagos[$i]['comprobantes'] = $comprobantes;
            $pagos[$i]['comprobante'] = $comprobantes[0] ?? null;
        }

        if ($disco === '' && $fechaDesde === '' && $fechaHasta === '' && $ruta === '') {
            return $pagos;
        }

        return array_values(array_filter(
            $pagos,
            fn($pago) => $this->pagoCoincideDetalle($pago, $disco, $fechaDesde, $fechaHasta, $ruta)
        ));
    }

    public function obtenerDiscosConductor($usuarioId, $q = '', $limite = 10) {
        $sql = "SELECT DISTINCT b.disco
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                WHERE t.usuario_id = :usuario_id
                  AND t.pagado = 0
                  AND t.valor > 0
                  AND t.fecha <= CURDATE()";
        $parametros = [':usuario_id' => $usuarioId];

        if ($q !== '') {
            $sql .= " AND (b.disco LIKE :q OR CAST(b.disco AS UNSIGNED) LIKE :q_normalizado)";
            $parametros[':q'] = $q . '%';
            $parametros[':q_normalizado'] = ltrim($q, '0') . '%';
        }

        $sql .= " ORDER BY CAST(b.disco AS UNSIGNED) ASC LIMIT :limite";
        $stmt = $this->conexion->prepare($sql);
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', max(1, (int)$limite), PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($fila) => $fila['disco'], $stmt->fetchAll());
    }

    public function registrarPago($usuarioId, array $turnosIds, $comprobante) {
        $turnosIds = array_values(array_unique(array_filter(array_map('intval', $turnosIds))));
        if (empty($turnosIds)) {
            return ['status' => 'sin_turnos'];
        }

        $this->conexion->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($turnosIds), '?'));
            $sql = "SELECT t.id, t.valor, t.fecha, t.ruta, b.disco
                    FROM turno t
                    INNER JOIN bus b ON t.bus_id = b.id
                    WHERE t.id IN ({$placeholders})
                      AND t.usuario_id = ?
                      AND t.pagado = 0
                      AND t.valor > 0
                    FOR UPDATE";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(array_merge($turnosIds, [(int)$usuarioId]));
            $turnos = $stmt->fetchAll();

            if (count($turnos) !== count($turnosIds)) {
                $this->conexion->rollBack();
                return ['status' => 'turnos_invalidos'];
            }

            $montoTotal = array_reduce(
                $turnos,
                fn($total, $turno) => $total + (float)$turno['valor'],
                0.0
            );

            $detallePagos = json_encode(
                array_map(
                    static fn($t) => [
                        'turno_id' => (int)$t['id'],
                        'fecha' => $t['fecha'],
                        'disco' => $t['disco'],
                        'ruta' => $t['ruta'] ?? null
                    ],
                    $turnos
                ),
                JSON_UNESCAPED_UNICODE
            );

            $comprobanteGuardado = self::serializarComprobantes($comprobante);

            $pagoId = $this->encontrarPagoAnuladoReutilizable($usuarioId, $turnosIds, 'turno_id');
            if ($pagoId) {
                $stmt = $this->conexion->prepare(
                    "UPDATE pago SET monto_total=:monto, fecha_pago=CURDATE(), comprobante=:comprobante,
                     estado='en_espera', motivo_rechazo=NULL, nro_comprobante=NULL, detalle_pagos=:detalle
                     WHERE id=:id"
                );
                $stmt->execute([':monto' => $montoTotal, ':comprobante' => $comprobanteGuardado, ':detalle' => $detallePagos, ':id' => $pagoId]);
            } else {
                $stmt = $this->conexion->prepare(
                    "INSERT INTO pago (usuario_id, monto_total, fecha_pago, comprobante, estado, detalle_pagos, activo)
                     VALUES (:usuario_id, :monto, CURDATE(), :comprobante, 'en_espera', :detalle, 1)"
                );
                $stmt->execute([':usuario_id' => $usuarioId, ':monto' => $montoTotal, ':comprobante' => $comprobanteGuardado, ':detalle' => $detallePagos]);
                $pagoId = (int)$this->conexion->lastInsertId();
            }

            $stmtActualizar = $this->conexion->prepare(
                "UPDATE turno
                 SET pagado = 1, pago_id = :pago_id
                 WHERE id = :turno_id AND usuario_id = :usuario_id AND pagado = 0"
            );
            foreach ($turnos as $turno) {
                $stmtActualizar->execute([
                    ':pago_id' => $pagoId,
                    ':turno_id' => $turno['id'],
                    ':usuario_id' => $usuarioId
                ]);
            }

            $this->conexion->commit();
            return [
                'status' => 'success',
                'pago_id' => $pagoId,
                'cantidad' => count($turnos),
                'monto' => $montoTotal
            ];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['status' => 'error'];
        }
    }

    public function obtenerPagosParaAdmin($filtros = []) {
        $conductor = trim((string)($filtros['conductor'] ?? ''));
        $disco = trim((string)($filtros['disco'] ?? ''));
        $fechaDesde = (string)($filtros['fecha_desde'] ?? '');
        $fechaHasta = (string)($filtros['fecha_hasta'] ?? '');
        $ruta = trim((string)($filtros['ruta'] ?? ''));
        $estado = (string)($filtros['estado'] ?? '');
        if (!in_array($estado, ['en_espera', 'aprobado', 'anulado', 'incompleto'], true)) {
            $estado = '';
        }

        $sql = "SELECT p.id, p.usuario_id, u.nombres, u.apellidos, u.codigo_conductor,
                       p.monto_total, p.fecha_pago, p.comprobante, p.estado, p.motivo_rechazo,
                       p.nro_comprobante, p.detalle_pagos
                FROM pago p
                INNER JOIN usuario u ON u.id = p.usuario_id
                WHERE p.activo = 1 AND (p.tipo IS NULL OR p.tipo <> 'manual')";
        $parametros = [];

        if ($conductor !== '') {
            $sql .= " AND (CONCAT(u.nombres, ' ', u.apellidos) LIKE :conductor_nombre
                           OR u.codigo_conductor LIKE :conductor_codigo)";
            $parametros[':conductor_nombre'] = '%' . $conductor . '%';
            $parametros[':conductor_codigo'] = '%' . $conductor . '%';
        }
        if ($estado !== '') {
            $sql .= " AND p.estado = :estado";
            $parametros[':estado'] = $estado;
        }
        $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        $pagos = $stmt->fetchAll();

        $stmtDetalle = $this->conexion->prepare(
            "SELECT fecha, disco, ruta FROM turno t
             INNER JOIN bus b ON t.bus_id = b.id
             WHERE t.pago_id = ?
             UNION
             SELECT fecha, disco, ruta FROM obligacion_pago WHERE pago_id = ?
             ORDER BY fecha"
        );
        foreach ($pagos as $i => $pago) {
            $detalle = $this->decodificarDetalle($pago['detalle_pagos'] ?? null);
            if ($detalle === null) {
                $stmtDetalle->execute([$pago['id'], $pago['id']]);
                $filas = $stmtDetalle->fetchAll();
                $pagos[$i]['fechas'] = array_map(static fn($fila) => $fila['fecha'], $filas);
                $pagos[$i]['discos'] = array_values(array_unique(array_map(static fn($fila) => $fila['disco'], $filas)));
                $pagos[$i]['rutas'] = array_values(array_filter(array_unique(array_map(
                    static fn($fila) => trim((string)($fila['ruta'] ?? '')),
                    $filas
                )), static fn($ruta) => $ruta !== ''));
            } else {
                $pagos[$i]['fechas'] = $detalle['fechas'];
                $pagos[$i]['discos'] = $detalle['discos'];
                $pagos[$i]['rutas'] = $detalle['rutas'];
            }
            $pagos[$i]['dias'] = count($pagos[$i]['fechas']);
            $comprobantes = self::normalizarComprobantes($pago['comprobante'] ?? null);
            if (($pago['estado'] ?? '') === 'anulado') $comprobantes = [];
            $pagos[$i]['comprobantes'] = $comprobantes;
            $pagos[$i]['comprobante'] = $comprobantes[0] ?? null;
        }

        if ($disco === '' && $fechaDesde === '' && $fechaHasta === '' && $ruta === '') {
            return $pagos;
        }

        return array_values(array_filter(
            $pagos,
            fn($pago) => $this->pagoCoincideDetalle($pago, $disco, $fechaDesde, $fechaHasta, $ruta)
        ));
    }

    private function normalizarDiscoDetalle($disco) {
        $disco = ltrim((string)$disco, '0');
        return $disco === '' ? '0' : $disco;
    }

    private function pagoCoincideDetalle($pago, $disco, $fechaDesde, $fechaHasta, $ruta) {
        if ($disco !== '') {
            $discoNormalizado = $this->normalizarDiscoDetalle($disco);
            $coincide = false;
            foreach ($pago['discos'] ?? [] as $discoPago) {
                if ($this->normalizarDiscoDetalle($discoPago) === $discoNormalizado) {
                    $coincide = true;
                    break;
                }
            }
            if (!$coincide) return false;
        }

        if ($fechaDesde !== '' || $fechaHasta !== '') {
            $coincide = false;
            foreach ($pago['fechas'] ?? [] as $fechaPago) {
                $fecha = (string)$fechaPago;
                $dentroRango = true;
                if ($fechaDesde !== '' && $fecha < $fechaDesde) $dentroRango = false;
                if ($fechaHasta !== '' && $fecha > $fechaHasta) $dentroRango = false;
                if ($dentroRango) {
                    $coincide = true;
                    break;
                }
            }
            if (!$coincide) return false;
        }

        if ($ruta !== '') {
            $rutaMinuscula = mb_strtolower($ruta);
            $coincide = false;
            foreach ($pago['rutas'] ?? [] as $rutaPago) {
                if (mb_strpos(mb_strtolower((string)$rutaPago), $rutaMinuscula) !== false) {
                    $coincide = true;
                    break;
                }
            }
            if (!$coincide) return false;
        }

        return true;
    }

    public function obtenerEstadoPago($pagoId) {
        $stmt = $this->conexion->prepare("SELECT estado, activo FROM pago WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$pagoId]);
        return $stmt->fetch();
    }

    private function decodificarDetalle($detallePagos) {
        if (!$detallePagos) {
            return null;
        }
        $filas = json_decode($detallePagos, true);
        if (!is_array($filas) || empty($filas)) {
            return null;
        }
        return [
            'fechas' => array_map(static fn($fila) => $fila['fecha'] ?? null, $filas),
            'discos' => array_values(array_unique(array_filter(array_map(
                static fn($fila) => trim((string)($fila['disco'] ?? '')),
                $filas
            )))),
            'rutas' => array_values(array_filter(array_unique(array_map(
                static fn($fila) => trim((string)($fila['ruta'] ?? '')),
                $filas
            )), static fn($ruta) => $ruta !== ''))
        ];
    }

    /** Analiza el número de comprobante almacenado (cadena " | " o JSON array). */
    private static function analizarNro($valor): array {
        $valor = trim((string)$valor);
        if ($valor === '') return [];
        $decodificado = json_decode($valor, true);
        if (is_array($decodificado)) {
            return array_values(array_filter(array_map('strval', $decodificado), static fn($c) => trim($c) !== ''));
        }
        return array_values(array_filter(preg_split('/\s*\|\s*/', $valor), static fn($c) => trim($c) !== ''));
    }

    /** Devuelve el primer número de comprobante que ya existe en otro pago activo, o null. */
    private function codigoEnOtroPago(array $codigos, $pagoId) {
        $stmt = $this->conexion->prepare(
            "SELECT nro_comprobante FROM pago
             WHERE activo = 1 AND nro_comprobante IS NOT NULL AND nro_comprobante <> '' AND id <> ?"
        );
        $stmt->execute([(int)$pagoId]);
        foreach ($stmt->fetchAll() as $fila) {
            $existentes = self::analizarNro($fila['nro_comprobante']);
            foreach ($codigos as $codigo) {
                if (in_array($codigo, $existentes, true)) {
                    $this->ultimoCodigoDuplicado = $codigo;
                    return $codigo;
                }
            }
        }
        return null;
    }

    /** Cuenta los comprobantes (archivos) subidos a un pago activo. */
    public function contarComprobantesPago($pagoId) {
        $stmt = $this->conexion->prepare("SELECT comprobante FROM pago WHERE id = ? AND activo = 1");
        $stmt->execute([(int)$pagoId]);
        $fila = $stmt->fetch();
        if (!$fila) return 0;
        return count(self::normalizarComprobantes($fila['comprobante'] ?? null));
    }

    /** Devuelve el último número detectado como duplicado tras una validación fallida. */
    public function obtenerUltimoCodigoDuplicado() {
        return $this->ultimoCodigoDuplicado;
    }

    public function guardarNroComprobantes($pagoId, array $codigos) {
        $entrados = array_values(array_filter(
            array_map(static fn($c) => trim((string)$c), $codigos),
            static fn($c) => $c !== ''
        ));
        if (!$entrados) return ['status' => 'comprobante_obligatorio'];
        foreach ($entrados as $codigo) {
            if (!preg_match('/^[0-9]+$/D', $codigo)) return ['status' => 'comprobante_invalido'];
        }

        $vistos = [];
        foreach ($entrados as $codigo) {
            if (isset($vistos[$codigo])) {
                $this->ultimoCodigoDuplicado = $codigo;
                return ['status' => 'codigo_duplicado', 'codigo' => $codigo];
            }
            $vistos[$codigo] = true;
        }

        $stock = $this->conexion->prepare("SELECT comprobante FROM pago WHERE id = ? AND activo = 1");
        $stock->execute([(int)$pagoId]);
        $pago = $stock->fetch();
        if (!$pago) return ['status' => 'no_encontrado'];
        $cantidadArchivos = count(self::normalizarComprobantes($pago['comprobante'] ?? null));
        if ($cantidadArchivos === 0) return ['status' => 'sin_comprobantes'];
        if (count($entrados) !== $cantidadArchivos) {
            return ['status' => 'cantidad_invalida', 'esperado' => $cantidadArchivos];
        }

        $duplicado = $this->codigoEnOtroPago($entrados, (int)$pagoId);
        if ($duplicado !== null) return ['status' => 'codigo_duplicado_sistema', 'codigo' => $duplicado];

        $guardado = implode(' | ', array_slice($entrados, 0, 20));
        if (mb_strlen($guardado) > 255) return ['status' => 'muy_largo'];

        try {
            $stmt = $this->conexion->prepare(
                "UPDATE pago SET nro_comprobante = :codigos WHERE id = :pago_id AND activo = 1"
            );
            $stmt->execute([':codigos' => $guardado, ':pago_id' => (int)$pagoId]);
            return ['status' => 'success', 'codigos' => $guardado];
        } catch (Throwable $e) {
            return ['status' => 'error'];
        }
    }

    public function adjuntarComprobantePago($pagoId, $usuarioId, $comprobante) {
        $this->conexion->beginTransaction();
        try {
            $stmt = $this->conexion->prepare(
                "SELECT usuario_id, estado, comprobante FROM pago WHERE id = ? AND activo = 1 FOR UPDATE"
            );
            $stmt->execute([(int)$pagoId]);
            $pago = $stmt->fetch();
            if (!$pago) throw new RuntimeException('no_encontrado');
            if ((int)$pago['usuario_id'] !== (int)$usuarioId) throw new RuntimeException('no_autorizado');
            if (($pago['estado'] ?? '') !== 'incompleto') throw new RuntimeException('estado_invalido');

            $comprobantes = self::normalizarComprobantes($pago['comprobante'] ?? null);
            $comprobantes[] = (string)$comprobante;
            $comprobantes = array_values(array_unique(array_filter($comprobantes, static fn($r) => trim($r) !== '')));

            $stmt = $this->conexion->prepare(
                "UPDATE pago SET comprobante = :comprobante, estado = 'en_espera', motivo_rechazo = NULL
                 WHERE id = :id"
            );
            $stmt->execute([':comprobante' => self::serializarComprobantes($comprobantes), ':id' => (int)$pagoId]);
            $this->conexion->commit();
            return ['status' => 'success', 'pago_id' => (int)$pagoId, 'comprobantes' => $comprobantes];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            return ['status' => $e instanceof RuntimeException ? $e->getMessage() : 'error'];
        }
    }

    public function actualizarEstadoPago($pagoId, $estado, $motivo = null, $codigos = null) {
        if (!in_array($estado, ['en_espera', 'aprobado', 'anulado', 'incompleto'], true)) return ['status' => 'estado_invalido'];
        $propia = !$this->conexion->inTransaction();
        if ($propia) $this->conexion->beginTransaction();
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM pago WHERE id = ? AND activo = 1 FOR UPDATE");
            $stmt->execute([(int)$pagoId]);
            $pago = $stmt->fetch();
            if (!$pago) throw new RuntimeException('no_encontrado');
            $numero = null;
            if ($estado === 'aprobado' && is_array($codigos)) {
                $lista = array_values(array_filter(
                    array_map(static fn($c) => trim((string)$c), $codigos),
                    static fn($c) => $c !== ''
                ));
                if (!$lista) throw new RuntimeException('comprobante_obligatorio');
                foreach ($lista as $codigo) {
                    if (!preg_match('/^[0-9]+$/D', $codigo)) throw new RuntimeException('comprobante_invalido');
                }
                $vistos = [];
                foreach ($lista as $codigo) {
                    if (isset($vistos[$codigo])) {
                        $this->ultimoCodigoDuplicado = $codigo;
                        throw new RuntimeException('codigo_duplicado');
                    }
                    $vistos[$codigo] = true;
                }
                $numero = implode(' | ', $lista);
            } else {
                $numero = trim($codigos ?? $pago['nro_comprobante'] ?? '');
            }
            if ($estado === 'aprobado' && !preg_match('/[^\s|]/u', $numero)) throw new RuntimeException('comprobante_obligatorio');
            if ($estado === 'aprobado' && !preg_match('/^[0-9]+(?: \| [0-9]+)*$/D', $numero)) throw new RuntimeException('comprobante_invalido');
            if ($estado === 'aprobado' && !is_array($codigos)) {
                $lista = self::analizarNro($numero);
                $vistos = [];
                foreach ($lista as $codigo) {
                    if (isset($vistos[$codigo])) {
                        $this->ultimoCodigoDuplicado = $codigo;
                        throw new RuntimeException('codigo_duplicado');
                    }
                    $vistos[$codigo] = true;
                }
            }
            if (mb_strlen($numero) > 255) throw new RuntimeException('muy_largo');
            if ($estado === 'aprobado') {
                $cantidadArchivos = count(self::normalizarComprobantes($pago['comprobante'] ?? null));
                if ($cantidadArchivos === 0) throw new RuntimeException('sin_comprobantes');
                $numerosValidados = self::analizarNro($numero);
                if (count($numerosValidados) !== $cantidadArchivos) throw new RuntimeException('cantidad_invalida');
                $duplicado = $this->codigoEnOtroPago($numerosValidados, (int)$pagoId);
                if ($duplicado !== null) throw new RuntimeException('codigo_duplicado_sistema');
            }
            if ($estado === 'anulado' || $estado === 'incompleto') {
                if (trim($motivo ?? '') === '') throw new RuntimeException('motivo_obligatorio');
            }

            $detalle = json_decode($pago['detalle_pagos'] ?? '', true) ?: [];
            if ($estado === 'anulado' && $pago['estado'] !== 'anulado') {
                // Conservar los IDs antes de liberar las deudas para poder corregir una anulación.
                $detalle = [];
                foreach (['obligacion_pago' => 'obligacion_id', 'turno' => 'turno_id'] as $tabla => $clave) {
                    $sql = $tabla === 'turno'
                        ? "SELECT t.id, t.fecha, b.disco, t.ruta FROM turno t JOIN bus b ON b.id = t.bus_id WHERE t.pago_id = ? FOR UPDATE"
                        : "SELECT id, fecha, disco, ruta FROM obligacion_pago WHERE pago_id = ? FOR UPDATE";
                    $stmt = $this->conexion->prepare($sql);
                    $stmt->execute([(int)$pagoId]);
                    foreach ($stmt->fetchAll() as $fila) {
                        $detalle[] = [$clave => (int)$fila['id'], 'fecha' => $fila['fecha'], 'disco' => $fila['disco'], 'ruta' => $fila['ruta']];
                    }
                }
                if (!$detalle) $detalle = json_decode($pago['detalle_pagos'] ?? '', true) ?: [];
            }
            if ($pago['estado'] === 'anulado' && $estado !== 'anulado') {
                if (!$detalle) throw new RuntimeException('detalle_no_disponible');
                foreach ($detalle as $fila) {
                    $candidatos = [];
                    foreach (['obligacion_pago' => 'obligacion_id', 'turno' => 'turno_id'] as $tabla => $clave) {
                        if (isset($fila['obligacion_id']) || isset($fila['turno_id'])) {
                            if (!isset($fila[$clave])) continue;
                            $stmt = $this->conexion->prepare("SELECT id, pago_id, pagado FROM $tabla WHERE id = ? FOR UPDATE");
                            $stmt->execute([(int)$fila[$clave]]);
                        } else {
                            // Compatibilidad con comprobantes anteriores que solo guardaban disco y fecha.
                            $sql = $tabla === 'turno'
                                ? "SELECT t.id, t.pago_id, t.pagado FROM turno t JOIN bus b ON b.id=t.bus_id WHERE b.disco=? AND t.fecha=? AND t.usuario_id=" . (int)$pago['usuario_id'] . " FOR UPDATE"
                                : "SELECT id, pago_id, pagado FROM obligacion_pago WHERE disco=? AND fecha=? AND activo=1 FOR UPDATE";
                            $stmt = $this->conexion->prepare($sql);
                            $stmt->execute([$fila['disco'], $fila['fecha']]);
                        }
                        foreach ($stmt->fetchAll() as $deuda) $candidatos[] = [$tabla, $deuda];
                    }
                    if (count($candidatos) !== 1) throw new RuntimeException('detalle_no_disponible');
                    [$tabla, $deuda] = $candidatos[0];
                    if (($deuda['pago_id'] !== null && (int)$deuda['pago_id'] !== (int)$pagoId) || ((int)$deuda['pagado'] === 1 && (int)$deuda['pago_id'] !== (int)$pagoId)) {
                        throw new RuntimeException('deuda_pagada');
                    }
                    $stmt = $this->conexion->prepare("UPDATE $tabla SET pagado=0, pago_id=? WHERE id=?");
                    $stmt->execute([(int)$pagoId, $deuda['id']]);
                }
            }
            $stmt = $this->conexion->prepare("UPDATE pago SET estado=?, motivo_rechazo=?, nro_comprobante=?, detalle_pagos=? WHERE id=?");
            $stmt->execute([$estado, in_array($estado, ['anulado', 'incompleto'], true) ? $motivo : null, $numero ?: null, $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : $pago['detalle_pagos'], (int)$pagoId]);
            if ($estado === 'anulado') {
                foreach (['obligacion_pago', 'turno'] as $tabla) {
                    $stmt = $this->conexion->prepare("UPDATE $tabla SET pagado=0, pago_id=NULL WHERE pago_id=?");
                    $stmt->execute([(int)$pagoId]);
                }
            } else {
                $pagado = $estado === 'aprobado' ? 1 : 0;
                foreach (['obligacion_pago', 'turno'] as $tabla) {
                    $stmt = $this->conexion->prepare("UPDATE $tabla SET pagado=? WHERE pago_id=?");
                    $stmt->execute([$pagado, (int)$pagoId]);
                }
            }
            if ($propia) $this->conexion->commit();
            return ['status' => 'success'];
        } catch (Throwable $e) {
            if ($propia && $this->conexion->inTransaction()) $this->conexion->rollBack();
            return ['status' => $e instanceof RuntimeException ? $e->getMessage() : 'error'];
        }
    }
}
