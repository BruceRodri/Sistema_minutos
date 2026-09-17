<?php

class ReporteDiferenciaDao {
    public function __construct(private PDO $conexion) {
        $this->conexion->exec(
            "CREATE TABLE IF NOT EXISTS reporte_diferencia_pago (
                id INT NOT NULL AUTO_INCREMENT,
                pago_id INT NOT NULL,
                usuario_id INT NOT NULL,
                monto_depositado DECIMAL(10,2) NOT NULL,
                saldo_favor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                estado ENUM('pendiente','confirmado','rechazado') NOT NULL DEFAULT 'pendiente',
                nota_admin VARCHAR(255) DEFAULT NULL,
                revisado_por INT DEFAULT NULL,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_reporte_diferencia_pago (pago_id),
                KEY idx_reporte_diferencia_usuario (usuario_id, estado),
                CONSTRAINT fk_reporte_diferencia_pago FOREIGN KEY (pago_id) REFERENCES pago(id),
                CONSTRAINT fk_reporte_diferencia_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id),
                CONSTRAINT fk_reporte_diferencia_revisor FOREIGN KEY (revisado_por) REFERENCES usuario(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function decorarPagosUsuario(array $pagos, int $usuarioId): array {
        require_once __DIR__ . '/SaldoPagoDao.php';
        new SaldoPagoDao($this->conexion);
        $idsPropios = [];
        foreach ($pagos as &$pago) {
            $pago['es_propio'] = (int)($pago['usuario_id'] ?? 0) === $usuarioId;
            $pago['reporte_diferencia'] = null;
            if ($pago['es_propio']) $idsPropios[] = (int)$pago['id'];
            unset($pago['usuario_id']);
        }
        unset($pago);
        if (!$idsPropios) return $pagos;
        $marcadores = implode(',', array_fill(0, count($idsPropios), '?'));
        $stmt = $this->conexion->prepare("SELECT pago_id, monto_depositado, saldo_favor, estado, nota_admin, creado_en FROM reporte_diferencia_pago WHERE usuario_id=? AND pago_id IN ($marcadores)");
        $stmt->execute(array_merge([$usuarioId], $idsPropios));
        $reportes = [];
        foreach ($stmt->fetchAll() as $fila) $reportes[(int)$fila['pago_id']] = $fila;
        foreach ($pagos as &$pago) {
            $pago['reporte_diferencia'] = $reportes[(int)$pago['id']] ?? null;
            $pago['comprobantes_saldo'] = $pago['es_propio'] && $pago['estado'] !== 'anulado'
                ? $this->obtenerComprobantesSaldo((int)$pago['id']) : [];
        }
        unset($pago);
        return $pagos;
    }

    public function decorarPagosAdmin(array $pagos): array {
        require_once __DIR__ . '/SaldoPagoDao.php';
        new SaldoPagoDao($this->conexion);
        if (!$pagos) return $pagos;
        $ids = array_map(static fn($p) => (int)$p['id'], $pagos);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conexion->prepare("SELECT pago_id, monto_depositado, saldo_favor, estado, nota_admin, creado_en FROM reporte_diferencia_pago WHERE pago_id IN ($marcadores)");
        $stmt->execute($ids);
        $reportes = [];
        foreach ($stmt->fetchAll() as $fila) $reportes[(int)$fila['pago_id']] = $fila;
        foreach ($pagos as &$pago) {
            $pago['reporte_diferencia'] = $reportes[(int)$pago['id']] ?? null;
            $q = $this->conexion->prepare('SELECT u.monto, r.pago_id AS origen FROM uso_saldo_pago u JOIN reporte_diferencia_pago r ON r.id=u.reporte_id WHERE u.pago_id=? AND u.devuelto=0');
            $q->execute([$pago['id']]);
            $pago['usos_saldo'] = $q->fetchAll();
            $pago['comprobantes_saldo'] = $pago['estado'] !== 'anulado'
                ? $this->obtenerComprobantesSaldo((int)$pago['id']) : [];
            $q = $this->conexion->prepare('SELECT u.monto, u.pago_id FROM uso_saldo_pago u JOIN reporte_diferencia_pago r ON r.id=u.reporte_id WHERE r.pago_id=? AND u.devuelto=0');
            $q->execute([$pago['id']]);
            $pago['destinos_saldo'] = $q->fetchAll();
        }
        unset($pago);
        return $pagos;
    }

    private function obtenerComprobantesSaldo(int $pagoId): array {
        // Referencias de respaldo: no son depósitos nuevos ni requieren validar otra vez sus códigos.
        $q = $this->conexion->prepare("SELECT p.id, p.comprobante, u.monto
            FROM uso_saldo_pago u
            JOIN reporte_diferencia_pago r ON r.id=u.reporte_id
            JOIN pago p ON p.id=r.pago_id
            WHERE u.pago_id=? AND u.devuelto=0 AND p.id<>u.pago_id
                AND p.activo=1 AND p.estado<>'anulado'
            ORDER BY p.id, u.id");
        $q->execute([$pagoId]);
        $comprobantes = [];
        foreach ($q->fetchAll() as $origen) {
            foreach (PagoDao::normalizarComprobantes($origen['comprobante'] ?? null) as $ruta) {
                $archivo = basename($ruta);
                $clave = (int)$origen['id'] . ':' . $archivo;
                $comprobantes[$clave] = [
                    'pago_id' => (int)$origen['id'],
                    'archivo' => $archivo,
                    'saldo_aplicado' => number_format((float)$origen['monto'], 2, '.', '')
                ];
            }
        }
        return array_values($comprobantes);
    }

    public function reportar(int $pagoId, int $usuarioId, float $montoDepositado): array {
        $stmt = $this->conexion->prepare("SELECT monto_total, estado FROM pago WHERE id=? AND usuario_id=? AND activo=1");
        $stmt->execute([$pagoId, $usuarioId]);
        $pago = $stmt->fetch();
        if (!$pago || ($pago['estado'] ?? '') === 'anulado') return ['status' => 'no_disponible'];
        if ($montoDepositado <= (float)$pago['monto_total']) return ['status' => 'sin_excedente'];
        try {
            $stmt = $this->conexion->prepare("INSERT INTO reporte_diferencia_pago (pago_id, usuario_id, monto_depositado) VALUES (?, ?, ?)");
            $stmt->execute([$pagoId, $usuarioId, number_format($montoDepositado, 2, '.', '')]);
            return ['status' => 'success'];
        } catch (PDOException $e) {
            return ['status' => $e->getCode() === '23000' ? 'ya_reportado' : 'error'];
        }
    }

    public function resolver(int $pagoId, float $saldo, string $nota, int $adminId): bool {
        $this->conexion->beginTransaction();
        try {
        $lock = $this->conexion->prepare('SELECT usuario_id FROM reporte_diferencia_pago WHERE pago_id=?');
        $lock->execute([$pagoId]);
        $uid = $lock->fetchColumn();
        $lock = $this->conexion->prepare('SELECT id FROM usuario WHERE id=? FOR UPDATE');
        $lock->execute([$uid]);
        $q = $this->conexion->prepare('SELECT 1 FROM uso_saldo_pago u JOIN reporte_diferencia_pago r ON r.id=u.reporte_id WHERE r.pago_id=? LIMIT 1');
        $q->execute([$pagoId]);
        if ($q->fetchColumn()) throw new RuntimeException('Este saldo ya tiene movimientos y no se puede sobrescribir.');
        $estado = $saldo > 0 ? 'confirmado' : 'rechazado';
        $stmt = $this->conexion->prepare("UPDATE reporte_diferencia_pago SET saldo_favor=?, estado=?, nota_admin=?, revisado_por=? WHERE pago_id=?");
        $stmt->execute([number_format(max(0, $saldo), 2, '.', ''), $estado, $nota, $adminId, $pagoId]);
        if ($stmt->rowCount() > 0) { $this->conexion->commit(); return true; }
        $stmt = $this->conexion->prepare("SELECT 1 FROM reporte_diferencia_pago WHERE pago_id=?");
        $stmt->execute([$pagoId]);
        $existe = (bool)$stmt->fetchColumn();
        $this->conexion->commit();
        return $existe;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $e;
        }
    }

    public function resumenUsuario(int $usuarioId): array {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) AS reportes, COALESCE(SUM(CASE WHEN estado='confirmado' THEN saldo_favor ELSE 0 END),0) AS saldo FROM reporte_diferencia_pago WHERE usuario_id=?");
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch() ?: [];
        return ['reportes' => (int)($fila['reportes'] ?? 0), 'saldo' => (float)($fila['saldo'] ?? 0)];
    }
}
