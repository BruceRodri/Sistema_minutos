<?php
$permisosCambioInterfaz = permisosEfectivosUsuario($conexion, (int)($_SESSION['usuario_id'] ?? 0), (string)($_SESSION['rol'] ?? ''));
$rutaWebCambioInterfaz = rutaPrimeraInterfaz($permisosCambioInterfaz, 'WEB');
if ($rutaWebCambioInterfaz && basename($_SERVER['PHP_SELF']) === 'perfil.php') $rutaWebCambioInterfaz = '/Web/admin/perfil.php';
?>
<?php if ($rutaWebCambioInterfaz): ?>
<a href="<?php echo htmlspecialchars($rutaWebCambioInterfaz, ENT_QUOTES, 'UTF-8'); ?>"
   class="fixed bottom-24 right-4 z-40 inline-flex items-center rounded-full bg-slate-900 px-4 py-3 text-sm font-extrabold text-white shadow-xl hover:bg-blue-700 lg:bottom-6 lg:right-6">
    <i class="fas fa-desktop mr-2"></i>Ir a Web
</a>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../Config/aviso_cumpleanos.php'; ?>
