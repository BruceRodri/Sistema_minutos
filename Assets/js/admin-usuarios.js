// Assets/js/admin-usuarios.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearUsuario');
    const boton = document.getElementById('btnGuardarUsuario');
    const alerta = document.getElementById('alertaUsuario');
    const buscador = document.getElementById('buscarUsuario');
    const sinUsuarios = document.getElementById('sinUsuarios');
    const accion = document.getElementById('accionUsuario');
    const usuarioId = document.getElementById('usuarioId');
    const titulo = document.getElementById('tituloFormularioUsuario');
    const textoGuardar = document.getElementById('textoGuardarUsuario');
    const cancelarEdicion = document.getElementById('btnCancelarEdicion');
    const token = form?.querySelector('[name="csrf_token"]')?.value || '';

    const mostrarAlerta = (mensaje, tipo) => {
        alerta.textContent = mensaje;
        alerta.className = tipo === 'success'
            ? 'mt-5 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm font-bold text-green-800'
            : 'mt-5 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-bold text-red-800';
    };

    const prepararCreacion = () => {
        form.reset();
        accion.value = 'crear_usuario';
        usuarioId.value = '';
        titulo.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Crear nuevo usuario';
        textoGuardar.textContent = 'Guardar usuario';
        cancelarEdicion.classList.add('hidden');
        alerta.classList.add('hidden');
    };

    if (form) {
        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            if (!form.reportValidity()) return;

            boton.disabled = true;
            alerta.classList.add('hidden');

            try {
                const respuesta = await fetch('../../Controllers/AdminUsuarioController.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                const datos = await respuesta.json();
                mostrarAlerta(datos.message || 'No se recibió una respuesta válida.', datos.status);

                if (datos.status === 'success') {
                    window.setTimeout(() => window.location.reload(), 1300);
                }
            } catch (error) {
                console.error(error);
                mostrarAlerta('No se pudo conectar con el servidor.', 'error');
            } finally {
                boton.disabled = false;
            }
        });
    }

    document.querySelectorAll('.btn-editar-usuario').forEach((botonEditar) => {
        botonEditar.addEventListener('click', () => {
            accion.value = 'editar_usuario';
            usuarioId.value = botonEditar.dataset.id;
            document.getElementById('nombres').value = botonEditar.dataset.nombres;
            document.getElementById('apellidos').value = botonEditar.dataset.apellidos;
            document.getElementById('fechaNacimiento').value = botonEditar.dataset.fecha;
            document.getElementById('cedula').value = botonEditar.dataset.cedula;
            document.getElementById('rol').value = botonEditar.dataset.rol;
            titulo.innerHTML = '<i class="fas fa-user-pen mr-2"></i>Editar usuario';
            textoGuardar.textContent = 'Guardar cambios';
            cancelarEdicion.classList.remove('hidden');
            alerta.classList.add('hidden');
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    cancelarEdicion?.addEventListener('click', prepararCreacion);

    document.querySelectorAll('.btn-estado-usuario').forEach((botonEstado) => {
        botonEstado.addEventListener('click', async () => {
            const habilitar = botonEstado.dataset.nuevoEstado === '1';
            const verbo = habilitar ? 'habilitar' : 'deshabilitar';
            if (!window.confirm(`¿Desea ${verbo} a ${botonEstado.dataset.nombre}?`)) return;

            botonEstado.disabled = true;
            const datosEnvio = new FormData();
            datosEnvio.append('accion', 'cambiar_estado');
            datosEnvio.append('usuario_id', botonEstado.dataset.id);
            datosEnvio.append('nuevo_estado', botonEstado.dataset.nuevoEstado);
            datosEnvio.append('csrf_token', token);

            try {
                const respuesta = await fetch('../../Controllers/AdminUsuarioController.php', {
                    method: 'POST',
                    body: datosEnvio
                });
                const datos = await respuesta.json();
                if (datos.status === 'success') {
                    window.location.reload();
                    return;
                }
                window.alert(datos.message || 'No se pudo cambiar el estado.');
            } catch (error) {
                console.error(error);
                window.alert('No se pudo conectar con el servidor.');
            } finally {
                botonEstado.disabled = false;
            }
        });
    });

    if (buscador) {
        buscador.addEventListener('input', () => {
            const termino = buscador.value.trim().toLocaleLowerCase('es');
            const filas = [...document.querySelectorAll('.fila-usuario')];
            let visibles = 0;

            filas.forEach((fila) => {
                const coincide = fila.dataset.busqueda.includes(termino);
                fila.classList.toggle('hidden', !coincide);
                if (coincide) visibles++;
            });

            sinUsuarios.classList.toggle('hidden', visibles > 0 || filas.length === 0);
        });
    }
});
