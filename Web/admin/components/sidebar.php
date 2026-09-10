<?php 
// Web/admin/components/sidebar.php
$paginaSidebarActual = basename($_SERVER['PHP_SELF']);
$rol = $_SESSION['rol'] ?? '';
$nombre = htmlspecialchars($_SESSION['nombre'] ?? '');
require_once __DIR__ . '/../../../Config/permisos.php';
$permisosSidebar = isset($conexion) ? permisosEfectivosUsuario($conexion, (int)($_SESSION['usuario_id'] ?? 0), $rol) : permisosPredeterminadosRol($rol);
$puedeSidebar = static fn($modulo) => in_array($modulo, $permisosSidebar, true);
$rutaWebSidebar = rutaPrimeraInterfaz($permisosSidebar, 'WEB');
$rutaAppSidebar = rutaPrimeraInterfaz($permisosSidebar, 'APP');
if ($rutaAppSidebar && $paginaSidebarActual === 'perfil.php') $rutaAppSidebar = '/App/conductor/perfil.php';

$rolesAdmin = ['admin', 'secretaria', 'operativo'];
?>

<!-- Barra superior móvil (Z-[60] para estar por encima de todo) -->
<div class="md:hidden fixed top-0 left-0 h-20 w-full bg-gray-900 text-white px-3 py-2 flex items-center z-[60] shadow-md">
    <button id="btnMenu" class="mr-3 text-white focus:outline-none p-2 rounded hover:bg-gray-800 transition-colors" aria-label="Abrir menú">
        <i class="fas fa-bars text-2xl"></i>
    </button>
    <div class="flex min-w-0 items-center">
        <img src="../../Assets/icons/icon-192x192.png" alt="Logo" class="h-8 w-8 mr-2 rounded">
        <h1 class="truncate text-xl font-bold">EJECUTTRANS</h1>
    </div>
</div>

<!-- Overlay oscuro para móvil -->
<div id="mobileOverlay" class="hidden md:hidden fixed inset-0 bg-black bg-opacity-50 z-[50] transition-opacity"></div>

<!-- Botón flotante Escritorio -->
<button id="btnOpenDesktop" class="hidden fixed top-4 left-4 z-50 items-center justify-center w-10 h-10 bg-gray-900 text-white rounded shadow-lg hover:bg-gray-800 transition-colors" style="display: none;">
    <i class="fas fa-bars"></i>
</button>

