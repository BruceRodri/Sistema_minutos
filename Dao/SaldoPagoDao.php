<?php
require_once __DIR__ . '/ReporteDiferenciaDao.php';
require_once __DIR__ . '/PagoDao.php';

class SaldoPagoDao {
    public function __construct(private PDO $db) {
        new ReporteDiferenciaDao($db);
        $db->exec("CREATE TABLE IF NOT EXISTS uso_saldo_pago (
            id INT AUTO_INCREMENT PRIMARY KEY, reporte_id INT NOT NULL, pago_id INT NOT NULL,
            monto DECIMAL(10,2) NOT NULL, devuelto TINYINT NOT NULL DEFAULT 0,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporte_id) REFERENCES reporte_diferencia_pago(id),
            FOREIGN KEY (pago_id) REFERENCES pago(id), UNIQUE KEY (reporte_id,pago_id)
        ) ENGINE=InnoDB");
    }

    public function pagar(int $uid, array $ids, ?string $archivo): array {
        $this->db->beginTransaction();
        try {
            // Bloqueo común para impedir consumos concurrentes del mismo usuario.
            $q = $this->db->prepare('SELECT id FROM usuario WHERE id=? FOR UPDATE');
            $q->execute([$uid]);
            $q = $this->db->prepare("SELECT * FROM reporte_diferencia_pago WHERE usuario_id=? AND estado='confirmado' AND saldo_favor>0 ORDER BY id FOR UPDATE");
            $q->execute([$uid]);
            $creditos = $q->fetchAll();
            $disponible = array_sum(array_map(static fn($r) => (int)round($r['saldo_favor'] * 100), $creditos));
            if (!$disponible) throw new RuntimeException('Tu saldo cambió. Actualiza la página.');
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
            if (!$ids) throw new RuntimeException('Selecciona al menos un día para pagar.');
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $q = $this->db->prepare("SELECT id, valor, fecha, disco, ruta FROM obligacion_pago
                WHERE id IN ($marcadores) AND pagado=0 AND pago_id IS NULL AND activo=1 AND valor>0
                ORDER BY id FOR UPDATE");
            $q->execute($ids);
            $obligaciones = $q->fetchAll();
            if (count($obligaciones) !== count($ids)) throw new RuntimeException('Las deudas seleccionadas ya no están disponibles.');
            $total = array_sum(array_map(static fn($o) => (int)round($o['valor'] * 100), $obligaciones));
            $usado = min($total, $disponible);
            if ($usado < $total && !$archivo) throw new RuntimeException('Adjunta el comprobante de la diferencia restante.');
            if ($usado === $total) {
                $pagoId = $this->agruparEnOrigen($uid, (int)$creditos[0]['pago_id'], $obligaciones, $total);
            } else {
                $resultado = (new PagoDao($this->db))->registrarPagoObligaciones($uid, $ids, $archivo);
                if ($resultado['status'] !== 'success') throw new RuntimeException('Las deudas seleccionadas ya no están disponibles.');
                $pagoId = (int)$resultado['pago_id'];
            }
            $resta = $usado;
            foreach ($creditos as $credito) {
                if (!$resta) break;
                $cantidad = min($resta, (int)round($credito['saldo_favor'] * 100));
                $q = $this->db->prepare('UPDATE reporte_diferencia_pago SET saldo_favor=saldo_favor-? WHERE id=?');
                $q->execute([$cantidad / 100, $credito['id']]);
                $q = $this->db->prepare('INSERT INTO uso_saldo_pago(reporte_id,pago_id,monto) VALUES(?,?,?)
                    ON DUPLICATE KEY UPDATE monto=monto+?, devuelto=0');
                $q->execute([$credito['id'], $pagoId, $cantidad / 100, $cantidad / 100]);
                $resta -= $cantidad;
            }
            $this->db->commit();
            return ['status'=>'success', 'message'=>$usado === $total
                ? 'Los días pagados se agregaron al pago original del excedente.'
                : 'Pago registrado con saldo a favor.', 'saldo'=>($disponible-$usado)/100];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status'=>'error', 'message'=>$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo registrar el pago.'];
        }
    }

    private function agruparEnOrigen(int $uid, int $pagoId, array $obligaciones, int $total): int {
        $q = $this->db->prepare("SELECT detalle_pagos FROM pago WHERE id=? AND usuario_id=? AND activo=1 AND estado='aprobado' FOR UPDATE");
        $q->execute([$pagoId, $uid]);
        $origen = $q->fetch();
        if (!$origen) throw new RuntimeException('El pago que originó el saldo ya no está disponible para agrupar.');
        $detalle = json_decode($origen['detalle_pagos'] ?? '', true);
        if (!is_array($detalle) || !$detalle) {
            // Recuperar los días de pagos antiguos que no guardaban detalle JSON.
            $q = $this->db->prepare("SELECT t.id AS turno_id, t.fecha, b.disco, t.ruta
                FROM turno t JOIN bus b ON b.id=t.bus_id WHERE t.pago_id=?");
            $q->execute([$pagoId]);
            $detalle = $q->fetchAll(PDO::FETCH_ASSOC);
            $q = $this->db->prepare('SELECT id AS obligacion_id, fecha, disco, ruta FROM obligacion_pago WHERE pago_id=?');
            $q->execute([$pagoId]);
            $detalle = array_merge($detalle, $q->fetchAll(PDO::FETCH_ASSOC));
        }
        foreach ($obligaciones as $o) {
            $detalle[] = ['obligacion_id'=>(int)$o['id'], 'fecha'=>$o['fecha'], 'disco'=>$o['disco'], 'ruta'=>$o['ruta']];
        }
        $q = $this->db->prepare('UPDATE pago SET monto_total=monto_total+?, detalle_pagos=? WHERE id=?');
        $q->execute([$total / 100, json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $pagoId]);
        $q = $this->db->prepare('UPDATE obligacion_pago SET pagado=1, pago_id=? WHERE id=? AND pago_id IS NULL AND pagado=0');
        foreach ($obligaciones as $o) {
            $q->execute([$pagoId, $o['id']]);
            if ($q->rowCount() !== 1) throw new RuntimeException('Una deuda cambió mientras se registraba el pago.');
        }
        return $pagoId;
    }
}
