<?php
// Web/admin/turnos.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/TurnoDao.php';

$turnoDao = new TurnoDao($conexion);

$filtroDisco = trim($_GET['disco'] ?? '');
$filtroConductor = trim($_GET['conductor'] ?? '');
$filtroFecha = trim($_GET['fecha'] ?? '');

if ($filtroFecha !== '') {
    $fechaValida = DateTime::createFromFormat('Y-m-d', $filtroFecha);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $filtroFecha) {
        $filtroFecha = '';
    }
}

$registrosPorPagina = 20;
$paginaActual = max(1, (int)($_GET['pagina'] ?? 1));
$totalTurnos = $turnoDao->contarTurnos($filtroDisco, $filtroConductor, $filtroFecha);
$totalPaginas = max(1, (int)ceil($totalTurnos / $registrosPorPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$offset = ($paginaActual - 1) * $registrosPorPagina;
$turnos = $turnoDao->obtenerTurnos(
    $filtroDisco,
    $filtroConductor,
    $filtroFecha,
    $registrosPorPagina,
    $offset
);

$primerRegistro = $totalTurnos > 0 ? $offset + 1 : 0;
$ultimoRegistro = min($offset + $registrosPorPagina, $totalTurnos);

function construirUrlPagina($pagina, $disco, $conductor, $fecha) {
    $parametros = ['pagina' => $pagina];
    if ($disco !== '') $parametros['disco'] = $disco;
    if ($conductor !== '') $parametros['conductor'] = $conductor;
    if ($fecha !== '') $parametros['fecha'] = $fecha;
    return '?' . http_build_query($parametros);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de turnos - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <style>
        .flatpickr-calendar {
            border: 0;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
            font-family: inherit;
        }
        .flatpickr-calendar.arrowTop::before,
        .flatpickr-calendar.arrowTop::after {
            border-bottom-color: #2563eb;
        }
        .flatpickr-months {
            align-items: center;
            padding: 0.65rem 0.5rem;
            background: linear-gradient(135deg, #2563eb, #1e3a8a);
        }
        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month input.cur-year {
            color: #fff;
            fill: #fff;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month .flatpickr-monthDropdown-months:hover,
        .flatpickr-current-month input.cur-year:hover {
            background: transparent;
        }
        .flatpickr-monthDropdown-month {
            color: #1f2937;
        }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            top: 0.55rem;
            padding: 0.5rem;
            color: #fff;
            fill: #fff;
            border-radius: 0.5rem;
        }
        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        .flatpickr-weekdays {
            padding-top: 0.45rem;
            background: #eff6ff;
        }
        span.flatpickr-weekday {
            color: #1e40af;
            font-weight: 700;
            background: #eff6ff;
        }
        .flatpickr-days {
            padding: 0.45rem;
        }
        .flatpickr-day {
            border-radius: 0.65rem;
            color: #374151;
            font-weight: 600;
        }
        .flatpickr-day:hover,
        .flatpickr-day:focus {
            border-color: #dbeafe;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .flatpickr-day.today {
            border-color: #60a5fa;
            color: #1d4ed8;
        }
        .flatpickr-day.selected,
        .flatpickr-day.selected:hover,
        .flatpickr-day.selected:focus {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
            box-shadow: 0 5px 12px rgba(37, 99, 235, 0.3);
        }
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: #cbd5e1;
        }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Historial de turnos
                </h2>
                <p class="text-xs text-gray-500">Registros ordenados del más reciente al más antiguo</p>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">
            <form method="GET" action="turnos.php" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <label for="filtroDisco" class="flex items-center text-sm font-bold text-gray-700 mb-2">
                            <span class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center mr-3">
                                <i class="fas fa-bus"></i>
                            </span>
                            Buscar por disco
                        </label>
                        <input type="search" id="filtroDisco" name="disco" value="<?php echo htmlspecialchars($filtroDisco); ?>" placeholder="Ej.: 001"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <label for="filtroConductor" class="flex items-center text-sm font-bold text-gray-700 mb-2">
                            <span class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center mr-3">
                                <i class="fas fa-id-badge"></i>
                            </span>
                            Buscar por conductor
                        </label>
                        <input type="search" id="filtroConductor" name="conductor" value="<?php echo htmlspecialchars($filtroConductor); ?>" placeholder="Ej.: 003"
                               class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <label for="filtroFecha" class="flex items-center text-sm font-bold text-gray-700 mb-2">
                            <span class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-day"></i>
                            </span>
                            Buscar por fecha
                        </label>
                        <input type="text" id="filtroFecha" name="fecha" value="<?php echo htmlspecialchars($filtroFecha); ?>" placeholder="Seleccione una fecha" readonly
                               class="w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap justify-end gap-3">
                    <a href="turnos.php" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-eraser mr-2"></i> Limpiar filtros
                    </a>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-700 transition-colors">
                        <i class="fas fa-magnifying-glass mr-2"></i> Buscar
                    </button>
                </div>
            </form>

            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p class="text-sm text-gray-500">Registros encontrados</p>
                    <p id="totalTurnos" class="text-3xl font-bold text-blue-700"><?php echo $totalTurnos; ?></p>
                </div>
                <p id="rangoTurnos" class="text-sm text-gray-500<?php echo $totalTurnos > 0 ? '' : ' hidden'; ?>">
                    <?php if ($totalTurnos > 0): ?>
                        Mostrando <?php echo $primerRegistro; ?>–<?php echo $ultimoRegistro; ?> de <?php echo $totalTurnos; ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disco</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Conductor</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="tablaTurnos" class="bg-white divide-y divide-gray-200">
                        <?php if ($totalTurnos === 0): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-clock text-3xl mb-3 text-gray-300"></i>
                                    <p>No se encontraron turnos con los filtros seleccionados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($turnos as $turno): ?>
                                <?php
                                    $fechaApertura = DateTime::createFromFormat(
                                        'Y-m-d H:i:s',
                                        $turno['fecha'] . ' ' . $turno['hora_apertura']
                                    );
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <?php echo htmlspecialchars($turno['disco']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono font-semibold text-gray-700">
                                        <?php echo htmlspecialchars($turno['codigo_conductor'] ?? 'Sin código'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                        <?php echo htmlspecialchars($fechaApertura ? $fechaApertura->format('d/m/Y H:i:s') : $turno['fecha'] . ' ' . $turno['hora_apertura']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="contenedorPaginacion" class="<?php echo $totalPaginas > 1 ? '' : 'hidden'; ?>">
                <nav id="paginacionTurnos" class="mt-6 flex flex-wrap items-center justify-center gap-2" aria-label="Paginación de turnos">
                <?php if ($totalPaginas > 1): ?>
                    <?php
                        $paginaInicial = max(1, $paginaActual - 2);
                        $paginaFinal = min($totalPaginas, $paginaActual + 2);
                    ?>
                    <?php if ($paginaActual > 1): ?>
                        <a href="<?php echo htmlspecialchars(construirUrlPagina($paginaActual - 1, $filtroDisco, $filtroConductor, $filtroFecha)); ?>"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-2"></i> Anterior
                        </a>
                    <?php endif; ?>

                    <?php for ($pagina = $paginaInicial; $pagina <= $paginaFinal; $pagina++): ?>
                        <a href="<?php echo htmlspecialchars(construirUrlPagina($pagina, $filtroDisco, $filtroConductor, $filtroFecha)); ?>"
                           class="inline-flex min-w-10 items-center justify-center rounded-lg px-3 py-2 text-sm font-bold <?php echo $pagina === $paginaActual ? 'bg-blue-600 text-white shadow' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?>"
                           <?php echo $pagina === $paginaActual ? 'aria-current="page"' : ''; ?>>
                            <?php echo $pagina; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($paginaActual < $totalPaginas): ?>
                        <a href="<?php echo htmlspecialchars(construirUrlPagina($paginaActual + 1, $filtroDisco, $filtroConductor, $filtroFecha)); ?>"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            Siguiente <i class="fas fa-chevron-right ml-2"></i>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                </nav>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
    <script>
        if (typeof flatpickr === 'function') {
            flatpickr('#filtroFecha', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                disableMobile: true,
                maxDate: 'today',
                monthSelectorType: 'static'
            });
        }

        (() => {
            if (typeof EventSource === 'undefined') return;

            const tabla = document.getElementById('tablaTurnos');
            const total = document.getElementById('totalTurnos');
            const rango = document.getElementById('rangoTurnos');
            const contenedorPaginacion = document.getElementById('contenedorPaginacion');
            const paginacion = document.getElementById('paginacionTurnos');
            const parametros = new URLSearchParams(window.location.search);
            const streamUrl = new URL('../../Controllers/TurnosStreamController.php', window.location.href);

            ['disco', 'conductor', 'fecha', 'pagina'].forEach((nombre) => {
                const valor = parametros.get(nombre);
                if (valor) streamUrl.searchParams.set(nombre, valor);
            });

            const escapar = (valor) => String(valor ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const urlPagina = (pagina) => {
                const url = new URL(window.location.href);
                url.searchParams.set('pagina', pagina);
                return `${url.pathname}?${url.searchParams.toString()}`;
            };

            const enlacePagina = (pagina, contenido, activo = false) => {
                const clases = activo
                    ? 'bg-blue-600 text-white shadow'
                    : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50';
                return `<a href="${escapar(urlPagina(pagina))}" class="inline-flex min-w-10 items-center justify-center rounded-lg px-3 py-2 text-sm font-bold ${clases}"${activo ? ' aria-current="page"' : ''}>${contenido}</a>`;
            };

            const renderizarPaginacion = (paginaActual, totalPaginas) => {
                if (totalPaginas <= 1) {
                    contenedorPaginacion.classList.add('hidden');
                    paginacion.innerHTML = '';
                    return;
                }

                const inicial = Math.max(1, paginaActual - 2);
                const final = Math.min(totalPaginas, paginaActual + 2);
                let html = '';

                if (paginaActual > 1) {
                    html += enlacePagina(paginaActual - 1, '<i class="fas fa-chevron-left mr-2"></i> Anterior');
                }
                for (let pagina = inicial; pagina <= final; pagina++) {
                    html += enlacePagina(pagina, pagina, pagina === paginaActual);
                }
                if (paginaActual < totalPaginas) {
                    html += enlacePagina(paginaActual + 1, 'Siguiente <i class="fas fa-chevron-right ml-2"></i>');
                }

                paginacion.innerHTML = html;
                contenedorPaginacion.classList.remove('hidden');
            };

            const renderizar = (datos) => {
                total.textContent = datos.total;

                if (datos.total === 0) {
                    rango.textContent = '';
                    rango.classList.add('hidden');
                    tabla.innerHTML = `
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-clock text-3xl mb-3 text-gray-300"></i>
                                <p>No se encontraron turnos con los filtros seleccionados.</p>
                            </td>
                        </tr>`;
                } else {
                    rango.textContent = `Mostrando ${datos.primero}–${datos.ultimo} de ${datos.total}`;
                    rango.classList.remove('hidden');
                    tabla.innerHTML = datos.turnos.map((turno) => `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200">${escapar(turno.disco)}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono font-semibold text-gray-700">${escapar(turno.codigo_conductor || 'Sin código')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">${escapar(turno.fecha_apertura)}</td>
                        </tr>`).join('');
                }

                renderizarPaginacion(datos.pagina, datos.total_paginas);
            };

            const eventos = new EventSource(streamUrl.toString());
            eventos.addEventListener('turnos', (evento) => {
                try {
                    renderizar(JSON.parse(evento.data));
                } catch (error) {
                    console.error('No se pudo actualizar el historial de turnos.', error);
                }
            });

            window.addEventListener('beforeunload', () => eventos.close());
        })();
    </script>
</body>
</html>
