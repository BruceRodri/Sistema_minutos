<?php
// Dao/SocioDao.php

class SocioDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerSociosConBuses() {
        $sql = "SELECT u.id AS usuario_id, u.cedula, u.nombres, u.apellidos,
                       b.id AS bus_id, b.disco, b.placa, b.activo AS bus_activo,
                       ub.id AS ub_id
                FROM usuario u
                LEFT JOIN usuario_bus ub ON ub.usuario_id = u.id AND ub.activo = 1
                LEFT JOIN bus b ON ub.bus_id = b.id
                WHERE u.rol_id = (SELECT id FROM rol WHERE nombre = 'socio')
                ORDER BY u.nombres, u.apellidos, CAST(b.disco AS UNSIGNED), b.disco";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerTodosSocios() {
        $sql = "SELECT u.id, u.cedula, CONCAT(u.nombres, ' ', u.apellidos) AS nombre
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                WHERE r.nombre = 'socio'
                ORDER BY u.nombres, u.apellidos";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerDiscosDisponibles() {
        $sql = "SELECT b.id, b.disco, b.placa
                FROM bus b
                WHERE b.activo = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM usuario_bus ub
                      WHERE ub.bus_id = b.id AND ub.activo = 1
                  )
                ORDER BY CAST(b.disco AS UNSIGNED), b.disco";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function asignarDiscoDisponible($bus_id, $usuario_id) {
        $manejaTransaccion = !$this->conexion->inTransaction();

        try {
            if ($manejaTransaccion) {
                $this->conexion->beginTransaction();
            }

            $stmt = $this->conexion->prepare(
                "SELECT u.id
                 FROM usuario u
                 INNER JOIN rol r ON u.rol_id = r.id
                 INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                 WHERE u.id = :usuario_id
                   AND r.nombre = 'socio'
                   AND u.activo = 1
                   AND eu.activo = 1
                 FOR UPDATE"
            );
            $stmt->execute([':usuario_id' => $usuario_id]);
            if (!$stmt->fetch()) {
                if ($manejaTransaccion) $this->conexion->rollBack();
                return 'socio_invalido';
            }

            $stmt = $this->conexion->prepare(
                "SELECT id FROM usuario_bus WHERE usuario_id = :usuario_id AND activo = 1 LIMIT 1"
            );
            $stmt->execute([':usuario_id' => $usuario_id]);
            if ($stmt->fetch()) {
                if ($manejaTransaccion) $this->conexion->rollBack();
                return 'socio_con_disco';
            }

            $stmt = $this->conexion->prepare("SELECT id FROM bus WHERE id = :bus_id AND activo = 1 FOR UPDATE");
            $stmt->execute([':bus_id' => $bus_id]);
            if (!$stmt->fetch()) {
                if ($manejaTransaccion) $this->conexion->rollBack();
                return 'disco_invalido';
            }

            $stmt = $this->conexion->prepare(
                "SELECT id FROM usuario_bus WHERE bus_id = :bus_id AND activo = 1 LIMIT 1"
            );
            $stmt->execute([':bus_id' => $bus_id]);
            if ($stmt->fetch()) {
                if ($manejaTransaccion) $this->conexion->rollBack();
                return 'no_disponible';
            }

            $stmt = $this->conexion->prepare(
                "INSERT INTO usuario_bus (usuario_id, bus_id, activo) VALUES (:usuario_id, :bus_id, 1)"
            );
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':bus_id' => $bus_id
            ]);

            if ($manejaTransaccion) {
                $this->conexion->commit();
            }
            return true;
        } catch (PDOException $e) {
            if ($manejaTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return false;
        }
    }

    public function cambiarSocioDeBus($bus_id, $nuevo_usuario_id) {
        $sql = "SELECT id FROM usuario_bus WHERE bus_id = :bus_id AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':bus_id' => $bus_id]);
        $relacion = $stmt->fetch();

        if ($relacion) {
            $sql = "UPDATE usuario_bus SET usuario_id = :uid WHERE id = :rid";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([':uid' => $nuevo_usuario_id, ':rid' => $relacion['id']]);
        }

        $sql = "INSERT INTO usuario_bus (usuario_id, bus_id, activo) VALUES (:uid, :bus_id, 1)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':uid' => $nuevo_usuario_id, ':bus_id' => $bus_id]);
    }
}
?>
