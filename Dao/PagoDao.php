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
                ORDER BY t.fecha ASC, t.hora_apertura ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function obtenerPagosConductor($usuarioId) {
        $sql = "SELECT p.id, p.monto_total AS monto, p.fecha_pago, p.comprobante,
                       COUNT(t.id) AS dias
                FROM pago p
                LEFT JOIN turno t ON t.pago_id = p.id
                WHERE p.usuario_id = :usuario_id AND p.activo = 1
                GROUP BY p.id, p.monto_total, p.fecha_pago, p.comprobante
                ORDER BY p.fecha_pago DESC, p.id DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function registrarPago($usuarioId, array $turnosIds, $comprobante) {
        $turnosIds = array_values(array_unique(array_filter(array_map('intval', $turnosIds))));
        if (empty($turnosIds)) {
            return ['status' => 'sin_turnos'];
        }

        $this->conexion->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($turnosIds), '?'));
            $sql = "SELECT id, valor
                    FROM turno
                    WHERE id IN ({$placeholders})
                      AND usuario_id = ?
                      AND pagado = 0
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

            $stmt = $this->conexion->prepare(
                "INSERT INTO pago (usuario_id, monto_total, fecha_pago, comprobante, activo)
                 VALUES (:usuario_id, :monto, CURDATE(), :comprobante, 1)"
            );
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':monto' => $montoTotal,
                ':comprobante' => $comprobante
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
}
