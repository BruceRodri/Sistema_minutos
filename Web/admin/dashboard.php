<?php
// Web/admin/dashboard.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Config/permisos.php';
exigirPermisoModulo($conexion, 'web_dashboard', '../../index.php');
require_once '../../Dao/ValoresDao.php';

function contar($conexion, $sql, $parametros = []) {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    return (int)$stmt->fetchColumn();
}

function sumar($conexion, $sql, $parametros = []) {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    $valor = $stmt->fetchColumn();
    return $valor === null ? 0.0 : (float)$valor;
}

$fechaSeleccionada = trim((string)($_GET['fecha'] ?? ''));
$fechaValida = DateTime::createFromFormat('!Y-m-d', $fechaSeleccionada);
if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaSeleccionada) {
    $fechaSeleccionada = date('Y-m-d');
}

// ---- Conteos globales ----
$totalSocios = contar($conexion, "SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON u.rol_id = r.id WHERE r.nombre = 'socio'");
$totalConductores = contar($conexion, "SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON u.rol_id = r.id WHERE r.nombre = 'conductor'");
$totalUsuarios = contar($conexion, "SELECT COUNT(*) FROM usuario");

$totalBuses = contar($conexion, "SELECT COUNT(*) FROM bus");
$busesActivos = contar($conexion, "SELECT COUNT(*) FROM bus WHERE activo = 1");
$sociosConBuses = contar($conexion, "SELECT COUNT(DISTINCT usuario_id) FROM usuario_bus WHERE activo = 1");
$busesAsignados = contar($conexion, "SELECT COUNT(*) FROM usuario_bus WHERE activo = 1");

