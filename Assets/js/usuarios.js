// Assets/js/usuarios.js

document.addEventListener('DOMContentLoaded', () => {

    // --- BUSCADOR EN TIEMPO REAL ---
    const buscador = document.getElementById('buscadorUsuarios');
    if(buscador) {
        buscador.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            const filas = document.querySelectorAll('.fila-usuario');
            let visibles = 0;

            filas.forEach(fila => {
                const cedula = fila.querySelector('.data-cedula').textContent.toLowerCase();
                const nombre = fila.querySelector('.data-nombre').textContent.toLowerCase();
                const rol = fila.querySelector('.data-rol').textContent.toLowerCase();

                if(cedula.includes(term) || nombre.includes(term) || rol.includes(term)) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            // Mostrar mensaje si no hay coincidencias
            const noResultados = document.getElementById('noResultados');
            if(visibles === 0) {
                noResultados.classList.remove('hidden');
            } else {
                noResultados.classList.add('hidden');
            }
        });
    }

    // --- LÓGICA DEL MODAL DE REGISTRO ---
    const modal = document.getElementById('modalUsuario');
    const btnNuevo = document.getElementById('btnNuevoUsuario');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCerrarIcon = document.getElementById('btnCerrarModalIcon');
    const formUsuario = document.getElementById('formUsuario');
    const modalAlert = document.getElementById('modalAlert');
    const btnGuardar = document.getElementById('btnGuardar');

    // Función compartida para abrir el modal
    const abrirModal = (modo, data = null) => {
        modalAlert.classList.add('hidden');
        const titulo = modal.querySelector('h3');
        const inputAccion = document.getElementById('accion_modal');
        const inputId = document.getElementById('id_usuario_modal');
        const btnGuardarTexto = document.getElementById('btnGuardar');

        if (modo === 'crear') {
            formUsuario.reset();
            titulo.innerHTML = '<i class="fas fa-user-plus text-blue-600 mr-2"></i>Registrar Personal';
            inputAccion.value = 'crear';
            inputId.value = '';
            btnGuardarTexto.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar';
            // Desmarcar todos los checkboxes
            document.querySelectorAll('input[name="permisos[]"]').forEach(cb => cb.checked = false);
        } else if (modo === 'editar') {
            titulo.innerHTML = '<i class="fas fa-user-edit text-orange-600 mr-2"></i>Editar Personal';
            inputAccion.value = 'editar';
            inputId.value = data.id;
            
            // Llenar campos de texto
            document.getElementById('cedula_nuevo').value = data.cedula;
            document.getElementById('nombre_nuevo').value = data.nombre;
            document.querySelector(`select[name="rol"]`).value = data.rol;
            btnGuardarTexto.innerHTML = '<i class="fas fa-save mr-2"></i> Actualizar';

            // Petición AJAX rápida para obtener los permisos actuales del usuario y marcarlos
            fetch(`../Controllers/UsuarioController.php?accion=obtener_permisos&id=${data.id}`)
                .then(res => res.json())
                .then(permisos => {
                    document.querySelectorAll('input[name="permisos[]"]').forEach(cb => {
                        // cb.value es 1, 2, 3... (el ID del módulo en la BD)
                        cb.checked = permisos.includes(parseInt(cb.value)); 
                    });
                })
                .catch(err => console.error("Error al obtener permisos:", err));
        }
        modal.classList.remove('hidden');
    };

    const cerrarModal = () => {
        modal.classList.add('hidden');
    };

    if(btnNuevo) btnNuevo.addEventListener('click', () => abrirModal('crear'));
    if(btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if(btnCerrarIcon) btnCerrarIcon.addEventListener('click', cerrarModal);

    // Lógica para botones de Editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = {
                id: this.getAttribute('data-id'),
                cedula: this.getAttribute('data-cedula'),
                nombre: this.getAttribute('data-nombre'),
                rol: this.getAttribute('data-rol')
            };
            abrirModal('editar', data);
        });
    });

    // Validar Cédula y Nombres en el modal
    const inputCedula = document.getElementById('cedula_nuevo');
    const inputNombre = document.getElementById('nombre_nuevo');
    
    if(inputCedula) {
        inputCedula.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        });
    }
    
    if(inputNombre) {
        inputNombre.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-ZÁÉÍÓÚÑ\s]/g, '');
        });
    }

    // Envío del Formulario (Crear Usuario)
    if(formUsuario) {
        formUsuario.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if(inputCedula.value.length !== 10) {
                modalAlert.className = 'text-sm text-center mb-4 p-3 rounded-lg bg-red-100 text-red-800 font-bold';
                modalAlert.textContent = "La cédula debe tener 10 dígitos exactos.";
                modalAlert.classList.remove('hidden');
                return;
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
            modalAlert.classList.add('hidden');

            const formData = new FormData(formUsuario);

            try {
                const response = await fetch('../Controllers/UsuarioController.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    modalAlert.className = 'text-sm text-center mb-4 p-3 rounded-lg bg-green-100 text-green-800 font-bold';
                    modalAlert.textContent = data.message;
                    modalAlert.classList.remove('hidden');
                    
                    // Recargar la página después de un momento para ver los cambios
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    modalAlert.className = 'text-sm text-center mb-4 p-3 rounded-lg bg-red-100 text-red-800 font-bold';
                    modalAlert.textContent = data.message;
                    modalAlert.classList.remove('hidden');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar';
                }
            } catch (error) {
                modalAlert.className = 'text-sm text-center mb-4 p-3 rounded-lg bg-red-100 text-red-800 font-bold';
                modalAlert.textContent = 'Error de conexión con el servidor.';
                modalAlert.classList.remove('hidden');
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar';
            }
        });
    }

    // --- ACCIONES EN LA TABLA ---

    // 1. Resetear Clave
    document.querySelectorAll('.btn-reset').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            const cedula = this.getAttribute('data-cedula');
            
            if(confirm(`¿Estás seguro de resetear la contraseña del usuario con cédula ${cedula}? Su nueva clave temporal será su misma cédula.`)) {
                const formData = new FormData();
                formData.append('accion', 'resetear_clave');
                formData.append('id', id);
                formData.append('cedula', cedula);

                try {
                    const response = await fetch('../Controllers/UsuarioController.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    alert(data.message);
                } catch (error) {
                    alert('Error de conexión con el servidor.');
                }
            }
        });
    });

    // 2. Cambiar Estado (Deshabilitar / Habilitar)
    document.querySelectorAll('.btn-estado').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            const estadoActual = this.getAttribute('data-estado');
            const accionTexto = estadoActual === '1' ? 'deshabilitar' : 'habilitar';
            
            if(confirm(`¿Estás seguro de ${accionTexto} a este usuario?`)) {
                const formData = new FormData();
                formData.append('accion', 'cambiar_estado');
                formData.append('id', id);
                formData.append('estado', estadoActual);

                try {
                    const response = await fetch('../Controllers/UsuarioController.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    
                    if(data.status === 'success') {
                        window.location.reload(); 
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    alert('Error de conexión con el servidor.');
                }
            }
        });
    });
});