<?php
class FrasePagoDao {
    public function __construct(private PDO $conexion) {}

    public function obtenerTodas(): array {
        return $this->conexion->query('SELECT id, estado, texto FROM frase_pago ORDER BY estado, id')->fetchAll();
    }

    public function eliminar(int $id): void {
        if ($id <= 0) throw new InvalidArgumentException('Selecciona una frase válida.');
        $stmt = $this->conexion->prepare('DELETE FROM frase_pago WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() !== 1) throw new InvalidArgumentException('La frase ya no existe. Actualiza la lista.');
    }

    public function guardar(int $id, string $estado, string $texto): void {
        $texto = trim($texto);
        if (!in_array($estado, ['incompleto', 'anulado'], true) || $texto === '' || mb_strlen($texto, 'UTF-8') > 255 || $id < 0) {
            throw new InvalidArgumentException('Selecciona el estado y escribe una frase de 1 a 255 caracteres.');
        }
        if ($id > 0) {
            $stmt = $this->conexion->prepare('SELECT id FROM frase_pago WHERE id = ? AND estado = ?');
            $stmt->execute([$id, $estado]);
            if (!$stmt->fetchColumn()) throw new InvalidArgumentException('La frase no existe en el estado seleccionado.');
            $stmt = $this->conexion->prepare('UPDATE frase_pago SET texto = ? WHERE id = ? AND estado = ?');
            $stmt->execute([$texto, $id, $estado]);
        } else {
            $stmt = $this->conexion->prepare('INSERT INTO frase_pago (estado, texto) VALUES (?, ?)');
            $stmt->execute([$estado, $texto]);
        }
    }
}
