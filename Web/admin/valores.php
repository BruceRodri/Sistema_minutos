<?php
// Web/admin/valores.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Config/permisos.php';
exigirPermisoModulo($conexion, 'web_valores', 'dashboard.php');
if (empty($_SESSION['csrf_valores'])) $_SESSION['csrf_valores'] = bin2hex(random_bytes(32));
require_once '../../Dao/ValoresDao.php';

function parametroVistaValores($nombre) {
    $valor = $_GET[$nombre] ?? '';
    return is_scalar($valor) ? trim((string)$valor) : '';
}

function urlPaginaValores($pagina, $filtros) {
    $parametros = ['pagina' => $pagina];
    foreach ($filtros as $nombre => $valor) {
        if ($valor !== '') $parametros[$nombre] = $valor;
    }
    return '?' . http_build_query($parametros);
}

$filtros = [
    'disco' => parametroVistaValores('disco'),
    'fecha' => parametroVistaValores('fecha'),
    'valor' => parametroVistaValores('valor'),
    'ruta' => parametroVistaValores('ruta')
];

if ($filtros['fecha'] !== '') {
    $fechaValida = DateTime::createFromFormat('!Y-m-d', $filtros['fecha']);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $filtros['fecha']) {
        $filtros['fecha'] = '';
    }
}