<!-- Barra lateral -->
<aside id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 w-64 bg-gray-900 text-white flex flex-col transition-transform duration-300 ease-in-out z-[55] pt-16 md:pt-0 shadow-2xl">
    
    <!-- Cabecera de la barra lateral (Escritorio) -->
    <div class="hidden md:flex h-16 items-center justify-center border-b border-gray-800 relative">
        <img src="../../Assets/icons/icon-192x192.png" alt="Logo" class="h-6 w-6 mr-2 rounded">
        <h1 class="text-lg font-bold tracking-wider">EJECUTTRANS</h1>
        <button id="btnCloseDesktop" class="absolute right-3 text-gray-400 hover:text-white transition-colors focus:outline-none" title="Ocultar menú">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <?php if($rutaWebSidebar): ?>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4 px-4">Administración</p>

            <?php if ($puedeSidebar('web_dashboard')): ?><a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'dashboard.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-chart-line w-6"></i><span>Dashboard</span>
            </a><?php endif; ?>

            <?php if ($puedeSidebar('web_buses')): ?><a href="buses.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'buses.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-bus w-6"></i><span>Buses</span>
            </a><?php endif; ?>

            <?php if ($puedeSidebar('web_turnos')): ?><a href="turnos.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'turnos.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-clock w-6"></i><span>Turnos</span>
            </a><?php endif; ?>

            <?php if ($puedeSidebar('web_pagos')): ?><a href="pagos.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'pagos.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-file-circle-check w-6"></i><span>Pagos</span>
            </a><?php endif; ?>
            <?php if ($rol === 'admin'): ?>
            <a href="usuarios.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'usuarios.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-users w-6"></i><span>Usuarios</span>
            </a>
            <?php endif; ?>
            <?php if ($puedeSidebar('web_socios')): ?><a href="socios.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'socios.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-id-card w-6"></i><span>Socios</span>
            </a><?php endif; ?>
            <?php if ($puedeSidebar('web_valores')): ?><a href="valores.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo ($paginaSidebarActual == 'valores.php') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <i class="fas fa-file-excel w-6"></i><span>Valores Diarios</span>
            </a><?php endif; ?>
        <?php endif; ?>
        <a href="/Web/admin/perfil.php" <?php echo $paginaSidebarActual === 'perfil.php' ? 'aria-current="page"' : ''; ?> class="flex items-center px-4 py-3 rounded-lg <?php echo $paginaSidebarActual === 'perfil.php' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>"><i class="fas fa-user mr-3"></i>Mi perfil</a>
    </nav>
    
    <div class="p-4 border-t border-gray-800">
        <div class="mb-4 px-4 flex items-center">
            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
            <div>
                <p class="text-sm text-gray-400">Usuario actual:</p>
                <p class="font-semibold text-xs text-blue-400 truncate"><?php echo $nombre; ?></p>
                <?php if ($paginaSidebarActual !== 'perfil.php'): ?><p class="text-[10px] text-gray-500 uppercase"><?php echo $rol; ?></p><?php endif; ?>
            </div>
        </div>
        <?php if ($rutaAppSidebar): ?>
        <a href="<?php echo htmlspecialchars($rutaAppSidebar, ENT_QUOTES, 'UTF-8'); ?>" class="mb-3 flex items-center justify-center w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold transition-colors shadow">
            <i class="fas fa-mobile-screen-button mr-2"></i>Ir a la App
        </a>
        <?php endif; ?>
        <a href="../../auth/logout.php" class="flex items-center justify-center w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition-colors shadow-lg hover:shadow-xl">
            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
        </a>
    </div>
</aside>

