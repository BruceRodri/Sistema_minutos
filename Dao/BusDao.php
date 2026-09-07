<?php
// Dao/BusDao.php

class BusDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM bus ORDER BY CAST(disco AS UNSIGNED), disco";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM bus WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function obtenerPorDisco($disco) {
        $sql = "SELECT * FROM bus WHERE disco = :disco LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':disco', $disco, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function existeDisco($disco, $excluirId = null) {
        $sql = "SELECT id FROM bus WHERE disco = :disco";
        if ($excluirId !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':disco', $disco, PDO::PARAM_STR);
        if ($excluirId !== null) {
            $stmt->bindParam(':id', $excluirId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    public function crearBus($disco, $placa) {
        try {
            if ($placa === '') {
                $placa = $disco;
            }
            $sql = "INSERT INTO bus (placa, disco, activo) VALUES (:placa, :disco, 1)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':placa' => $placa, ':disco' => $disco]);
            return true;
        } catch (PDOException $e) {
            return ($e->errorInfo[1] == 1062) ? 'duplicado' : false;
        }
    }

    public function editarBus($id, $disco, $placa) {
        try {
            if ($placa === '') {
                $placa = $disco;
            }
            $sql = "UPDATE bus SET disco = :disco, placa = :placa WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':disco' => $disco, ':placa' => $placa, ':id' => $id]);
            return true;
        } catch (PDOException $e) {
            return ($e->errorInfo[1] == 1062) ? 'duplicado' : false;
        }
    }

    public function eliminarBus($id) {
        try {
            $sql = "DELETE FROM bus WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            // 1451 = violación de llave foránea (bus con turnos o socios vinculados)
            return ($e->errorInfo[1] == 1451) ? 'en_uso' : false;
        }
    }

    public function cambiarEstado($id, $nuevoEstado) {
        $sql = "UPDATE bus SET activo = :estado WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
    }
}
?>