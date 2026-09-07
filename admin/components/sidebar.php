<?php 
// admin/components/sidebar.php
$paginaActual = basename($_SERVER['PHP_SELF']);
$rol = $_SESSION['rol'] ?? '';
$nombre = htmlspecialchars($_SESSION['nombre'] ?? '');
$permisos = $_SESSION['permisos'] ?? [];

$base_url = '/sistema_resoluciones/'; 
?>

<!-- Barra superior móvil (Z-[60] para estar por encima de todo) -->
<div class="md:hidden fixed top-0 left-0 w-full bg-gray-900 text-white p-4 flex justify-between items-center z-[60] shadow-md">
    <div class="flex items-center">
        <img src="<?php echo $base_url; ?>Assets/icons/icon-192x192.png" alt="Logo" class="h-8 w-8 mr-2 rounded">
        <h1 class="text-xl font-bold">EJECUTTRANS</h1>
    </div>
    <button id="btnMenu" class="text-white focus:outline-none p-2 rounded hover:bg-gray-800 transition-colors">
        <i class="fas fa-bars text-2xl"></i>
    </button>
</div>

<!-- Overlay oscuro para móvil -->
<div id="mobileOverlay" class="hidden md:hidden fixed inset-0 bg-black bg-opacity-50 z-[50] transition-opacity"></div>

<!-- Botón flotante Escritorio -->
<button id="btnOpenDesktop" class="hidden fixed top-4 left-4 z-50 items-center justify-center w-10 h-10 bg-gray-900 text-white rounded shadow-lg hover:bg-gray-800 transition-colors" style="display: none;">
    <i class="fas fa-bars"></i>
</button>

<!-- Barra lateral -->
<!-- Se ajusta pt-16 para que en móvil el contenido baje, pero el menú cubra toda la altura -->
<aside id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 w-64 bg-gray-900 text-white flex flex-col transition-transform duration-300 ease-in-out z-[55] pt-16 md:pt-0 shadow-2xl">
    
    <!-- Cabecera de la barra lateral (Escritorio) -->
    <div class="hidden md:flex h-16 items-center justify-center border-b border-gray-800 relative">
        <img src="<?php echo $base_url; ?>Assets/icons/icon-192x192.png" alt="Logo" class="h-6 w-6 mr-2 rounded">
        <h1 class="text-lg font-bold tracking-wider">EJECUTTRANS</h1>
        <button id="btnCloseDesktop" class="absolute right-3 text-gray-400 hover:text-white transition-colors focus:outline-none" title="Ocultar menú">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- MÓDULO ADMINISTRADOR RESTRINGIDO -->
        <?php if($rol === 'admin'): ?>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4 px-4">Administración</p>
            <a href="<?php echo $base_url; ?>admin/dashboard.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'dashboard.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-home w-6"></i><span>Inicio</span>
            </a>
            <a href="<?php echo $base_url; ?>admin/turnos.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'turnos.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-clock w-6"></i><span>Turnos del Día</span>
            </a>
            <a href="<?php echo $base_url; ?>admin/usuarios.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'usuarios.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-users w-6"></i><span>Personal y Permisos</span>
            </a>
        <?php endif; ?>

        <!-- MÓDULOS DE GESTIÓN Y CONSULTA (Lectura / Edición) -->
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4 px-4">Consultas y Gestión</p>
        
        <?php if(in_array('mod_resoluciones', $permisos) || $rol === 'admin'): ?>
        <a href="<?php echo $base_url; ?>secretaria/gestion_resoluciones.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'gestion_resoluciones.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <i class="fas fa-folder-open w-6"></i><span>Resoluciones</span>
        </a>
        <?php endif; ?>

        <?php if(in_array('mod_socios', $permisos) || $rol === 'admin'): ?>
        <a href="<?php echo $base_url; ?>admin/socios.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'socios.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <i class="fas fa-id-card w-6"></i><span>Directorio de Socios</span>
        </a>
        <?php endif; ?>

        <!-- MÓDULOS OPERATIVOS (Ingreso de Datos) -->
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-6 px-4">Ingreso de Datos</p>

        <?php if(in_array('mod_resoluciones', $permisos) || $rol === 'admin'): ?>
        <a href="<?php echo $base_url; ?>operador/ingreso_resolucion.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'ingreso_resolucion.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <i class="fas fa-file-signature w-6"></i><span>Registrar Resolución</span>
        </a>
        <?php endif; ?>

        <?php if(in_array('mod_socios', $permisos) || $rol === 'admin'): ?>
        <a href="<?php echo $base_url; ?>admin/importar_socios.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaActual == 'importar_socios.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <i class="fas fa-user-plus w-6"></i><span>Registrar Socios</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="p-4 border-t border-gray-800">
        <div class="mb-4 px-4 flex items-center">
            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
            <div>
                <p class="text-sm text-gray-400">Usuario actual:</p>
                <p class="font-semibold text-xs text-blue-400 truncate"><?php echo $nombre; ?></p>
                <p class="text-[10px] text-gray-500 uppercase"><?php echo $rol; ?></p>
            </div>
        </div>
        <a href="<?php echo $base_url; ?>auth/logout.php" class="flex items-center justify-center w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition-colors shadow-lg hover:shadow-xl">
            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
        </a>
    </div>
</aside>

<script>
    const btnMenu = document.getElementById('btnMenu');
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');

    // Lógica para abrir/cerrar en móvil
    if (btnMenu && sidebar) {
        btnMenu.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            if(mobileOverlay) mobileOverlay.classList.toggle('hidden');
        });
    }

    // Cerrar al tocar el fondo oscuro
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    }

    // Lógica Escritorio
    const btnCloseDesktop = document.getElementById('btnCloseDesktop');
    const btnOpenDesktop = document.getElementById('btnOpenDesktop');
    
    if (btnCloseDesktop && btnOpenDesktop) {
        btnCloseDesktop.addEventListener('click', () => {
            sidebar.classList.add('md:hidden');
            btnOpenDesktop.classList.remove('hidden');
            btnOpenDesktop.classList.add('md:flex');
            btnOpenDesktop.style.display = 'flex';
        });
        
        btnOpenDesktop.addEventListener('click', () => {
            sidebar.classList.remove('md:hidden');
            btnOpenDesktop.style.display = 'none';
        });
    }
</script>