$valoresDao = new ValoresDao($conexion);
$filas = $valoresDao->obtenerFilasFiltradas($filtros['disco'], $filtros['fecha'], $filtros['valor'], $filtros['ruta']);
$registrosPorPagina = 20;
$totalFilas = count($filas);
$totalPaginas = max(1, (int)ceil($totalFilas / $registrosPorPagina));
$paginaActual = min(max(1, (int)parametroVistaValores('pagina')), $totalPaginas);
$offset = ($paginaActual - 1) * $registrosPorPagina;
$filasPagina = array_slice($filas, $offset, $registrosPorPagina);
$primerRegistro = $totalFilas > 0 ? $offset + 1 : 0;
$ultimoRegistro = min($offset + $registrosPorPagina, $totalFilas);
$existeArchivo = $valoresDao->archivoExiste();
$fechaSubida = $valoresDao->fechaSubida();
require __DIR__ . '/../../Config/exportar_modulo.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-valores" content="<?php echo htmlspecialchars($_SESSION['csrf_valores']); ?>">
    <title>Valores Diarios - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="/Assets/icons/icon-192x192.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>dialog::backdrop { background: rgb(3 7 18 / 60%); backdrop-filter: blur(4px); } dialog { margin: auto; } summary::-webkit-details-marker { display: none; }</style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="min-h-16 bg-white shadow-sm flex flex-wrap items-center gap-4 px-4 py-3 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">Valores Diarios</h2>
                <p class="text-xs text-gray-500">Datos financieros cargados desde Excel</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button id="btnGestionArchivos" type="button" class="inline-flex items-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50">
                    <i class="fas fa-folder-open mr-2"></i>Gestión Archivos
                </button>
                <button id="btnAbrirCarga" type="button" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-700 transition-colors">
                    <i class="fas fa-file-arrow-up mr-2"></i>Cargar archivos
                </button>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">
            <div id="alerta" class="hidden mb-5 rounded-xl border p-4 text-sm font-bold text-center" role="alert"></div>

            <section class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div>
                        <p class="text-sm text-gray-500">Registros encontrados</p>
                        <p id="totalValores" class="text-3xl font-bold text-blue-700"><?php echo $totalFilas; ?></p>
                    </div>
                    <div class="text-right text-sm">
                        <p id="estadoArchivo" class="font-bold <?php echo $existeArchivo ? 'text-green-700' : 'text-amber-700'; ?>">
                            <i class="fas <?php echo $existeArchivo ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mr-1"></i>
                            <?php echo $existeArchivo ? 'Archivo cargado' : 'Sin archivo cargado'; ?>
                        </p>
                        <p id="fechaArchivo" class="text-xs text-gray-500 mt-1"><?php echo $fechaSubida ? 'Última carga: ' . htmlspecialchars($fechaSubida) : 'Cargue un archivo para visualizar datos'; ?></p>
                    </div>
                </div>

                <form method="GET" action="valores.php" class="bg-slate-50 border-b border-gray-200 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label for="filtroDiscoValor" class="block mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 text-center">Disco</label>
                            <input id="filtroDiscoValor" name="disco" type="search" value="<?php echo htmlspecialchars($filtros['disco']); ?>" placeholder="Ej.: 002"
                                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-center font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        </div>
                        <div>
                            <label for="filtroFechaValor" class="block mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 text-center">Fecha</label>
                            <input id="filtroFechaValor" name="fecha" type="date" value="<?php echo htmlspecialchars($filtros['fecha']); ?>"
                                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-center outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        </div>
                        <div>
                            <label for="filtroMontoValor" class="block mb-1 text-xs font-bold uppercase tracking-wide text-gray-500 text-center">Valor</label>
                            <input id="filtroMontoValor" name="valor" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($filtros['valor']); ?>" placeholder="Ej.: 2.50"
                                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-center outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        </div>
                        <div>
                            <label for="filtroRutaValor" class="block mb-1 text-xs font-bold uppercase tracking-wide text-gray-500">Ruta</label>
                            <input id="filtroRutaValor" name="ruta" type="search" value="<?php echo htmlspecialchars($filtros['ruta']); ?>" placeholder="Buscar ruta..."
                                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap justify-end gap-2">
                        <a href="valores.php" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-eraser mr-2"></i>Limpiar
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white hover:bg-blue-700">
                            <i class="fas fa-magnifying-glass mr-2"></i>Filtrar
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table data-server-pagination="true" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ruta</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaValores" class="bg-white divide-y divide-gray-200">
                            <?php if (empty($filasPagina)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-14 text-center text-gray-500">
                                        <i class="fas fa-table-list text-3xl text-gray-300 mb-3"></i>
                                        <p><?php echo $existeArchivo ? 'No se encontraron datos con los filtros seleccionados.' : 'Aún no se ha cargado un archivo de valores.'; ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filasPagina as $fila): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-center font-mono font-bold text-blue-800"><?php echo htmlspecialchars($fila['disco']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700"><?php echo htmlspecialchars(date('d/m/Y', strtotime($fila['fecha']))); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-800">$ <?php echo number_format((float)$fila['valor'], 2, '.', ','); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($fila['ruta'] ?: '—'); ?></td>
                                        <td class="px-6 py-4 text-center"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo (int)$fila['pagado'] === 1 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>"><?php echo (int)$fila['pagado'] === 1 ? 'Pagado' : 'No pagado'; ?></span></td>
                                        <td class="px-6 py-4 text-center">
                                            <details class="inline-block text-left">
                                                <summary class="list-none cursor-pointer whitespace-nowrap rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50">Acciones <i class="fas fa-chevron-down ml-2 text-xs"></i></summary>
                                                <button type="button" data-borrar-registro="<?php echo (int)$fila['id']; ?>" data-descripcion="<?php echo htmlspecialchars('Disco ' . $fila['disco'] . ' · ' . date('d/m/Y', strtotime($fila['fecha'])) . ' · $ ' . number_format((float)$fila['valor'], 2), ENT_QUOTES); ?>" class="mt-1 w-full whitespace-nowrap rounded-lg bg-red-50 px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-100"><i class="fas fa-trash-can mr-2"></i>Borrar registro</button>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-4">
                    <p id="rangoValores" class="text-sm text-gray-500<?php echo $totalFilas > 0 ? '' : ' hidden'; ?>">
                        <?php if ($totalFilas > 0): ?>Mostrando <?php echo $primerRegistro; ?>–<?php echo $ultimoRegistro; ?> de <?php echo $totalFilas; ?><?php endif; ?>
                    </p>
                    <nav id="paginacionValores" class="flex flex-wrap items-center justify-end gap-2<?php echo $totalPaginas > 1 ? '' : ' hidden'; ?>" aria-label="Paginación de valores">
                        <?php if ($totalPaginas > 1): ?>
                            <?php if ($paginaActual > 1): ?>
                                <a href="<?php echo htmlspecialchars(urlPaginaValores($paginaActual - 1, $filtros)); ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"><i class="fas fa-chevron-left mr-1"></i>Anterior</a>
                            <?php endif; ?>
                            <?php for ($pagina = max(1, $paginaActual - 2); $pagina <= min($totalPaginas, $paginaActual + 2); $pagina++): ?>
                                <a href="<?php echo htmlspecialchars(urlPaginaValores($pagina, $filtros)); ?>" class="inline-flex min-w-10 items-center justify-center rounded-lg px-3 py-2 text-sm font-bold <?php echo $pagina === $paginaActual ? 'bg-blue-600 text-white shadow' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?>"><?php echo $pagina; ?></a>
                            <?php endfor; ?>
                            <?php if ($paginaActual < $totalPaginas): ?>
                                <a href="<?php echo htmlspecialchars(urlPaginaValores($paginaActual + 1, $filtros)); ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Siguiente<i class="fas fa-chevron-right ml-1"></i></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </nav>
                </div>
            </section>
        </div>
    </main>

    <div id="modalCargaValores" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Cargar datos</h3>
                    <p class="text-xs text-gray-500">Archivos Excel con DISCO, FECHA, VALOR y RUTA</p>
                </div>
                <button id="btnCerrarCarga" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-red-600"><i class="fas fa-times"></i></button>
            </div>
            <form id="formSubirValores" class="p-6">
                <label class="block w-full cursor-pointer rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-file-excel block text-5xl text-green-600 mb-3"></i>
                    <span id="nombreArchivo" class="block max-h-40 overflow-y-auto break-words whitespace-pre-line text-sm font-bold text-gray-700">Seleccione uno o varios archivos .xlsx</span>
                    <span class="block text-xs text-gray-400 mt-2">Puede seleccionar varios archivos. Máximo: 10 MB por archivo</span>
                    <input id="inputArchivo" name="archivo" type="file" accept=".xlsx" class="hidden" multiple required>
                </label>
                <div class="mt-6 flex gap-3">
                    <button id="btnCancelarCarga" type="button" class="flex-1 rounded-lg bg-gray-100 px-4 py-2.5 font-bold text-gray-700 hover:bg-gray-200">Cancelar</button>
                    <button id="btnSubir" type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white shadow hover:bg-blue-700 disabled:opacity-60"><i class="fas fa-upload mr-2"></i>Cargar</button>
                </div>
            </form>
        </div>
    </div>

    <dialog id="gestionArchivosValores" aria-labelledby="tituloGestionArchivos" class="w-[calc(100%_-_2rem)] max-w-3xl max-h-[85vh] rounded-2xl bg-white p-0 shadow-2xl">
        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div><h3 id="tituloGestionArchivos" class="text-xl font-bold text-gray-800"><i class="fas fa-folder-open mr-2 text-blue-600"></i>Gestión Archivos</h3><p class="mt-1 text-sm text-gray-500">Consulta, descarga o elimina tus archivos Excel.</p></div>
            <button id="cerrarGestionArchivos" type="button" aria-label="Cerrar gestión de archivos" class="rounded-lg px-3 py-2 text-gray-400 hover:bg-gray-100"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-6 py-4 text-sm text-blue-800 bg-blue-50">Al eliminar un Excel se borran sus registros vinculados. Si vuelve a subir datos antiguos idénticos sin archivo, quedarán vinculados a esa carga. Los datos que ya pertenecen a otro Excel conservan su vínculo.</div>
        <div id="listaArchivosValores" class="space-y-3 p-6" aria-live="polite"></div>
    </dialog>

    <dialog id="confirmarBorradoValores" aria-labelledby="tituloBorradoValores" aria-describedby="detalleBorradoValores" class="w-[calc(100%_-_2rem)] max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-100 text-2xl text-red-600"><i class="fas fa-trash-can"></i></div>
        <h3 id="tituloBorradoValores" class="text-xl font-bold text-gray-900">¿Eliminar registro?</h3>
        <p id="detalleBorradoValores" class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-gray-600"></p>
        <p class="mt-3 text-xs font-bold text-red-600">Esta acción no se puede deshacer.</p>
        <p id="errorBorradoValores" role="alert" class="hidden mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700"></p>
        <div class="mt-6 flex gap-3">
            <button id="cancelarBorradoValores" type="button" autofocus class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancelar</button>
            <button id="aceptarBorradoValores" type="button" class="flex-1 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50">Sí, eliminar</button>
        </div>
    </dialog>

    <script src="../../Assets/js/valores.js?v=<?php echo filemtime('../../Assets/js/valores.js'); ?>"></script>
<script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registrado correctamente.', reg))
                    .catch(err => console.log('Falló el registro del Service Worker.', err));
            });
        }
    </script>
</body>
</html>
