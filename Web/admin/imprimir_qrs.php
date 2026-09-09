<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'], true)) {
    header('Location: ../../index.php');
    exit;
}
require_once '../../Config/conexion.php';
require_once '../../Dao/BusDao.php';
$buses = (new BusDao($conexion))->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas QR de buses - Ejecuttrans</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: Arial, sans-serif; color: #111; background: #f3f4f6; }
        .barra { max-width: 190mm; margin: 0 auto 24px; }
        h1 { font-size: 24px; margin: 0 0 8px; }
        button { padding: 12px 20px; border: 0; border-radius: 8px; background: #1d4ed8; color: white; font-weight: bold; cursor: pointer; }
        button:disabled { opacity: .5; cursor: default; }
        .etiquetas { max-width: 190mm; margin: auto; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 5mm; }
        .etiqueta { border: 1px dashed #888; background: white; padding: 5mm; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        .marca { font-size: 12px; letter-spacing: 2px; margin-bottom: 3mm; }
        .qr { width: 44mm; height: 44mm; padding: 3mm; margin: auto; background: white; }
        .qr img, .qr canvas { width: 100%; height: 100%; display: block; }
        h2 { margin: 3mm 0 2mm; font-size: 20px; overflow-wrap: anywhere; }
        .placa { margin: 0; font-size: 13px; overflow-wrap: anywhere; }
        @media screen and (max-width: 650px) { .etiquetas { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media screen and (max-width: 420px) { .etiquetas { grid-template-columns: 1fr; } }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { padding: 0; background: white; }
            .barra { display: none; }
            .etiquetas { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <h1>Etiquetas QR de buses</h1>
        <p><?php echo count($buses); ?> buses · Ordenados por disco · Incluye habilitados y deshabilitados.</p>
        <button id="imprimir" type="button" disabled>Imprimir todos los QR</button>
        <p id="mensaje" role="status"><?php echo $buses ? 'Preparando códigos QR…' : 'No hay buses registrados para imprimir.'; ?></p>
    </div>
    <main class="etiquetas">
        <?php foreach ($buses as $bus): ?>
        <article class="etiqueta">
            <div class="marca">EJECUTTRANS</div>
            <div class="qr" data-disco="<?php echo htmlspecialchars((string)$bus['disco'], ENT_QUOTES, 'UTF-8'); ?>"></div>
            <h2>Disco <?php echo htmlspecialchars((string)$bus['disco'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="placa">Placa: <?php echo htmlspecialchars((string)($bus['placa'] ?: 'Sin placa'), ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
        <?php endforeach; ?>
    </main>
    <script src="../../Assets/js/imprimir_qrs.js"></script>
</body>
</html>
