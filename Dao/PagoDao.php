<?php

class PagoDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
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
        $sql = "SELECT p.id, p.monto_total AS monto, p.fecha_pago, p.comprobante, p.estado,
                       p.motivo_rechazo, p.detalle_pagos
                FROM pago p
                WHERE p.usuario_id = :usuario_id AND p.activo = 1
                ORDER BY p.fecha_pago DESC, p.id DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $pagos = $stmt->fetchAll();

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
        }

        return $pagos;
    }

    public function obtenerTodosDiscos($q = '') {
        $sql = "SELECT DISTINCT discos.disco
                FROM (
                    SELECT disco FROM bus WHERE activo = 1
                    UNION
                    SELECT disco FROM obligacion_pago WHERE activo = 1
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
                WHERE pagado = 0 AND activo = 1 AND valor > 0
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
                 WHERE id IN ({$placeholders}) AND pagado = 0 AND activo = 1 AND valor > 0
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
                        'fecha' => $o['fecha'],
                        'disco' => $o['disco'],
                        'ruta' => $o['ruta'] ?? null
                    ],
                    $obligaciones
                ),
                JSON_UNESCAPED_UNICODE
            );

            $stmt = $this->conexion->prepare(
                "INSERT INTO pago (usuario_id, monto_total, fecha_pago, comprobante, estado, detalle_pagos, activo)
                 VALUES (:usuario_id, :monto, CURDATE(), :comprobante, 'en_espera', :detalle, 1)"
            );
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':monto' => $montoTotal,
                ':comprobante' => $comprobante,
                ':detalle' => $detallePagos
            ]);
            $pagoId = $this->conexion->lastInsertId();

            $stmt = $this->conexion->prepare(
                "UPDATE obligacion_pago
                 SET pagado = 1, pago_id = :pago_id
                 WHERE id = :id AND pagado = 0"
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
                        'fecha' => $t['fecha'],
                        'disco' => $t['disco'],
                        'ruta' => $t['ruta'] ?? null
                    ],
                    $turnos
                ),
                JSON_UNESCAPED_UNICODE
            );

            $stmt = $this->conexion->prepare(
                "INSERT INTO pago (usuario_id, monto_total, fecha_pago, comprobante, estado, detalle_pagos, activo)
                 VALUES (:usuario_id, :monto, CURDATE(), :comprobante, 'en_espera', :detalle, 1)"
            );
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':monto' => $montoTotal,
                ':comprobante' => $comprobante,
                ':detalle' => $detallePagos
            ]);
            $pagoId = $this->conexion->lastInsertId();

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

    public function obtenerPagosParaAdmin() {
        $sql = "SELECT p.id, p.usuario_id, u.nombres, u.apellidos, u.codigo_conductor,
                       p.monto_total, p.fecha_pago, p.comprobante, p.estado, p.motivo_rechazo,
                       p.nro_comprobante, p.detalle_pagos
                FROM pago p
                INNER JOIN usuario u ON u.id = p.usuario_id
                WHERE p.activo = 1
                ORDER BY p.fecha_pago DESC, p.id DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
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
        }

        return $pagos;
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

    public function guardarNroComprobantes($pagoId, array $codigos) {
        $codigosLimpios = [];
        foreach ($codigos as $codigo) {
            $codigo = trim((string)$codigo);
            $codigo = preg_replace('/\s*\|\s*/', ' | ', $codigo);
            $codigo = trim($codigo);
            if ($codigo !== '') {
                $codigosLimpios[] = $codigo;
            }
        }
        if (count($codigosLimpios) > 0) {
            $codigosLimpios = array_values(array_unique($codigosLimpios));
        }

        $guardado = null;
        if (!empty($codigosLimpios)) {
            $guardado = implode(' | ', array_slice($codigosLimpios, 0, 20));
        }
        if (mb_strlen($guardado ?? '') > 255) {
            return ['status' => 'muy_largo'];
        }

        try {
            $stmt = $this->conexion->prepare(
                "UPDATE pago SET nro_comprobante = :codigos WHERE id = :pago_id AND activo = 1"
            );
            $stmt->execute([':codigos' => $guardado, ':pago_id' => (int)$pagoId]);
            if ($stmt->rowCount() === 0 && $guardado == null) {
                return ['status' => 'no_encontrado'];
            }
            return ['status' => 'success', 'codigos' => $guardado];
        } catch (Throwable $e) {
            return ['status' => 'error'];
        }
    }

    public function actualizarEstadoPago($pagoId, $estado, $motivo = null) {
        if (!in_array($estado, ['en_espera', 'aprobado', 'anulado'], true)) {
            return ['status' => 'estado_invalido'];
        }

        $manejaTransaccion = !$this->conexion->inTransaction();
        if ($manejaTransaccion) {
            $this->conexion->beginTransaction();
        }
        try {
            $stmt = $this->conexion->prepare(
                "UPDATE pago
                 SET estado = :estado, motivo_rechazo = :motivo
                 WHERE id = :pago_id AND activo = 1"
            );
            $stmt->execute([
                ':estado' => $estado,
                ':motivo' => $estado === 'anulado' ? $motivo : null,
                ':pago_id' => (int)$pagoId
            ]);

            if ($stmt->rowCount() === 0) {
                if ($manejaTransaccion) {
                    $this->conexion->rollBack();
                }
                return ['status' => 'no_encontrado'];
            }

            if ($estado === 'anulado') {
                $stmt = $this->conexion->prepare(
                    "UPDATE obligacion_pago SET pagado = 0, pago_id = NULL WHERE pago_id = :pago_id"
                );
                $stmt->execute([':pago_id' => (int)$pagoId]);
                $stmt = $this->conexion->prepare(
                    "UPDATE turno SET pagado = 0, pago_id = NULL WHERE pago_id = :pago_id"
                );
                $stmt->execute([':pago_id' => (int)$pagoId]);
            }

            if ($manejaTransaccion) {
                $this->conexion->commit();
            }
            return ['status' => 'success'];
        } catch (Throwable $e) {
            if ($manejaTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['status' => 'error'];
        }
    }
}