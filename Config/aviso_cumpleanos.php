<?php if (!empty($_SESSION['usuario_id'])):
$_SESSION['csrf_cumpleanos'] ??= bin2hex(random_bytes(32));
?>
<aside data-token="<?php echo htmlspecialchars($_SESSION['csrf_cumpleanos'], ENT_QUOTES); ?>" id="avisoCumpleanos" hidden role="status" aria-live="polite" style="position:fixed;right:16px;top:88px;z-index:65;max-width:min(360px,calc(100vw - 32px));max-height:55vh;overflow:auto;background:#fff7ed;color:#7c2d12;border:2px solid #fdba74;border-radius:20px;padding:18px;box-shadow:0 8px 30px #0002">
    <button type="button" id="cerrarCumpleanos" aria-label="Cerrar aviso de cumpleaños" style="float:right;padding:0 8px;font-size:24px">×</button>
    <strong id="tituloCumpleanos"></strong>
    <div id="mensajesCumpleanos"></div>
</aside>
<script src="/Assets/js/cumpleanos.js?v=<?php echo hash_file('sha256', __DIR__ . '/../Assets/js/cumpleanos.js'); ?>"></script>
<?php endif; ?>
