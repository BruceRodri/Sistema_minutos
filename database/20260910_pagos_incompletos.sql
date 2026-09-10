-- Compatibiliza bases existentes con comprobantes múltiples y pagos incompletos.
ALTER TABLE pago
    MODIFY COLUMN comprobante TEXT NULL,
    MODIFY COLUMN estado ENUM('en_espera', 'aprobado', 'anulado', 'incompleto')
        NOT NULL DEFAULT 'en_espera';
