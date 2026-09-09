CREATE TABLE IF NOT EXISTS usuario_permiso_modulo (
    usuario_id INT NOT NULL,
    modulo VARCHAR(40) NOT NULL,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, modulo),
    CONSTRAINT fk_permiso_modulo_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

