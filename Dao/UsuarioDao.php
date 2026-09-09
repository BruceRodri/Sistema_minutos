<?php
// Dao/UsuarioDao.php

class UsuarioDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerPorCedula($cedula) {
        $sql = "SELECT u.id, u.cedula, u.codigo_conductor, u.nombres, u.apellidos,
                       CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                       r.nombre AS rol, u.activo, u.rol_id, u.estado_usuario_id
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                WHERE u.cedula = :cedula AND u.activo = 1 AND eu.activo = 1 AND r.activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function obtenerTodos() {
        $sql = "SELECT u.id, u.cedula, CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                       r.nombre AS rol, eu.id AS estado, u.activo, u.nombres, u.apellidos, u.rol_id
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                ORDER BY r.nombre, u.nombres";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function crearUsuario($cedula, $nombre, $password, $rol) {
        try {
            // debe_cambiar_clave se inserta como 1 (True) por defecto
            $sql = "INSERT INTO usuarios (cedula, nombre, password, rol, debe_cambiar_clave, estado) 
                    VALUES (:cedula, :nombre, :password, :rol, 1, 1)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':rol', $rol, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false; // Retorna falso si hay error (ej. cédula duplicada)
        }
    }

    public function actualizarClave($id, $passwordHash) {
        $sql = "UPDATE usuarios SET password = :password, debe_cambiar_clave = 0 WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerPorId($id) {
        $sql = "SELECT id, nombre, rol FROM usuarios WHERE id = :id AND estado = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Añadir al final de Dao/UsuarioDao.php

    public function resetearClave($id, $passwordHash) {
        // Asigna la nueva clave y obliga al usuario a cambiarla en su próximo login
        $sql = "UPDATE usuarios SET password = :password, debe_cambiar_clave = 1 WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cambiarEstado($id, $nuevoEstado) {
        // Habilita (1) o deshabilita (0) al usuario
        $sql = "UPDATE usuarios SET estado = :estado WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerPermisosUsuario($usuario_id) {
        // Busca los códigos de los módulos donde el usuario tiene permiso de visualización
        $sql = "SELECT m.codigo 
                FROM permisos_usuarios p
                JOIN modulos m ON p.modulo_id = m.id
                WHERE p.usuario_id = :usuario_id AND p.puede_ver = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Devolvemos un arreglo simple con los códigos (ej: ['mod_socios', 'mod_actas'])
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function crearUsuarioYDevolverId($cedula, $nombre, $password, $rol) {
        try {
            $sql = "INSERT INTO usuarios (cedula, nombre, password, rol, debe_cambiar_clave, estado) 
                    VALUES (:cedula, :nombre, :password, :rol, 1, 1)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':cedula' => $cedula,
                ':nombre' => $nombre,
                ':password' => $password,
                ':rol' => $rol
            ]);
            // Devuelve el ID generado para vincularle los permisos
            return $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            return false; 
        }
    }

    public function asignarPermisosModulos($usuario_id, $modulos_ids) {
        // Asignamos permiso de lectura y creación por defecto al módulo
        $sql = "INSERT INTO permisos_usuarios (usuario_id, modulo_id, puede_ver, puede_crear, puede_editar) 
                VALUES (:uid, :mid, 1, 1, 0)";
        $stmt = $this->conexion->prepare($sql);
        
        foreach ($modulos_ids as $mod_id) {
            $stmt->execute([
                ':uid' => $usuario_id,
                ':mid' => $mod_id
            ]);
        }
    }

    public function obtenerIdsPermisosUsuario($usuario_id) {
        $sql = "SELECT modulo_id FROM permisos_usuarios WHERE usuario_id = :usuario_id AND puede_ver = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function editarUsuario($id, $cedula, $nombre, $rol) {
        try {
            $sql = "UPDATE usuarios SET cedula = :cedula, nombre = :nombre, rol = :rol WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([
                ':cedula' => $cedula,
                ':nombre' => $nombre,
                ':rol' => $rol,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function limpiarPermisos($usuario_id) {
        $sql = "DELETE FROM permisos_usuarios WHERE usuario_id = :uid";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':uid' => $usuario_id]);
    }

    // ==================== BD NUEVA (sistema_minutos_db) ====================