<style>
    .pagination-hidden { display: none !important; }
    @media (max-width: 767px) {
        body > main {
            height: calc(100vh - 5rem);
            min-width: 0;
            margin-top: 5rem !important;
        }
        body > main > header {
            height: auto !important;
            min-height: 5rem !important;
            flex: none !important;
            align-items: flex-start !important;
            gap: .75rem !important;
            padding: 1rem !important;
            overflow: visible !important;
        }
        body > main > header > * {
            min-width: 0;
            max-width: 100%;
        }
        body > main > header > :last-child:not(:first-child) {
            width: 100%;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        body > main > header form {
            max-width: 100%;
            flex-wrap: wrap;
        }
        .admin-table-wrap {
            overflow: visible !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        table.admin-responsive-table,
        table.admin-responsive-table tbody {
            display: block;
            width: 100%;
            min-width: 0 !important;
        }
        table.admin-responsive-table thead { display: none; }
        table.admin-responsive-table tbody tr:not(.hidden):not(.pagination-hidden) {
            display: block;
            width: 100%;
            margin-bottom: 1rem;
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
        }
        table.admin-responsive-table tbody tr.hidden,
        table.admin-responsive-table tbody tr.pagination-hidden { display: none !important; }
        table.admin-responsive-table tbody td:not([colspan]) {
            display: grid !important;
            grid-template-columns: minmax(7rem, 38%) minmax(0, 62%);
            align-items: center;
            gap: .75rem;
            min-width: 0 !important;
            padding: .8rem 1rem !important;
            border-bottom: 1px solid #f1f5f9;
            text-align: right !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }
        table.admin-responsive-table tbody td:not([colspan])::before {
            content: attr(data-label);
            text-align: left;
            color: #64748b;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        table.admin-responsive-table tbody td:last-child { border-bottom: 0; }
        table.admin-responsive-table tbody td[colspan] {
            display: block !important;
            width: 100%;
            white-space: normal !important;
        }
        .admin-client-pagination { justify-content: center !important; }
    }
</style>

<script>
    const btnMenu = document.getElementById('btnMenu');
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');

    if (btnMenu && sidebar) {
        btnMenu.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            if(mobileOverlay) mobileOverlay.classList.toggle('hidden');
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    }

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

    // Tablas administrativas: tarjetas en móvil y 20 registros por página.
    document.addEventListener('DOMContentLoaded', () => {
        const prepararTabla = (tabla) => {
            tabla.classList.add('admin-responsive-table');
            tabla.parentElement?.classList.add('admin-table-wrap');
            const etiquetas = [...tabla.querySelectorAll('thead th')].map((th) => th.textContent.trim());
            tabla.querySelectorAll('tbody tr').forEach((fila) => {
                [...fila.children].forEach((celda, indice) => {
                    if (celda.tagName === 'TD' && !celda.hasAttribute('colspan')) {
                        celda.dataset.label = etiquetas[indice] || 'Dato';
                    }
                });
            });
        };

        const crearPaginador = (tabla) => {
            if (tabla.dataset.serverPagination === 'true') return;
            const cuerpo = tabla.tBodies[0];
            if (!cuerpo) return;
            let pagina = 1;
            const limite = 20;
            const barra = document.createElement('div');
            barra.className = 'admin-client-pagination mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3';
            tabla.parentElement.insertAdjacentElement('afterend', barra);

            const renderizar = () => {
                prepararTabla(tabla);
                const filas = [...cuerpo.rows].filter((fila) => !fila.querySelector('td[colspan]'));
                const filtradas = filas.filter((fila) => !fila.classList.contains('hidden'));
                const totalPaginas = Math.max(1, Math.ceil(filtradas.length / limite));
                pagina = Math.min(pagina, totalPaginas);
                filas.forEach((fila) => fila.classList.add('pagination-hidden'));
                filtradas.slice((pagina - 1) * limite, pagina * limite)
                    .forEach((fila) => fila.classList.remove('pagination-hidden'));

                if (filtradas.length <= limite) {
                    barra.classList.add('hidden');
                    barra.replaceChildren();
                    return;
                }
                barra.classList.remove('hidden');
                const primero = (pagina - 1) * limite + 1;
                const ultimo = Math.min(pagina * limite, filtradas.length);
                barra.innerHTML = `<p class="text-sm text-gray-500">Mostrando ${primero}–${ultimo} de ${filtradas.length}</p>
                    <div class="flex items-center gap-2">
                        <button type="button" data-pagina="anterior" class="rounded-lg border px-3 py-2 text-sm font-bold disabled:opacity-40" ${pagina === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>
                        <span class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white">${pagina} / ${totalPaginas}</span>
                        <button type="button" data-pagina="siguiente" class="rounded-lg border px-3 py-2 text-sm font-bold disabled:opacity-40" ${pagina === totalPaginas ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>
                    </div>`;
            };

            barra.addEventListener('click', (evento) => {
                const boton = evento.target.closest('button[data-pagina]');
                if (!boton || boton.disabled) return;
                pagina += boton.dataset.pagina === 'siguiente' ? 1 : -1;
                renderizar();
                tabla.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            document.addEventListener('input', () => window.setTimeout(() => { pagina = 1; renderizar(); }, 0));
            document.addEventListener('change', () => window.setTimeout(() => { pagina = 1; renderizar(); }, 0));
            new MutationObserver(() => window.setTimeout(renderizar, 0))
                .observe(cuerpo, { childList: true });
            renderizar();
        };

        document.querySelectorAll('main table').forEach((tabla) => {
            prepararTabla(tabla);
            if (tabla.tBodies[0]) {
                new MutationObserver(() => window.setTimeout(() => prepararTabla(tabla), 0))
                    .observe(tabla.tBodies[0], { childList: true });
            }
            crearPaginador(tabla);
        });
    });
</script>

<?php require_once __DIR__ . '/../../../Config/aviso_cumpleanos.php'; ?>
