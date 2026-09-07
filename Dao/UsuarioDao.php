<?php
// Dao/UsuarioDao.php

class UsuarioDao {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerPorCedula($cedula) {
        $sql = "SELECT * FROM usuarios WHERE cedula = :cedula AND estado = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function obtenerTodos() {
        // Obtenemos todos los usuarios ordenados por rol y nombre
        $sql = "SELECT id, cedula, nombre, rol, estado, debe_cambiar_clave FROM usuarios ORDER BY rol, nombre";
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
}
?>