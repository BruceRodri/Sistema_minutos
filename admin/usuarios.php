<?php
// admin/usuarios.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../Config/conexion.php';
require_once '../Dao/UsuarioDao.php';

$usuarioDao = new UsuarioDao($conexion);
$listaUsuarios = $usuarioDao->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal - Ejecuttrans</title>
    <link rel="icon" href="../Assets/icons/icon-192x192.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    
    <?php include 'components/sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col overflow-y-auto mt-16 md:mt-0 w-full">
        <header class="h-16 bg-white shadow-sm flex items-center px-4 md:px-8 justify-between border-b border-gray-200">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-gray-800">
                Gestión de Personal
            </h2>
            <button id="btnNuevoUsuario" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white px-5 py-2 rounded-lg shadow-lg transform hover:-translate-y-0.5 transition-all text-sm font-bold flex items-center">
                <i class="fas fa-user-plus mr-2"></i> Nuevo Usuario
            </button>
        </header>

        <div class="p-4 md:p-8 w-full max-w-7xl mx-auto">
            
            <!-- Buscador en tiempo real -->
            <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-100 flex items-center">
                <div class="relative w-full md:w-1/2">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="buscadorUsuarios" placeholder="Buscar por cédula, nombre o rol..." class="w-full pl-10 p-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm">
                </div>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="tablaUsuarios">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Cédula</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol en Sistema</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($listaUsuarios as $user): 
                            // Opacidad para usuarios inactivos
                            $claseFila = ($user['estado'] == 1) ? 'hover:bg-gray-50' : 'bg-gray-50 opacity-60 grayscale-[30%]';
                            
                            // Colores distintivos por rol
                            $colorRol = match($user['rol']) {
                                'admin' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'operador' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'secretaria' => 'bg-pink-100 text-pink-800 border-pink-200',
                                'gerente' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200'
                            };
                        ?>
                        <tr class="fila-usuario <?php echo $claseFila; ?> transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-700 data-cedula">
                                <?php echo htmlspecialchars($user['cedula']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 data-nombre">
                                <?php echo htmlspecialchars($user['nombre']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm data-rol">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border <?php echo $colorRol; ?> uppercase tracking-wide">
                                    <?php echo htmlspecialchars($user['rol']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if($user['estado'] == 1): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1 mt-0.5"></i> Activo</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1 mt-0.5"></i> Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center space-x-3">
                                <?php if($user['id'] !== $_SESSION['usuario_id']): ?>
                                    <button class="btn-editar text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition-colors" 
                                            title="Editar Usuario" 
                                            data-id="<?php echo $user['id']; ?>" 
                                            data-cedula="<?php echo htmlspecialchars($user['cedula']); ?>"
                                            data-nombre="<?php echo htmlspecialchars($user['nombre']); ?>"
                                            data-rol="<?php echo htmlspecialchars($user['rol']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-reset text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 p-2 rounded-lg transition-colors" title="Resetear Clave a Cédula" data-id="<?php echo $user['id']; ?>" data-cedula="<?php echo htmlspecialchars($user['cedula']); ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php if($user['estado'] == 1): ?>
                                        <button class="btn-estado text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors" title="Deshabilitar Acceso" data-id="<?php echo $user['id']; ?>" data-estado="1">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-estado text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 p-2 rounded-lg transition-colors" title="Habilitar Acceso" data-id="<?php echo $user['id']; ?>" data-estado="0">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                        <i class="fas fa-user-shield mr-1"></i> Tú
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Mensaje cuando no hay resultados en el buscador -->
                <div id="noResultados" class="hidden p-8 text-center text-gray-500">
                    <i class="fas fa-user-slash text-4xl mb-3 text-gray-300 block"></i>
                    No se encontraron usuarios que coincidan con la búsqueda.
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Registro -->
    <div id="modalUsuario" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center backdrop-blur-sm transition-all">
        <div class="relative mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white">
            <div class="mb-4 flex justify-between items-center border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800"><i class="fas fa-user-plus text-blue-600 mr-2"></i>Registrar Personal</h3>
                <button type="button" id="btnCerrarModalIcon" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="formUsuario" class="space-y-4">
                <input type="hidden" name="accion" id="accion_modal" value="crear">
                <input type="hidden" name="id_usuario" id="id_usuario_modal" value="">
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1">Cédula <span class="text-red-500">*</span></label>
                    <input type="text" name="cedula" id="cedula_nuevo" required pattern="\d{10}" maxlength="10" placeholder="10 dígitos" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" id="nombre_nuevo" required class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all uppercase">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1">Rol del Sistema <span class="text-red-500">*</span></label>
                    <select name="rol" required class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                        <option value="" disabled selected>Seleccione un rol...</option>
                        <option value="operador">Operador</option>
                        <option value="secretaria">Secretaría</option>
                        <option value="gerente">Gerente</option>
                        <option value="admin">Administrador (Acceso Total)</option>
                    </select>
                </div>

                <!-- NUEVA SECCIÓN: Asignación de Módulos (Permisos) -->
                <div class="border-t border-gray-200 pt-3 mt-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-shield-alt text-blue-500 mr-1"></i> Asignación de Módulos
                        <span class="block text-xs text-gray-500 font-normal mt-0.5">Selecciona a qué secciones tendrá acceso este usuario.</span>
                    </label>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                        <!-- Permiso: Resoluciones -->
                        <label class="flex items-center space-x-3 p-2 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="permisos[]" value="1" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Resoluciones</span>
                        </label>
                        <!-- Permiso: Actas -->
                        <label class="flex items-center space-x-3 p-2 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="permisos[]" value="2" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Actas de Reunión</span>
                        </label>
                        <!-- Permiso: Socios -->
                        <label class="flex items-center space-x-3 p-2 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="permisos[]" value="3" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Socios</span>
                        </label>
                        <!-- Permiso: Vehículos -->
                        <label class="flex items-center space-x-3 p-2 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="permisos[]" value="4" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Vehículos</span>
                        </label>
                        <!-- Permiso: Conductores -->
                        <label class="flex items-center space-x-3 p-2 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="permisos[]" value="5" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Conductores</span>
                        </label>
                    </div>
                </div>

                <div id="modalAlert" class="hidden text-sm text-center p-3 rounded-lg font-bold"></div>
                
                <div class="flex items-center justify-end mt-6 space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" id="btnCerrarModal" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">Cancelar</button>
                    <button type="submit" id="btnGuardar" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../Assets/js/usuarios.js"></script>
</body>
</html>
