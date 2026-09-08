<?php
// Web/admin/usuarios.php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once '../../Config/conexion.php';
require_once '../../Dao/UsuarioDao.php';

if (empty($_SESSION['csrf_admin_usuarios'])) {
    $_SESSION['csrf_admin_usuarios'] = bin2hex(random_bytes(32));
}

$usuarioDao = new UsuarioDao($conexion);
$usuarios = $usuarioDao->obtenerUsuariosAdministrables();
$roles = $usuarioDao->obtenerRolesActivos();

function etiquetaRolUsuario($rol) {
    return match ($rol) {
        'admin' => 'Administrador',
        'operativo' => 'Operativo',
        'secretaria' => 'Secretaría',
        'socio' => 'Socio',
        'conductor' => 'Conductor',
        default => ucfirst($rol)
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Ejecuttrans</title>
    <link rel="icon" href="../../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="min-h-16 bg-white shadow-sm flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between md:px-8 border-b border-gray-200">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                    Administración de usuarios
                </h2>
                <p class="text-xs text-gray-500">Registro de conductores, socios y personal del sistema</p>
            </div>
            <button id="btnNuevoUsuario" type="button"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-700 transition-colors md:w-auto">
                <i class="fas fa-user-plus mr-2"></i>Crear usuario
            </button>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">
            <section>
                <div class="mb-4 flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500">Usuarios registrados</p>
                        <p class="text-3xl font-bold text-blue-700"><?php echo count($usuarios); ?></p>
                    </div>
                    <div class="relative w-full md:w-80">
                        <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                        <input id="buscarUsuario" type="search" placeholder="Buscar usuario..."
                               class="w-full rounded-lg border border-gray-300 bg-white pl-10 pr-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Cédula</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Nacimiento</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="listaUsuarios" class="bg-white divide-y divide-gray-200">
                            <?php if (empty($usuarios)): ?>
                                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Aún no hay usuarios registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <?php
                                        $codigo = $usuario['codigo_conductor'] ?: $usuario['codigo_socio'];
                                        $estaHabilitado = (int)$usuario['activo'] === 1 && $usuario['estado'] === 'habilitado';
                                        $textoBusqueda = implode(' ', [$usuario['cedula'], $usuario['nombres'], $usuario['apellidos'], $usuario['rol'], $codigo]);
                                    ?>
                                    <tr class="fila-usuario hover:bg-gray-50 transition-colors" data-busqueda="<?php echo htmlspecialchars(mb_strtolower($textoBusqueda)); ?>">
                                        <td class="px-5 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-700"><?php echo htmlspecialchars($usuario['cedula']); ?></td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></p>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-center text-sm text-gray-700"><?php echo htmlspecialchars(date('d/m/Y', strtotime($usuario['fecha_nacimiento']))); ?></td>
                                        <td class="px-5 py-4 whitespace-nowrap text-center text-sm font-mono font-bold text-gray-700"><?php echo htmlspecialchars($codigo ?: '—'); ?></td>
                                        <td class="px-5 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex rounded-full bg-blue-50 border border-blue-200 px-3 py-1 text-xs font-bold text-blue-800"><?php echo htmlspecialchars(etiquetaRolUsuario($usuario['rol'])); ?></span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo $estaHabilitado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $estaHabilitado ? 'Habilitado' : 'Deshabilitado'; ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-center">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" class="btn-editar-usuario inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors"
                                                        title="Editar usuario"
                                                        data-id="<?php echo (int)$usuario['id']; ?>"
                                                        data-nombres="<?php echo htmlspecialchars($usuario['nombres'], ENT_QUOTES); ?>"
                                                        data-apellidos="<?php echo htmlspecialchars($usuario['apellidos'], ENT_QUOTES); ?>"
                                                        data-fecha="<?php echo htmlspecialchars($usuario['fecha_nacimiento'], ENT_QUOTES); ?>"
                                                        data-cedula="<?php echo htmlspecialchars($usuario['cedula'], ENT_QUOTES); ?>"
                                                        data-rol="<?php echo htmlspecialchars($usuario['rol'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <?php if ((int)$usuario['id'] !== (int)$_SESSION['usuario_id']): ?>
                                                    <button type="button" class="btn-estado-usuario inline-flex h-9 w-9 items-center justify-center rounded-lg <?php echo $estaHabilitado ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-green-50 text-green-700 hover:bg-green-100'; ?> transition-colors"
                                                            title="<?php echo $estaHabilitado ? 'Deshabilitar usuario' : 'Habilitar usuario'; ?>"
                                                            data-id="<?php echo (int)$usuario['id']; ?>"
                                                            data-nombre="<?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos'], ENT_QUOTES); ?>"
                                                            data-nuevo-estado="<?php echo $estaHabilitado ? '0' : '1'; ?>">
                                                        <i class="fas <?php echo $estaHabilitado ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="inline-flex h-9 items-center rounded-lg bg-gray-100 px-3 text-xs font-bold text-gray-500" title="Usuario administrador conectado">Tú</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div id="sinUsuarios" class="hidden px-6 py-10 text-center text-gray-500">
                        <i class="fas fa-user-slash text-3xl text-gray-300 mb-2"></i>
                        <p>No se encontraron usuarios.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div id="modalUsuario" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="tituloFormularioUsuario">
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between bg-gradient-to-r from-blue-700 to-blue-900 px-5 py-4 text-white md:px-6">
                <div>
                    <h3 id="tituloFormularioUsuario" class="text-lg font-bold"><i class="fas fa-user-plus mr-2"></i>Crear nuevo usuario</h3>
                    <p class="mt-1 text-sm text-blue-100">Los códigos de conductor y socio se generan automáticamente.</p>
                </div>
                <button id="btnCerrarModalUsuario" type="button" class="ml-4 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-blue-100 hover:bg-white/15 hover:text-white" aria-label="Cerrar formulario">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formCrearUsuario" class="overflow-y-auto p-5 md:p-6">
                <input id="accionUsuario" type="hidden" name="accion" value="crear_usuario">
                <input id="usuarioId" type="hidden" name="usuario_id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_admin_usuarios']); ?>">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="nombres" class="mb-2 block text-sm font-bold text-gray-700">Nombres <span class="text-red-500">*</span></label>
                        <input id="nombres" name="nombres" type="text" maxlength="100" required autocomplete="given-name" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nombres">
                    </div>
                    <div>
                        <label for="apellidos" class="mb-2 block text-sm font-bold text-gray-700">Apellidos <span class="text-red-500">*</span></label>
                        <input id="apellidos" name="apellidos" type="text" maxlength="100" required autocomplete="family-name" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Apellidos">
                    </div>
                    <div>
                        <label for="fechaNacimiento" class="mb-2 block text-sm font-bold text-gray-700">Fecha de nacimiento <span class="text-red-500">*</span></label>
                        <input id="fechaNacimiento" name="fecha_nacimiento" type="date" max="<?php echo date('Y-m-d'); ?>" required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="cedula" class="mb-2 block text-sm font-bold text-gray-700">Cédula <span class="text-red-500">*</span></label>
                        <input id="cedula" name="cedula" type="text" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" required autocomplete="off" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-center font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="10 números">
                    </div>
                    <div class="md:col-span-2">
                        <label for="rol" class="mb-2 block text-sm font-bold text-gray-700">Rol <span class="text-red-500">*</span></label>
                        <select id="rol" name="rol" required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">Seleccione...</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo htmlspecialchars($rol); ?>"><?php echo htmlspecialchars(etiquetaRolUsuario($rol)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <p class="mt-5 text-sm text-gray-500"><i class="fas fa-key mr-1 text-amber-500"></i>La contraseña inicial será el número de cédula.</p>
                <div id="alertaUsuario" class="hidden mt-5 rounded-xl border px-4 py-3 text-sm font-bold" role="alert"></div>

                <div class="mt-6 flex flex-col-reverse justify-end gap-3 border-t border-gray-100 pt-5 sm:flex-row">
                    <button id="btnCancelarEdicion" type="button" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                    <button id="btnGuardarUsuario" type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 transition-colors">
                        <i class="fas fa-save mr-2"></i><span id="textoGuardarUsuario">Guardar usuario</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../Assets/js/admin-usuarios.js?v=2"></script>
</body>
</html>
