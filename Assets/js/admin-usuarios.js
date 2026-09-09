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
    const modal = document.getElementById('modalUsuario');
    const abrirCreacion = document.getElementById('btnNuevoUsuario');
    const cerrarModalBoton = document.getElementById('btnCerrarModalUsuario');
    const token = form?.querySelector('[name="csrf_token"]')?.value || '';
    const modalPermisos = document.getElementById('modalPermisos');
    const selectorPermisos = document.getElementById('usuarioPermisos');
    const buscadorPermisos = document.getElementById('buscarUsuarioPermisos');
    const resultadosPermisos = document.getElementById('resultadosUsuarioPermisos');
    const guardarPermisos = document.getElementById('btnGuardarPermisos');
    const checksPermisos = [...document.querySelectorAll('[name="permisos_modulos"]')];
    const alertaPermisos = document.getElementById('alertaPermisos');
    const tablaUsuariosPermisos = document.getElementById('usuariosPermisosSeleccionados');
    const sinUsuariosPermisos = document.getElementById('sinUsuariosPermisos');
    const usuariosPermisos = new Map();
    let rolPermisosActual = '';
    let temporizadorBusquedaPermisos = null;
    let busquedaPermisosActual = null;
    checksPermisos.forEach((c) => { c.disabled = true; });

    const abrirModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const cerrarModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

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
        alerta.classList.add('hidden');
    };

    abrirCreacion?.addEventListener('click', () => {
        prepararCreacion();
        abrirModal();
        window.setTimeout(() => document.getElementById('nombres')?.focus(), 50);
    });

    const cerrarPermisos = () => { modalPermisos?.classList.add('hidden'); modalPermisos?.classList.remove('flex'); };
    document.getElementById('btnGestionarPermisos')?.addEventListener('click', () => {
        modalPermisos?.classList.remove('hidden'); modalPermisos?.classList.add('flex');
        window.setTimeout(() => { buscadorPermisos?.focus(); buscarUsuariosPermisos(); }, 50);
    });
    document.getElementById('btnCerrarPermisos')?.addEventListener('click', cerrarPermisos);
    modalPermisos?.addEventListener('click', (e) => { if (e.target === modalPermisos) cerrarPermisos(); });

    const solicitarPermisos = async (accion) => {
        const cuerpo = new FormData();
        cuerpo.append('accion', accion);
        cuerpo.append('usuario_id', selectorPermisos.value);
        if (accion === 'guardar_permisos') usuariosPermisos.forEach((usuario) => cuerpo.append('usuario_ids[]', usuario.id));
        cuerpo.append('csrf_token', token);
        if (accion === 'guardar_permisos') checksPermisos.filter((c) => c.checked).forEach((c) => cuerpo.append('permisos[]', c.value));
        const respuesta = await fetch('../../Controllers/AdminUsuarioController.php', { method: 'POST', body: cuerpo });
        return respuesta.json();
    };

    const renderizarUsuariosPermisos = () => {
        tablaUsuariosPermisos.replaceChildren();
        usuariosPermisos.forEach((usuario) => {
            const fila = document.createElement('tr');
            const nombre = document.createElement('td');
            nombre.className = 'px-4 py-3 font-bold text-gray-800';
            nombre.textContent = `${usuario.nombres} ${usuario.apellidos}`;
            const cedula = document.createElement('td');
            cedula.className = 'px-4 py-3 text-center font-mono text-gray-600';
            cedula.textContent = usuario.cedula;
            const rol = document.createElement('td');
            rol.className = 'px-4 py-3 text-center capitalize text-gray-600';
            rol.textContent = usuario.rol;
            const accionEliminar = document.createElement('td');
            accionEliminar.className = 'px-4 py-3 text-center';
            const eliminar = document.createElement('button');
            eliminar.type = 'button';
            eliminar.className = 'inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100';
            eliminar.title = 'Eliminar de la lista';
            eliminar.setAttribute('aria-label', `Eliminar a ${usuario.nombres} de la lista`);
            eliminar.innerHTML = '<i class="fas fa-trash"></i>';
            eliminar.addEventListener('click', () => {
                usuariosPermisos.delete(String(usuario.id));
                const primero = usuariosPermisos.values().next().value;
                selectorPermisos.value = primero?.id || '';
                rolPermisosActual = primero?.rol || '';
                if (!primero) {
                    checksPermisos.forEach((c) => { c.checked = false; c.disabled = true; });
                    alertaPermisos.classList.add('hidden');
                }
                guardarPermisos.disabled = usuariosPermisos.size === 0;
                renderizarUsuariosPermisos();
            });
            accionEliminar.appendChild(eliminar);
            fila.append(nombre, cedula, rol, accionEliminar);
            tablaUsuariosPermisos.appendChild(fila);
        });
        sinUsuariosPermisos.classList.toggle('hidden', usuariosPermisos.size > 0);
    };

    const cargarPermisosUsuario = async () => {
        checksPermisos.forEach((c) => { c.checked = false; c.disabled = true; });
        guardarPermisos.disabled = true;
        alertaPermisos.classList.add('hidden');
        if (!selectorPermisos.value) return;
        if (rolPermisosActual === 'admin') {
            checksPermisos.forEach((c) => { c.checked = true; });
            alertaPermisos.textContent = 'El administrador tiene acceso completo.';
            alertaPermisos.className = 'mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm font-bold text-blue-700';
            return;
        }
        try {
            const datos = await solicitarPermisos('obtener_permisos');
            if (datos.status !== 'success') throw new Error(datos.message);
            checksPermisos.forEach((c) => { c.checked = datos.permisos.includes(c.value); c.disabled = false; });
            guardarPermisos.disabled = false;
        } catch (error) {
            alertaPermisos.textContent = error.message || 'No se pudieron cargar los permisos.';
            alertaPermisos.className = 'mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700';
        }
    };

    const buscarUsuariosPermisos = async () => {
        busquedaPermisosActual?.abort();
        const consulta = new AbortController();
        busquedaPermisosActual = consulta;
        const cuerpo = new FormData();
        cuerpo.append('accion', 'buscar_usuarios_permisos');
        cuerpo.append('termino', buscadorPermisos.value.trim());
        cuerpo.append('csrf_token', token);
        try {
            const respuesta = await fetch('../../Controllers/AdminUsuarioController.php', { method: 'POST', body: cuerpo, signal: consulta.signal });
            const datos = await respuesta.json();
            if (consulta.signal.aborted || datos.status !== 'success') return;
            resultadosPermisos.replaceChildren();
            datos.usuarios.slice(0, 5).forEach((usuario) => {
                const opcion = document.createElement('button');
                opcion.type = 'button';
                opcion.className = 'flex w-full items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 text-left last:border-0 hover:bg-blue-50';
                const identidad = document.createElement('span');
                identidad.className = 'font-bold text-gray-800';
                identidad.textContent = `${usuario.nombres} ${usuario.apellidos}`;
                const detalle = document.createElement('span');
                detalle.className = 'shrink-0 text-sm font-mono text-gray-500';
                detalle.textContent = usuario.cedula;
                opcion.append(identidad, detalle);
                opcion.addEventListener('click', () => {
                    if (usuario.rol === 'admin') {
                        alertaPermisos.textContent = 'El administrador tiene acceso completo y no necesita permisos individuales.';
                        alertaPermisos.className = 'mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm font-bold text-blue-700';
                        resultadosPermisos.classList.add('hidden');
                        return;
                    }
                    const esPrimero = usuariosPermisos.size === 0;
                    usuariosPermisos.set(String(usuario.id), usuario);
                    if (esPrimero) {
                        selectorPermisos.value = usuario.id;
                        rolPermisosActual = usuario.rol;
                    }
                    buscadorPermisos.value = '';
                    resultadosPermisos.classList.add('hidden');
                    renderizarUsuariosPermisos();
                    if (esPrimero) cargarPermisosUsuario();
                    else guardarPermisos.disabled = false;
                });
                resultadosPermisos.appendChild(opcion);
            });
            if (!datos.usuarios.length) {
                const vacio = document.createElement('p');
                vacio.className = 'px-4 py-3 text-sm text-gray-500';
                vacio.textContent = 'No se encontraron usuarios.';
                resultadosPermisos.appendChild(vacio);
            }
            resultadosPermisos.classList.remove('hidden');
        } catch (error) {
            if (error.name !== 'AbortError') console.error('No se pudieron buscar usuarios.', error);
        }
    };

    buscadorPermisos?.addEventListener('input', () => {
        if (usuariosPermisos.size === 0) {
            selectorPermisos.value = '';
            rolPermisosActual = '';
            guardarPermisos.disabled = true;
            checksPermisos.forEach((c) => { c.checked = false; c.disabled = true; });
        }
        if (temporizadorBusquedaPermisos) window.clearTimeout(temporizadorBusquedaPermisos);
        temporizadorBusquedaPermisos = window.setTimeout(buscarUsuariosPermisos, 300);
    });
    buscadorPermisos?.addEventListener('focus', buscarUsuariosPermisos);
    document.addEventListener('click', (evento) => {
        if (!evento.target.closest('#buscarUsuarioPermisos') && !evento.target.closest('#resultadosUsuarioPermisos')) resultadosPermisos?.classList.add('hidden');
    });

    guardarPermisos?.addEventListener('click', async () => {
        guardarPermisos.disabled = true;
        try {
            const datos = await solicitarPermisos('guardar_permisos');
            alertaPermisos.textContent = datos.message;
            alertaPermisos.className = `mt-4 rounded-xl border p-3 text-sm font-bold ${datos.status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'}`;
        } catch (error) {
            alertaPermisos.textContent = 'No se pudieron guardar los permisos.';
            alertaPermisos.className = 'mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700';
        } finally { guardarPermisos.disabled = false; }
    });

    cerrarModalBoton?.addEventListener('click', cerrarModal);
    cancelarEdicion?.addEventListener('click', cerrarModal);
    modal?.addEventListener('click', (evento) => {
        if (evento.target === modal) cerrarModal();
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !modal?.classList.contains('hidden')) cerrarModal();
    });

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
            alerta.classList.add('hidden');
            abrirModal();
        });
    });

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
