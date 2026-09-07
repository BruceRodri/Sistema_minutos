<?php
// Dao/TurnoDao.php

class TurnoDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerBusPorDisco($disco) {
        $sql = "SELECT * FROM bus WHERE disco = :disco LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':disco', $disco, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function obtenerBusPorId($id) {
        $sql = "SELECT * FROM bus WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function abrirTurno($usuario_id, $bus_id) {
        try {
            $sql = "INSERT INTO turno (usuario_id, bus_id, fecha, hora_apertura)
                    VALUES (:usuario_id, :bus_id, CURDATE(), CURTIME())";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':bus_id' => $bus_id
            ]);
            return [
                'id' => $this->conexion->lastInsertId(),
                'hora' => $this->conexion->query('SELECT CURTIME()')->fetchColumn()
            ];
        } catch (PDOException $e) {
            // 1062 = entrada duplicada (clave única unq_bus_fecha)
            if ($e->errorInfo[1] == 1062) {
                return ['duplicado' => true];
            }
            return false;
        }
    }

    public function obtenerTurnosHoy() {
        $sql = "SELECT t.id, t.hora_apertura,
                       b.id AS bus_id, b.disco, b.placa, b.activo AS bus_activo,
                       u.id AS conductor_id, u.nombres, u.apellidos
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                INNER JOIN usuario u ON t.usuario_id = u.id
                WHERE t.fecha = CURDATE() AND t.activo = 1
                ORDER BY t.hora_apertura DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerTurnosHoyConductor($usuario_id) {
        $sql = "SELECT t.id, t.hora_apertura, b.disco
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                WHERE t.usuario_id = :uid AND t.fecha = CURDATE() AND t.activo = 1
                ORDER BY t.hora_apertura DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':uid' => $usuario_id]);
        return $stmt->fetchAll();
    }

    public function cambiarEstadoBus($bus_id, $nuevoEstado) {
        $sql = "UPDATE bus SET activo = :estado WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':estado' => $nuevoEstado,
            ':id' => $bus_id
        ]);
    }
}
?>