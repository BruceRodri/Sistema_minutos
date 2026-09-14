CREATE TABLE IF NOT EXISTS frase_pago (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estado ENUM('incompleto', 'anulado') NOT NULL,
    texto VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO frase_pago (id, estado, texto) VALUES
(1, 'anulado', 'El comprobante no corresponde al disco o a la fecha del pago.'),
(2, 'anulado', 'El comprobante está duplicado y ya fue utilizado en otro pago.'),
(3, 'incompleto', 'El valor depositado es menor al total pendiente. Adjunte el comprobante del valor restante.'),
(4, 'incompleto', 'Falta adjuntar uno de los comprobantes para completar el pago.')
ON DUPLICATE KEY UPDATE id = frase_pago.id;
