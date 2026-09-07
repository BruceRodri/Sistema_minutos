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