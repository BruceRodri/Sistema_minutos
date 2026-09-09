<?php
// Web/admin/dashboard.php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'secretaria', 'operativo'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/ValoresDao.php';

function contar($conexion, $sql) {
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function sumar($conexion, $sql) {
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $valor = $stmt->fetchColumn();
    return $valor === null ? 0.0 : (float)$valor;
}

// ---- Conteos globales ----
$totalSocios = contar($conexion, "SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON u.rol_id = r.id WHERE r.nombre = 'socio'");
$totalConductores = contar($conexion, "SELECT COUNT(*) FROM usuario u INNER JOIN rol r ON u.rol_id = r.id WHERE r.nombre = 'conductor'");
$totalUsuarios = contar($conexion, "SELECT COUNT(*) FROM usuario");

$totalBuses = contar($conexion, "SELECT COUNT(*) FROM bus");
$busesActivos = contar($conexion, "SELECT COUNT(*) FROM bus WHERE activo = 1");
$sociosConBuses = contar($conexion, "SELECT COUNT(DISTINCT usuario_id) FROM usuario_bus WHERE activo = 1");
$busesAsignados = contar($conexion, "SELECT COUNT(*) FROM usuario_bus WHERE activo = 1");

// ---- Turnos ----
$turnosHoy = contar($conexion, "SELECT COUNT(*) FROM turno t INNER JOIN bus b ON t.bus_id = b.id WHERE t.fecha = CURDATE()");
$turnosHoyAbiertos = contar($conexion, "SELECT COUNT(*) FROM turno WHERE fecha = CURDATE() AND activo = 1");
$turnosHoyPendientes = contar($conexion, "SELECT COUNT(DISTINCT t.id)
    FROM turno t
    INNER JOIN bus b ON t.bus_id = b.id
    INNER JOIN obligacion_pago o
        ON CAST(b.disco AS UNSIGNED) = CAST(o.disco AS UNSIGNED)
       AND o.pagado = 0 AND o.activo = 1 AND o.valor > 0
    WHERE t.fecha = CURDATE()");
$turnosTotales = contar($conexion, "SELECT COUNT(*) FROM turno");

// ---- Pagos ----
$conteos = ['en_espera' => 0, 'aprobado' => 0, 'anulado' => 0];
foreach ($conexion->query("SELECT estado, COUNT(*) AS cantidad FROM pago WHERE activo = 1 GROUP BY estado") as $fila) {
    $conteos[$fila['estado']] = (int)$fila['cantidad'];
}

$pagosHoy = contar($conexion, "SELECT COUNT(*) FROM pago WHERE fecha_pago = CURDATE() AND activo = 1");
$montoPagosHoy = sumar($conexion, "SELECT SUM(monto_total) FROM pago WHERE fecha_pago = CURDATE() AND activo = 1");
$pagosTotales = contar($conexion, "SELECT COUNT(*) FROM pago WHERE activo = 1");

// ---- Valores diarios ----
$valoresDao = new ValoresDao($conexion);
$valoresSubido = $valoresDao->archivoExiste();
$valoresFilas = $valoresSubido ? count($valoresDao->leerFilas()) : 0;
$valoresFecha = $valoresSubido ? $valoresDao->fechaSubida() : '';

$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$nombreAdmin = explode(' ', $_SESSION['nombre'] ?? 'Administrador')[0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Minutos</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-xs text-gray-500"><?php echo $dias[(int)date('w')] . ' ' . date('d/m/Y'); ?></p>
            </div>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 font-bold px-4 py-2 rounded-xl">
                <i class="fas fa-user-shield mr-2"></i><?php echo htmlspecialchars($nombreAdmin); ?>
            </div>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">

            <?php if (!$valoresSubido): ?>
            <div class="mb-6 bg-amber-50 border-2 border-amber-300 text-amber-800 rounded-2xl p-5 flex items-start gap-4">
                <i class="fas fa-triangle-exclamation text-3xl mt-1"></i>
                <div>
                    <p class="text-lg font-bold">No hay archivo de valores diarios</p>
                    <p class="text-base">Los conductores no podrán abrir turnos hasta que subas el Excel (DISCO, FECHA, VALOR, RUTA).</p>
                    <a href="valores.php" class="mt-2 inline-block font-bold text-blue-700 underline hover:text-blue-900">Ir a Valores Diarios</a>
                </div>
            </div>
            <?php endif; ?>

            <h2 class="text-lg font-bold text-gray-700 mb-3">Estado de pagos</h2>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                <a href="socios.php" class="group bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-3xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-id-card text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">SOCIOS</span>
                    </div>
                    <p class="text-6xl font-extrabold text-white mt-6"><?php echo $totalSocios; ?></p>
                    <p class="text-white/80 text-lg mt-1">socios registrados</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-link mr-1"></i><?php echo $sociosConBuses; ?> con buses asignados</p>
                </a>

                <a href="usuarios.php" class="group bg-gradient-to-br from-purple-500 to-purple-700 rounded-3xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-user-tie text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">CONDUCTORES</span>
                    </div>
                    <p class="text-6xl font-extrabold text-white mt-6"><?php echo $totalConductores; ?></p>
                    <p class="text-white/80 text-lg mt-1">conductores activos</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-users mr-1"></i><?php echo $totalUsuarios; ?> usuarios en total</p>
                </a>

                <a href="buses.php" class="group bg-gradient-to-br from-blue-500 to-blue-700 rounded-3xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-bus text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">DISCOS</span>
                    </div>
                    <p class="text-6xl font-extrabold text-white mt-6"><?php echo $totalBuses; ?></p>
                    <p class="text-white/80 text-lg mt-1">discos / buses</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-check-circle mr-1"></i><?php echo $busesActivos; ?> activos · <i class="fas fa-link mr-1"></i><?php echo $busesAsignados; ?> asignados</p>
                </a>

                <a href="turnos.php" class="group bg-gradient-to-br from-cyan-500 to-cyan-700 rounded-3xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-clock text-white/40 text-5xl"></i>
                        <span class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">TURNOS HOY</span>
                    </div>
                    <p class="text-6xl font-extrabold text-white mt-6"><?php echo $turnosHoy; ?></p>
                    <p class="text-white/80 text-lg mt-1">turnos de hoy</p>
                    <p class="text-white/70 text-sm mt-3"><i class="fas fa-circle-notch mr-1"></i><?php echo $turnosHoyAbiertos; ?> abiertos · <i class="fas fa-hourglass-half mr-1"></i><?php echo $turnosHoyPendientes; ?> por pagar</p>
                </a>
            </div>

            <!-- Fila secundaria -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-6">

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-bold text-gray-700 text-lg">Historial de turnos</p>
                        <i class="fas fa-list-check text-cyan-600 text-3xl"></i>
                    </div>
                    <p class="text-5xl font-extrabold text-cyan-700"><?php echo $turnosTotales; ?></p>
                    <p class="text-gray-500 text-base mt-1">turnos registrados</p>
                </div>

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-bold text-gray-700 text-lg">Pagos de hoy</p>
                        <i class="fas fa-money-bill-wave text-green-600 text-3xl"></i>
                    </div>
                    <p class="text-5xl font-extrabold text-green-700"><?php echo $pagosHoy; ?></p>
                    <p class="text-gray-500 text-base mt-1">por $ <?php echo number_format($montoPagosHoy, 2, '.', ','); ?></p>
                </div>

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-bold text-gray-700 text-lg">Valores diarios</p>
                        <i class="fas fa-file-excel text-emerald-600 text-3xl"></i>
                    </div>
                    <?php if ($valoresSubido): ?>
                        <p class="text-4xl font-extrabold text-emerald-700"><?php echo $valoresFilas; ?></p>
                        <p class="text-gray-500 text-base mt-1">filas leídas · subido <?php echo $valoresFecha; ?></p>
                    <?php else: ?>
                        <p class="text-4xl font-extrabold text-amber-600">No subido</p>
                        <p class="text-gray-500 text-base mt-1">Sube el Excel para habilitar los turnos.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimos registros -->
            <div class="mt-6 bg-white rounded-3xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800 text-xl">Resumen del sistema</h3>
                    <span class="text-sm text-gray-400"><?php echo $pagosTotales; ?> pagos registrados en total</span>
                </div>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="bg-slate-50 rounded-2xl py-4">
                        <dt class="text-gray-500 text-sm font-semibold">Socios</dt>
                        <dd class="text-3xl font-extrabold text-indigo-700"><?php echo $totalSocios; ?></dd>
                    </div>
                    <div class="bg-slate-50 rounded-2xl py-4">
                        <dt class="text-gray-500 text-sm font-semibold">Conductores</dt>
                        <dd class="text-3xl font-extrabold text-purple-700"><?php echo $totalConductores; ?></dd>
                    </div>
                    <div class="bg-slate-50 rounded-2xl py-4">
                        <dt class="text-gray-500 text-sm font-semibold">Discos / Buses</dt>
                        <dd class="text-3xl font-extrabold text-blue-700"><?php echo $totalBuses; ?></dd>
                    </div>
                    <div class="bg-slate-50 rounded-2xl py-4">
                        <dt class="text-gray-500 text-sm font-semibold">Usuarios totales</dt>
                        <dd class="text-3xl font-extrabold text-cyan-700"><?php echo $totalUsuarios; ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </main>
</body>
</html>