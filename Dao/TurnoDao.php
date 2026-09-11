<?php
// Dao/TurnoDao.php

class TurnoDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public static function esConductorDuplicado($turno) {
        return ($turno['estado'] ?? '') === 'fallido'
            && str_starts_with($turno['motivo'] ?? '', 'Usted ya abrió un turno');
    }

    public function obtenerBusPorDisco($disco) {
        $disco = trim((string)$disco);
        if (ctype_digit($disco)) {
            $disco = str_pad((string)(int)$disco, 2, '0', STR_PAD_LEFT);
        }
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

    public function registrarIntentoFallido($usuarioId, $busId, $discoEscaneado, $motivo) {
        try {
            $sql = "INSERT INTO intento_turno
                        (usuario_id, bus_id, disco_escaneado, fecha, hora_intento, motivo)
                    VALUES
                        (:usuario_id, :bus_id, :disco_escaneado, CURDATE(), CURTIME(), :motivo)";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([
                ':usuario_id' => (int)$usuarioId,
                ':bus_id' => $busId !== null ? (int)$busId : null,
                ':disco_escaneado' => mb_substr((string)$discoEscaneado, 0, 30),
                ':motivo' => mb_substr((string)$motivo, 0, 255)
            ]);
        } catch (PDOException $e) {
            error_log('No se pudo registrar el intento fallido de turno: ' . $e->getMessage());
            return false;
        }
    }

    public function abrirTurno($usuario_id, $bus_id) {
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
                        (usuario_id, bus_id, fecha, hora_apertura, hora_cierre)
                    SELECT u.id, :bus_id, CURDATE(), CURTIME(), '23:59:00'
                    FROM usuario u
                    INNER JOIN rol r ON u.rol_id = r.id
                    INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                    WHERE u.id = :usuario_id
                      AND u.activo = 1
                      AND r.activo = 1
                      AND eu.activo = 1
                      AND ((u.codigo_conductor IS NOT NULL AND r.nombre = 'conductor')
                           OR (u.codigo_socio IS NOT NULL AND r.nombre = 'socio'))";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':bus_id' => $bus_id
            ]);

            if ($stmt->rowCount() !== 1) {
                return ['conductor_invalido' => true];
            }

            $turnoId = $this->conexion->lastInsertId();
            $stmtTurno = $this->conexion->prepare(
                "SELECT t.fecha, t.hora_apertura, u.codigo_conductor
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
                'codigo_conductor' => $turnoCreado['codigo_conductor']
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
        $sql = "SELECT MAX(usuario_id = :usuario_id) AS conductor_duplicado,
                       MAX(bus_id = :bus_id) AS bus_duplicado
                FROM turno
                WHERE fecha = CURDATE()
                  AND activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':bus_id' => $bus_id
        ]);

        $restriccion = $stmt->fetch();
        if ((int)($restriccion['conductor_duplicado'] ?? 0) === 1) {
            return 'conductor';
        }
        if ((int)($restriccion['bus_duplicado'] ?? 0) === 1) {
            return 'bus';
        }

        return null;
    }

    public function deshabilitarTurnoHoy($turnoId, $usuarioResponsableId, $comentario) {
        $sql = "UPDATE turno
                SET activo = 0,
                    cancelado_en = NOW(),
                    cancelado_por = :cancelado_por,
                    comentario_cancelacion = :comentario
                WHERE id = :turno_id
                  AND fecha = CURDATE()
                  AND activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':cancelado_por' => (int)$usuarioResponsableId,
            ':comentario' => $comentario,
            ':turno_id' => (int)$turnoId
        ]);
        return $stmt->rowCount() === 1;
    }

    public function habilitarTurnoNuevamente($turnoId, $usuarioResponsableId) {
        $transaccionPropia = !$this->conexion->inTransaction();
        try {
            if ($transaccionPropia) {
                $this->conexion->beginTransaction();
            }

            $stmt = $this->conexion->prepare(
                "SELECT id, usuario_id, bus_id, valor, ruta, hora_cierre
                 FROM turno
                 WHERE id = :turno_id
                   AND fecha = CURDATE()
                   AND activo = 0
                   AND cancelado_en IS NOT NULL
                   AND rehabilitado_en IS NULL
                 FOR UPDATE"
            );
            $stmt->execute([':turno_id' => (int)$turnoId]);
            $turno = $stmt->fetch();
            if (!$turno) {
                if ($transaccionPropia) $this->conexion->rollBack();
                return 'no_disponible';
            }

            $stmtConflicto = $this->conexion->prepare(
                "SELECT MAX(bus_id = :bus_id) AS bus_ocupado,
                        MAX(usuario_id = :usuario_id) AS conductor_ocupado
                 FROM turno
                 WHERE fecha = CURDATE()
                   AND activo = 1"
            );
            $stmtConflicto->execute([
                ':bus_id' => (int)$turno['bus_id'],
                ':usuario_id' => (int)$turno['usuario_id']
            ]);
            $conflicto = $stmtConflicto->fetch();
            if ((int)($conflicto['bus_ocupado'] ?? 0) === 1) {
                if ($transaccionPropia) $this->conexion->rollBack();
                return 'bus_ocupado';
            }
            if ((int)($conflicto['conductor_ocupado'] ?? 0) === 1) {
                if ($transaccionPropia) $this->conexion->rollBack();
                return 'conductor_ocupado';
            }

            $stmtNuevo = $this->conexion->prepare(
                "INSERT INTO turno
                    (usuario_id, bus_id, fecha, hora_apertura, hora_cierre, valor, ruta, activo, pagado)
                 VALUES
                    (:usuario_id, :bus_id, CURDATE(), CURTIME(), :hora_cierre, :valor, :ruta, 1, 0)"
            );
            $stmtNuevo->execute([
                ':usuario_id' => (int)$turno['usuario_id'],
                ':bus_id' => (int)$turno['bus_id'],
                ':hora_cierre' => $turno['hora_cierre'],
                ':valor' => $turno['valor'],
                ':ruta' => $turno['ruta']
            ]);
            $nuevoTurnoId = (int)$this->conexion->lastInsertId();

            $stmtActualizar = $this->conexion->prepare(
                "UPDATE turno
                 SET rehabilitado_en = NOW(),
                     rehabilitado_por = :rehabilitado_por,
                     turno_rehabilitado_id = :nuevo_turno_id
                 WHERE id = :turno_id
                   AND rehabilitado_en IS NULL"
            );
            $stmtActualizar->execute([
                ':rehabilitado_por' => (int)$usuarioResponsableId,
                ':nuevo_turno_id' => $nuevoTurnoId,
                ':turno_id' => (int)$turnoId
            ]);
            if ($transaccionPropia) $this->conexion->commit();
            return $stmtActualizar->rowCount() === 1 ? true : 'no_disponible';
        } catch (PDOException $e) {
            if ($transaccionPropia && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            if (($e->errorInfo[1] ?? null) == 1062) {
                $detalle = $e->errorInfo[2] ?? '';
                return str_contains($detalle, 'unq_conductor_fecha')
                    ? 'conductor_ocupado'
                    : 'bus_ocupado';
            }
            return false;
        }
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

    public function obtenerEstadoFlotaHoy() {
        $this->cerrarTurnosVencidos();

        $sql = "SELECT b.id AS bus_id, b.disco, b.placa, b.activo AS bus_activo,
                       t.id AS turno_id, t.hora_apertura,
                       u.codigo_conductor, u.nombres, u.apellidos
                FROM bus b
                LEFT JOIN turno t
                       ON t.bus_id = b.id
                      AND t.fecha = CURDATE()
                      AND t.activo = 1
                LEFT JOIN usuario u ON u.id = t.usuario_id
                ORDER BY CAST(b.disco AS UNSIGNED), b.disco";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerTurnos($disco = '', $codigoConductor = '', $fecha = '', $limite = 10, $offset = 0, $estado = '') {
        $this->cerrarTurnosVencidos();

        [$condiciones, $parametros] = $this->construirFiltrosTurnos($disco, $codigoConductor, $fecha, $estado);
        $sql = "SELECT registros.*
                FROM (
                    SELECT CONCAT('turno-', t.id) AS registro_id,
                           t.id, t.fecha, t.hora_apertura, t.hora_cierre,
                           b.id AS bus_id, b.disco,
                           u.id AS conductor_id, u.codigo_usuario AS codigo_conductor,
                           CONCAT_WS(' ', u.nombres, u.apellidos) AS nombre_conductor,
                           CASE WHEN t.activo = 0 AND t.cancelado_en IS NOT NULL THEN 'deshabilitado' ELSE 'abierto' END AS estado,
                           CASE WHEN t.activo = 0 AND t.cancelado_en IS NOT NULL THEN t.comentario_cancelacion ELSE NULL END AS motivo,
                           t.rehabilitado_en
                    FROM turno t
                    INNER JOIN bus b ON t.bus_id = b.id
                    INNER JOIN usuario u ON t.usuario_id = u.id

                    UNION ALL

                    SELECT CONCAT('intento-', i.id) AS registro_id,
                           i.id, i.fecha, i.hora_intento AS hora_apertura, NULL AS hora_cierre,
                           i.bus_id, COALESCE(b.disco, NULLIF(i.disco_escaneado, ''), '—') AS disco,
                           u.id AS conductor_id, u.codigo_usuario AS codigo_conductor,
                           CONCAT_WS(' ', u.nombres, u.apellidos) AS nombre_conductor,
                           'fallido' AS estado, i.motivo, NULL AS rehabilitado_en
                    FROM intento_turno i
                    LEFT JOIN bus b ON i.bus_id = b.id
                    INNER JOIN usuario u ON i.usuario_id = u.id
                    WHERE i.activo = 1
                ) AS registros
                {$condiciones}
                ORDER BY registros.fecha DESC, registros.hora_apertura DESC, registros.registro_id DESC
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

    public function contarTurnos($disco = '', $codigoConductor = '', $fecha = '', $estado = '') {
        [$condiciones, $parametros] = $this->construirFiltrosTurnos($disco, $codigoConductor, $fecha, $estado);
        $sql = "SELECT COUNT(*)
                FROM (
                    SELECT b.disco, u.codigo_usuario AS codigo_conductor, t.fecha,
                           CASE WHEN t.activo = 0 AND t.cancelado_en IS NOT NULL THEN 'deshabilitado' ELSE 'abierto' END AS estado
                    FROM turno t
                    INNER JOIN bus b ON t.bus_id = b.id
                    INNER JOIN usuario u ON t.usuario_id = u.id

                    UNION ALL

                    SELECT COALESCE(b.disco, NULLIF(i.disco_escaneado, ''), '—') AS disco,
                           u.codigo_usuario AS codigo_conductor, i.fecha, 'fallido' AS estado
                    FROM intento_turno i
                    LEFT JOIN bus b ON i.bus_id = b.id
                    INNER JOIN usuario u ON i.usuario_id = u.id
                    WHERE i.activo = 1
                ) AS registros
                {$condiciones}";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        return (int)$stmt->fetchColumn();
    }

    private function construirFiltrosTurnos($disco, $codigoConductor, $fecha, $estado) {
        $filtros = [];
        $parametros = [];

        if ($disco !== '') {
            $filtros[] = 'registros.disco LIKE :disco';
            $parametros[':disco'] = '%' . $disco . '%';
        }
        if ($codigoConductor !== '') {
            $filtros[] = 'registros.codigo_conductor LIKE :codigo_conductor';
            $parametros[':codigo_conductor'] = '%' . $codigoConductor . '%';
        }
        if ($fecha !== '') {
            $filtros[] = 'registros.fecha = :fecha';
            $parametros[':fecha'] = $fecha;
        }

        if (in_array($estado, ['abierto', 'fallido', 'deshabilitado'], true)) {
            $filtros[] = 'registros.estado = :estado';
            $parametros[':estado'] = $estado;
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
