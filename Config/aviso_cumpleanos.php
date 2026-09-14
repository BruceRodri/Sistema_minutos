<?php if (!empty($_SESSION['usuario_id'])):
require_once __DIR__ . '/permisos.php';
$_SESSION['csrf_cumpleanos'] ??= bin2hex(random_bytes(32));
?>
<?php if (!empty($cumpleanosEnApp)): ?>
<dialog id="avisoCumpleanos" data-url="<?php echo htmlspecialchars(ruta('Controllers/CumpleanosController.php'), ENT_QUOTES); ?>" data-token="<?php echo htmlspecialchars($_SESSION['csrf_cumpleanos'], ENT_QUOTES); ?>" aria-labelledby="tituloCumpleanos" aria-describedby="mensajesCumpleanos" class="m-auto w-[calc(100%-2rem)] max-w-md max-h-[90dvh] overflow-y-auto rounded-3xl border-0 bg-white p-0 text-gray-800 shadow-2xl backdrop:bg-black/60 backdrop:backdrop-blur-sm">
    <div class="relative px-6 py-8 sm:p-8 text-center">
        <button type="button" id="cerrarCumpleanos" aria-label="Cerrar aviso de cumpleaños" class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus:ring-2 focus:ring-blue-300"><i class="fas fa-xmark text-xl" aria-hidden="true"></i></button>
        <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600"><i class="fas fa-cake-candles text-4xl" aria-hidden="true"></i></div>
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-blue-600">Un día especial</p>
        <h2 id="tituloCumpleanos" class="mb-4 text-3xl font-extrabold text-gray-800"></h2>
        <div id="mensajesCumpleanos" class="space-y-3 text-base leading-relaxed text-gray-600"></div>
        <button type="button" id="aceptarCumpleanos" class="mt-7 w-full rounded-xl bg-blue-600 px-5 py-3.5 text-lg font-bold text-white shadow-sm transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-300"><i class="fas fa-check mr-2" aria-hidden="true"></i>Entendido</button>
    </div>
</dialog>
<?php else: ?>
<aside data-url="<?php echo htmlspecialchars(ruta('Controllers/CumpleanosController.php'), ENT_QUOTES); ?>" data-token="<?php echo htmlspecialchars($_SESSION['csrf_cumpleanos'], ENT_QUOTES); ?>" id="avisoCumpleanos" hidden role="status" aria-live="polite" style="position:fixed;right:16px;top:88px;z-index:90;max-width:min(360px,calc(100vw - 32px));max-height:55vh;overflow:auto;background:#fff7ed;color:#7c2d12;border:2px solid #fdba74;border-radius:20px;padding:18px;box-shadow:0 8px 30px #0002">
    <button type="button" id="cerrarCumpleanos" aria-label="Cerrar aviso de cumpleaños" style="float:right;padding:0 8px;font-size:24px">×</button>
    <strong id="tituloCumpleanos"></strong>
    <div id="mensajesCumpleanos"></div>
</aside>
<?php endif; ?>
<script src="<?php echo htmlspecialchars(ruta('Assets/js/cumpleanos.js'), ENT_QUOTES); ?>?v=<?php echo hash_file('sha256', __DIR__ . '/../Assets/js/cumpleanos.js'); ?>"></script>
<?php endif; ?>