    public function obtenerUsuariosAdministrables() {
        $sql = "SELECT u.id, u.nombres, u.apellidos, u.fecha_nacimiento, u.cedula,
                       u.codigo_conductor, u.codigo_socio, u.activo,
                       r.nombre AS rol, eu.nombre AS estado
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                ORDER BY u.id DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerRolesActivos() {
        $sql = "SELECT nombre FROM rol WHERE activo = 1 ORDER BY id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function rolActivoExiste($rol) {
        $sql = "SELECT COUNT(*) FROM rol WHERE nombre = :rol AND activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':rol' => $rol]);
        return (int)$stmt->fetchColumn() === 1;
    }

    public function obtenerUsuarioAdministrablePorId($id) {
        $sql = "SELECT u.id, u.nombres, u.apellidos, u.fecha_nacimiento, u.cedula,
                       u.codigo_conductor, u.codigo_socio, u.activo, r.nombre AS rol
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                WHERE u.id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function siguienteCodigoBloqueante($columna) {
        $propia = !$this->conexion->inTransaction();
        if ($propia) {
            $this->conexion->beginTransaction();
        }
        $stmt = $this->conexion->prepare(
            "SELECT MAX(CAST($columna AS UNSIGNED)) FROM usuario WHERE $columna IS NOT NULL FOR UPDATE"
        );
        $stmt->execute();
        $maximo = $stmt->fetchColumn();
        if ($propia) {
            $this->conexion->commit();
        }
        $siguiente = $maximo ? ($maximo + 1) : 1;
        return str_pad((string)$siguiente, 3, '0', STR_PAD_LEFT);
    }

    public function generarCodigoConductor() {
        return $this->siguienteCodigoBloqueante('codigo_conductor');
    }

    public function generarCodigoSocio() {
        return $this->siguienteCodigoBloqueante('codigo_socio');
    }

    public function registrarUsuario($nombres, $apellidos, $fechaNacimiento, $cedula, $rolNombre) {
        $propia = !$this->conexion->inTransaction();
        if ($propia) {
            $this->conexion->beginTransaction();
        }
        try {
            $codigoConductor = null;
            $codigoSocio = null;
            $rolNombreBajado = strtolower($rolNombre);

            if ($rolNombreBajado === 'conductor') {
                $codigoConductor = $this->siguienteCodigoBloqueante('codigo_conductor');
            } elseif ($rolNombreBajado === 'socio') {
                $codigoSocio = $this->siguienteCodigoBloqueante('codigo_socio');
            }

            $sql = "INSERT INTO usuario (nombres, apellidos, fecha_nacimiento, cedula, codigo_conductor, codigo_socio, rol_id, estado_usuario_id, activo)
                    VALUES (:nombres, :apellidos, :fecha, :cedula, :codigo_conductor, :codigo_socio,
                            (SELECT id FROM rol WHERE nombre = :rol),
                            (SELECT id FROM estado_usuario WHERE nombre = 'habilitado'), 1)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':fecha' => $fechaNacimiento,
                ':cedula' => $cedula,
                ':codigo_conductor' => $codigoConductor,
                ':codigo_socio' => $codigoSocio,
                ':rol' => $rolNombre
            ]);
            if ($propia) {
                $this->conexion->commit();
            }
            return [
                'id' => $this->conexion->lastInsertId(),
                'codigo_conductor' => $codigoConductor,
                'codigo_socio' => $codigoSocio
            ];
        } catch (PDOException $e) {
            if ($propia && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ($e->errorInfo[1] == 1062) ? 'duplicado' : false;
        }
    }

    public function actualizarUsuarioAdministrable($id, $nombres, $apellidos, $fechaNacimiento, $cedula, $rolNombre) {
        $manejaTransaccion = !$this->conexion->inTransaction();
        try {
            if ($manejaTransaccion) {
                $this->conexion->beginTransaction();
            }

            $stmt = $this->conexion->prepare(
                "SELECT codigo_conductor, codigo_socio FROM usuario WHERE id = :id FOR UPDATE"
            );
            $stmt->execute([':id' => $id]);
            $usuario = $stmt->fetch();
            if (!$usuario) {
                if ($manejaTransaccion) {
                    $this->conexion->rollBack();
                }
                return 'no_encontrado';
            }

            $codigoConductor = null;
            $codigoSocio = null;
            if ($rolNombre === 'conductor') {
                $codigoConductor = $usuario['codigo_conductor'] ?: $this->generarCodigoConductor();
            } elseif ($rolNombre === 'socio') {
                $codigoSocio = $usuario['codigo_socio'] ?: $this->generarCodigoSocio();
            }

            $sql = "UPDATE usuario
                    SET nombres = :nombres,
                        apellidos = :apellidos,
                        fecha_nacimiento = :fecha,
                        cedula = :cedula,
                        codigo_conductor = :codigo_conductor,
                        codigo_socio = :codigo_socio,
                        rol_id = (SELECT id FROM rol WHERE nombre = :rol AND activo = 1)
                    WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':fecha' => $fechaNacimiento,
                ':cedula' => $cedula,
                ':codigo_conductor' => $codigoConductor,
                ':codigo_socio' => $codigoSocio,
                ':rol' => $rolNombre,
                ':id' => $id
            ]);

            if ($manejaTransaccion) {
                $this->conexion->commit();
            }
            return [
                'id' => $id,
                'codigo_conductor' => $codigoConductor,
                'codigo_socio' => $codigoSocio
            ];
        } catch (PDOException $e) {
            if ($manejaTransaccion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ($e->errorInfo[1] == 1062) ? 'duplicado' : false;
        }
    }

    public function cambiarEstadoUsuarioAdministrable($id, $habilitado) {
        $estado = $habilitado ? 'habilitado' : 'deshabilitado';
        $sql = "UPDATE usuario
                SET activo = :activo,
                    estado_usuario_id = (SELECT id FROM estado_usuario WHERE nombre = :estado AND activo = 1)
                WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':activo' => $habilitado ? 1 : 0,
            ':estado' => $estado,
            ':id' => $id
        ]);
    }

    public function obtenerPorCedulaNuevo($cedula) {
        $sql = "SELECT u.id, u.cedula, u.codigo_conductor, u.codigo_socio, u.nombres, u.apellidos,
                       CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                       r.nombre AS rol
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                INNER JOIN estado_usuario eu ON u.estado_usuario_id = eu.id
                WHERE u.cedula = :cedula AND u.activo = 1 AND eu.activo = 1 AND r.activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
