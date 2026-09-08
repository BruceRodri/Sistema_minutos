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

    public function abrirTurno($usuario_id, $bus_id, $valor, $ruta) {
        try {
            $this->cerrarTurnosVencidos();

            $turnoExistente = $this->obtenerRestriccionTurnoHoy($usuario_id, $bus_id);
            if ($turnoExistente === 'conductor') {
                return ['conductor_duplicado' => true];
            }
            if ($turnoExistente === 'bus') {
                return ['bus_duplicado' => true];
            }

            // El conductor se obtiene de la sesión y se valida nuevamente en la BD.
            // El navegador nunca envía ni decide el código del conductor.
            $sql = "INSERT INTO turno
                        (usuario_id, bus_id, fecha, hora_apertura, hora_cierre, valor, ruta)
                    SELECT u.id, :bus_id, CURDATE(), CURTIME(), '23:59:00', :valor, :ruta
                    FROM usuario u
                    INNER JOIN rol r ON u.rol_id = r.id
                    INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                    WHERE u.id = :usuario_id
                      AND u.codigo_conductor IS NOT NULL
                      AND u.activo = 1
                      AND r.nombre = 'conductor'
                      AND r.activo = 1
                      AND eu.activo = 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':bus_id' => $bus_id,
                ':valor' => $valor,
                ':ruta' => $ruta
            ]);

            if ($stmt->rowCount() !== 1) {
                return ['conductor_invalido' => true];
            }

            $turnoId = $this->conexion->lastInsertId();
            $stmtTurno = $this->conexion->prepare(
                "SELECT t.fecha, t.hora_apertura, t.valor, t.ruta, u.codigo_conductor
                 FROM turno t
                 INNER JOIN usuario u ON t.usuario_id = u.id
                 WHERE t.id = :turno_id"
            );
            $stmtTurno->execute([':turno_id' => $turnoId]);
            $turnoCreado = $stmtTurno->fetch();

            return [
                'id' => $turnoId,
                'fecha' => $turnoCreado['fecha'],
                'hora' => $turnoCreado['hora_apertura'],
                'hora_cierre' => '23:59:00',
                'codigo_conductor' => $turnoCreado['codigo_conductor'],
                'valor' => (float)$turnoCreado['valor'],
                'ruta' => $turnoCreado['ruta']
            ];
        } catch (PDOException $e) {
            // 1062 protege también ante dos solicitudes simultáneas.
            if ($e->errorInfo[1] == 1062) {
                $detalle = $e->errorInfo[2] ?? '';
                if (str_contains($detalle, 'unq_conductor_fecha')) {
                    return ['conductor_duplicado' => true];
                }
                return ['bus_duplicado' => true];
            }
            return false;
        }
    }

    private function obtenerRestriccionTurnoHoy($usuario_id, $bus_id) {
        $sql = "SELECT usuario_id, bus_id
                FROM turno
                WHERE fecha = CURDATE()
                  AND (usuario_id = :usuario_id OR bus_id = :bus_id)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':bus_id' => $bus_id
        ]);

        foreach ($stmt->fetchAll() as $turno) {
            if ((int)$turno['usuario_id'] === (int)$usuario_id) {
                return 'conductor';
            }
            if ((int)$turno['bus_id'] === (int)$bus_id) {
                return 'bus';
            }
        }

        return null;
    }

    public function obtenerTurnosHoy() {
        $this->cerrarTurnosVencidos();

        $sql = "SELECT t.id, t.fecha, t.hora_apertura, t.hora_cierre,
                       b.id AS bus_id, b.disco, b.placa, b.activo AS bus_activo,
                       u.id AS conductor_id, u.codigo_conductor, u.nombres, u.apellidos
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                INNER JOIN usuario u ON t.usuario_id = u.id
                WHERE t.fecha = CURDATE() AND t.activo = 1
                ORDER BY t.hora_apertura DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerTurnos($disco = '', $codigoConductor = '', $fecha = '', $limite = 10, $offset = 0) {
        $this->cerrarTurnosVencidos();

        [$condiciones, $parametros] = $this->construirFiltrosTurnos($disco, $codigoConductor, $fecha);
        $sql = "SELECT t.id, t.fecha, t.hora_apertura, t.hora_cierre,
                       b.id AS bus_id, b.disco,
                       u.id AS conductor_id, u.codigo_conductor
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                INNER JOIN usuario u ON t.usuario_id = u.id
                {$condiciones}
                ORDER BY t.fecha DESC, t.hora_apertura DESC, t.id DESC
                LIMIT :limite OFFSET :offset";

        $stmt = $this->conexion->prepare($sql);
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', max(1, (int)$limite), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarTurnos($disco = '', $codigoConductor = '', $fecha = '') {
        [$condiciones, $parametros] = $this->construirFiltrosTurnos($disco, $codigoConductor, $fecha);
        $sql = "SELECT COUNT(*)
                FROM turno t
                INNER JOIN bus b ON t.bus_id = b.id
                INNER JOIN usuario u ON t.usuario_id = u.id
                {$condiciones}";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        return (int)$stmt->fetchColumn();
    }

    private function construirFiltrosTurnos($disco, $codigoConductor, $fecha) {
        $filtros = [];
        $parametros = [];

        if ($disco !== '') {
            $filtros[] = 'b.disco LIKE :disco';
            $parametros[':disco'] = '%' . $disco . '%';
        }
        if ($codigoConductor !== '') {
            $filtros[] = 'u.codigo_conductor LIKE :codigo_conductor';
            $parametros[':codigo_conductor'] = '%' . $codigoConductor . '%';
        }
        if ($fecha !== '') {
            $filtros[] = 't.fecha = :fecha';
            $parametros[':fecha'] = $fecha;
        }

        $condiciones = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';
        return [$condiciones, $parametros];
    }

    public function cerrarTurnosVencidos() {
        $sql = "UPDATE turno
                SET activo = 0
                WHERE activo = 1
                  AND TIMESTAMP(fecha, hora_cierre) <= NOW()";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute();
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