// ---- Turnos (según la fecha seleccionada) ----
$turnosFecha = contar($conexion, "SELECT COUNT(*) FROM turno t INNER JOIN bus b ON t.bus_id = b.id WHERE t.fecha = :fecha", [':fecha' => $fechaSeleccionada]);
$turnosAbiertos = contar($conexion, "SELECT COUNT(*) FROM turno WHERE fecha = :fecha AND activo = 1", [':fecha' => $fechaSeleccionada]);
$turnosPendientes = contar($conexion, "SELECT COUNT(DISTINCT t.id)
    FROM turno t
    INNER JOIN bus b ON t.bus_id = b.id
    INNER JOIN obligacion_pago o
        ON CAST(b.disco AS UNSIGNED) = CAST(o.disco AS UNSIGNED)
       AND o.pagado = 0 AND o.pago_id IS NULL AND o.activo = 1 AND o.valor > 0
    WHERE t.fecha = :fecha", [':fecha' => $fechaSeleccionada]);
$turnosHistorial = contar($conexion, "SELECT COUNT(*) FROM turno WHERE fecha = :fecha", [':fecha' => $fechaSeleccionada]);

// ---- Pagos (según la fecha seleccionada) ----
$conteos = ['en_espera' => 0, 'aprobado' => 0, 'anulado' => 0];
$stmtConteos = $conexion->prepare("SELECT estado, COUNT(*) AS cantidad FROM pago WHERE activo = 1 AND fecha_pago = :fecha GROUP BY estado");
$stmtConteos->execute([':fecha' => $fechaSeleccionada]);
foreach ($stmtConteos as $fila) {
    $conteos[$fila['estado']] = (int)$fila['cantidad'];
}

$pagosFecha = contar($conexion, "SELECT COUNT(*) FROM pago WHERE fecha_pago = :fecha AND activo = 1", [':fecha' => $fechaSeleccionada]);
$montoPagosFecha = sumar($conexion, "SELECT SUM(monto_total) FROM pago WHERE fecha_pago = :fecha AND activo = 1", [':fecha' => $fechaSeleccionada]);

// ---- Valores diarios (según la fecha seleccionada) ----
$valoresDao = new ValoresDao($conexion);
$valoresSubido = $valoresDao->archivoExiste();
$valoresFecha = $valoresDao->fechaSubida();
$valoresFilasFecha = $valoresSubido
    ? count($valoresDao->obtenerFilasFiltradas('', $fechaSeleccionada, '', ''))
    : 0;

$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$tsFecha = strtotime($fechaSeleccionada);
$nombreAdmin = explode(' ', $_SESSION['nombre'] ?? 'Administrador')[0];
$esHoy = $fechaSeleccionada === date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Minutos</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="/Assets/icons/icon-192x192.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <style>
        .flatpickr-calendar { border: 0; border-radius: 1rem; overflow: hidden; box-shadow: 0 20px 45px rgba(15, 23, 42, .2); font-family: inherit; }
        .flatpickr-calendar.arrowTop::before, .flatpickr-calendar.arrowTop::after { border-bottom-color: #2563eb; }
        .flatpickr-months { align-items: center; padding: .65rem .5rem; background: linear-gradient(135deg, #2563eb, #1e3a8a); }
        .flatpickr-months .flatpickr-month, .flatpickr-current-month, .flatpickr-current-month .flatpickr-monthDropdown-months, .flatpickr-current-month input.cur-year { color: #fff; fill: #fff; }
        .flatpickr-current-month .flatpickr-monthDropdown-months, .flatpickr-current-month .flatpickr-monthDropdown-months:hover, .flatpickr-current-month input.cur-year:hover { background: transparent; }
        .flatpickr-monthDropdown-month { color: #1f2937; }
        .flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month { top: .55rem; padding: .5rem; color: #fff; fill: #fff; border-radius: .5rem; }
        .flatpickr-months .flatpickr-prev-month:hover, .flatpickr-months .flatpickr-next-month:hover { background: rgba(255, 255, 255, .15); }
        .flatpickr-weekdays { padding-top: .45rem; background: #eff6ff; }
        span.flatpickr-weekday { color: #1e40af; font-weight: 700; background: #eff6ff; }
        .flatpickr-days { padding: .45rem; }
        .flatpickr-day { border-radius: .65rem; color: #374151; font-weight: 600; }
        .flatpickr-day:hover, .flatpickr-day:focus { border-color: #dbeafe; background: #dbeafe; color: #1d4ed8; }
        .flatpickr-day.today { border-color: #60a5fa; color: #1d4ed8; }
        .flatpickr-day.selected, .flatpickr-day.selected:hover, .flatpickr-day.selected:focus { border-color: #2563eb; background: #2563eb; color: #fff; box-shadow: 0 5px 12px rgba(37, 99, 235, .3); }
        .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay, .flatpickr-day.flatpickr-disabled { color: #cbd5e1; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between gap-3 border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-xs text-gray-500">
                    <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                    <?php echo $dias[(int)date('w', $tsFecha)] . ' ' . date('d/m/Y', $tsFecha); ?>
                    <?php echo $esHoy ? '(hoy)' : ''; ?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <form method="GET" action="dashboard.php" class="flex items-center gap-2" id="formFechaDashboard">
                    <label for="fechaDashboard" class="hidden sm:inline text-xs font-bold text-gray-500">Fecha:</label>
                    <input type="text" id="fechaDashboard" name="fecha" value="<?php echo htmlspecialchars($fechaSeleccionada); ?>" readonly aria-label="Filtrar dashboard por fecha"
                           class="w-32 cursor-pointer rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-center text-sm font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    <?php if (!$esHoy): ?>
                        <a href="dashboard.php" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50" title="Volver a hoy">
                            <i class="fas fa-calendar-day"></i>
                        </a>
                    <?php endif; ?>
                </form>
                <div class="bg-blue-50 border border-blue-200 text-blue-700 font-bold px-4 py-2 rounded-xl">
                    <i class="fas fa-user-shield mr-2"></i><?php echo htmlspecialchars($nombreAdmin); ?>
                </div>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <?php if (!$valoresSubido): ?>
            <div class="mb-6 bg-amber-50 border-2 border-amber-300 text-amber-800 rounded-2xl p-5 flex items-start gap-4">
                <i class="fas fa-triangle-exclamation text-3xl mt-1"></i>
                <div>
                    <p class="text-lg font-bold">Hoy no se ha subido el archivo Excel</p>
                    <p class="text-base">Sube el archivo del día (DISCO, FECHA, VALOR, RUTA). Los valores de cargas anteriores se conservan en la base de datos.</p>
                    <a href="valores.php" class="mt-2 inline-block font-bold text-blue-700 underline hover:text-blue-900">Ir a Valores Diarios</a>
                </div>
            </div>
            <?php endif; ?>

            <h2 class="text-lg font-bold text-gray-700 mb-3">Estado de pagos · <?php echo date('d/m/Y', $tsFecha); ?></h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-clock text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-3xl font-bold text-amber-600"><?php echo $conteos['en_espera']; ?></p>
                        <p class="text-sm font-semibold text-gray-500">En espera</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-green-200 shadow-sm p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-circle-check text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-3xl font-bold text-green-600"><?php echo $conteos['aprobado']; ?></p>
                        <p class="text-sm font-semibold text-gray-500">Aprobados</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-red-200 shadow-sm p-5 flex items-center gap-4">
                    <span class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-circle-xmark text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-3xl font-bold text-red-600"><?php echo $conteos['anulado']; ?></p>
                        <p class="text-sm font-semibold text-gray-500">Anulados</p>
                    </div>
                </div>
            </div>

            <!-- Tarjetas principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">

                <a href="socios.php" class="order-2 lg:col-span-3 group bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-3xl p-5 min-h-64 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-id-card text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">SOCIOS</span>
                    </div>
                    <p class="text-5xl xl:text-4xl font-extrabold text-white mt-6"><?php echo $totalSocios; ?></p>
                    <p class="text-white/80 text-base mt-1">socios registrados</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-link mr-1"></i><?php echo $sociosConBuses; ?> con buses asignados</p>
                </a>

                <a href="usuarios.php" class="order-2 lg:col-span-3 group bg-gradient-to-br from-purple-500 to-purple-700 rounded-3xl p-5 min-h-64 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-user-tie text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">CONDUCTORES</span>
                    </div>
                    <p class="text-5xl xl:text-4xl font-extrabold text-white mt-6"><?php echo $totalConductores; ?></p>
                    <p class="text-white/80 text-base mt-1">conductores activos</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-users mr-1"></i><?php echo $totalUsuarios; ?> usuarios en total</p>
                </a>

                <a href="buses.php" class="order-2 lg:col-span-3 group bg-gradient-to-br from-blue-500 to-blue-700 rounded-3xl p-5 min-h-64 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-bus text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">DISCOS</span>
                    </div>
                    <p class="text-5xl xl:text-4xl font-extrabold text-white mt-6"><?php echo $totalBuses; ?></p>
                    <p class="text-white/80 text-base mt-1">discos / buses</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-check-circle mr-1"></i><?php echo $busesActivos; ?> activos · <i class="fas fa-link mr-1"></i><?php echo $busesAsignados; ?> asignados</p>
                </a>

                <a href="turnos.php" class="order-2 lg:col-span-3 group bg-gradient-to-br from-cyan-500 to-cyan-700 rounded-3xl p-5 min-h-64 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-clock text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">TURNOS</span>
                    </div>
                    <p class="text-5xl xl:text-4xl font-extrabold text-white mt-6"><?php echo $turnosFecha; ?></p>
                    <p class="text-white/80 text-base mt-1">turnos del día</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-circle-notch mr-1"></i><?php echo $turnosAbiertos; ?> abiertos · <i class="fas fa-hourglass-half mr-1"></i><?php echo $turnosPendientes; ?> por pagar</p>
                </a>

                <div class="order-1 lg:col-span-4 bg-gradient-to-br from-white to-cyan-50 rounded-3xl p-5 shadow-sm border border-cyan-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-bold text-gray-700 text-lg">Historial de turnos</p>
                        <i class="fas fa-list-check text-cyan-600 text-3xl"></i>
                    </div>
                    <p class="text-5xl xl:text-4xl font-extrabold text-cyan-700"><?php echo $turnosHistorial; ?></p>
                    <p class="text-gray-500 text-base mt-1">turnos del <?php echo date('d/m/Y', $tsFecha); ?></p>
                </div>

                <div class="order-1 lg:col-span-4 bg-gradient-to-br from-white to-green-50 rounded-3xl p-5 shadow-sm border border-green-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-bold text-gray-700 text-lg">Pagos del día</p>
                        <i class="fas fa-money-bill-wave text-green-600 text-3xl"></i>
                    </div>
                    <p class="text-5xl xl:text-4xl font-extrabold text-green-700"><?php echo $pagosFecha; ?></p>
                    <p class="text-gray-500 text-base mt-1">por $ <?php echo number_format($montoPagosFecha, 2, '.', ','); ?></p>
                </div>

                <div class="order-1 lg:col-span-4 bg-gradient-to-br from-white to-emerald-50 rounded-3xl p-5 shadow-sm border border-emerald-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-bold text-gray-700 text-lg">Valores diarios</p>
                        <i class="fas fa-file-excel text-emerald-600 text-3xl"></i>
                    </div>
                    <?php if ($valoresSubido): ?>
                        <p class="text-4xl font-extrabold text-emerald-700"><?php echo $valoresFilasFecha; ?></p>
                        <p class="text-gray-500 text-base mt-1">filas del <?php echo date('d/m/Y', $tsFecha); ?> · subido <?php echo $valoresFecha; ?></p>
                    <?php else: ?>
                        <p class="text-4xl font-extrabold text-amber-600">No subido</p>
                        <p class="text-gray-500 text-base mt-1">Sube el Excel para habilitar los turnos.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
    <script>
        const formularioFecha = document.getElementById('formFechaDashboard');
        if (typeof flatpickr === 'function' && formularioFecha) {
            flatpickr('#fechaDashboard', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                disableMobile: true,
                maxDate: 'today',
                monthSelectorType: 'static',
                onChange: (fechasSeleccionadas) => {
                    if (fechasSeleccionadas.length) formularioFecha.requestSubmit();
                }
            });
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registrado correctamente.', reg))
                    .catch(err => console.log('Falló el registro del Service Worker.', err));
            });
        }
    </script>
<script>
    // Actualizar el aviso también si el dashboard permanece abierto al cierre del día.
    setInterval(async () => {
        if (document.hidden) return;
        try {
            const respuesta = await fetch('../../Controllers/ValoresStreamController.php?consulta=1', {cache: 'no-store'});
            const datos = await respuesta.json();
            if (typeof datos.archivo_existe === 'boolean' && datos.archivo_existe !== <?php echo json_encode($valoresSubido); ?>) location.reload();
        } catch (_) {}
    }, 15000);
</script>
</body>
</html>
