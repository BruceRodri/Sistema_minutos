// Assets/js/valores.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formSubirValores');
    const inputArchivo = document.getElementById('inputArchivo');
    const nombreArchivo = document.getElementById('nombreArchivo');
    const btnSubir = document.getElementById('btnSubir');
    const alerta = document.getElementById('alerta');
    const modal = document.getElementById('modalCargaValores');
    const tabla = document.getElementById('tablaValores');
    const total = document.getElementById('totalValores');
    const rango = document.getElementById('rangoValores');
    const paginacion = document.getElementById('paginacionValores');
    const estadoArchivo = document.getElementById('estadoArchivo');
    const fechaArchivo = document.getElementById('fechaArchivo');
    const gestionArchivos = document.getElementById('gestionArchivosValores');
    const listaArchivos = document.getElementById('listaArchivosValores');
    const confirmacion = document.getElementById('confirmarBorradoValores');
    const cancelarBorrado = document.getElementById('cancelarBorradoValores');
    const aceptarBorrado = document.getElementById('aceptarBorradoValores');
    const errorBorrado = document.getElementById('errorBorradoValores');
    const csrf = document.querySelector('meta[name="csrf-valores"]')?.content || '';
    const opcionesTrasBorrado = document.getElementById('opcionesTrasBorrado');
    const editorRegistro = document.getElementById('editorRegistroValores');
    const formRegistro = document.getElementById('formRegistroValores');
    const errorEditor = document.getElementById('errorEditorValores');
    const guardarRegistro = document.getElementById('guardarRegistroValores');
    const cancelarEditor = document.getElementById('cancelarEditorValores');
    let registroEliminado = null;
    let editorDesdeBorrado = false;
    let guardandoRegistro = false;
    let borradoPendiente = null;
    let borrando = false;
    let archivosDisponibles = [];
    let eventos = null;
    let temporizadorAlerta = null;
    let consultaActual = null;
    let subiendo = false;

    if (!form || !inputArchivo || !nombreArchivo || !btnSubir || !alerta || !modal ||
        !tabla || !total || !rango || !paginacion || !estadoArchivo || !fechaArchivo ||
        !gestionArchivos || !listaArchivos || !confirmacion || !cancelarBorrado || !aceptarBorrado || !errorBorrado) {
        console.error('Valores Diarios no pudo iniciar porque falta un elemento requerido de la interfaz.');
        return;
    }

    const escapar = (valor) => String(valor ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const mostrarAlerta = (tipo, mensaje) => {
        if (temporizadorAlerta) window.clearTimeout(temporizadorAlerta);
        alerta.textContent = mensaje;
        alerta.className = 'mb-5 rounded-xl border p-4 text-sm font-bold text-center whitespace-pre-line break-words ' +
            (tipo === 'success'
                ? 'border-green-200 bg-green-100 text-green-800'
                : 'border-red-200 bg-red-100 text-red-800');
        if (tipo === 'success') {
            temporizadorAlerta = window.setTimeout(() => alerta.classList.add('hidden'), 5000);
        }
    };

    async function solicitarValores(url, opciones = {}) {
        let respuesta;
        try {
            respuesta = await fetch(url, { cache: 'no-store', ...opciones });
        } catch (error) {
            throw new Error('No se pudo contactar al servidor. Revise la conexión e intente nuevamente.');
        }
        let datos;
        try {
            datos = await respuesta.json();
        } catch (error) {
            throw new Error(`El servidor devolvió una respuesta no válida (HTTP ${respuesta.status}). No se pudo confirmar la operación; recargue la página y revise el registro de PHP.`);
        }
        if (!respuesta.ok || datos?.status !== 'success') {
            throw new Error(datos?.message || `El servidor rechazó la operación (HTTP ${respuesta.status}).`);
        }
        return datos;
    }

    async function cargarArchivos() {
        listaArchivos.innerHTML = '<p class="py-6 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando archivos...</p>';
        try {
            const datos = await solicitarValores('../../Controllers/ValoresController.php?accion=archivos');
            archivosDisponibles = datos.archivos;
            listaArchivos.innerHTML = archivosDisponibles.length ? archivosDisponibles.map((archivo) => `
                <article class="rounded-xl border border-gray-200 p-4" style="background-color: ${escapar(archivo.color || '#ffffff')}">
                    <div class="flex items-start gap-3">
                        <i class="fas ${archivo.sin_archivo ? 'fa-table-list text-amber-600' : 'fa-file-excel text-green-600'} mt-1 text-2xl"></i>
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-bold text-gray-800">${escapar(archivo.nombre)}</p>
                            ${archivo.sin_archivo ? '' : `<p class="mt-1 text-xs text-gray-500">${escapar(archivo.fecha)} · ${(Number(archivo.tamano) / 1024).toFixed(1)} KB</p>`}
                            <p class="mt-2 text-sm text-gray-600">${archivo.sin_archivo
                                ? `${Number(archivo.registros)} registro(s) permanecen en la tabla sin un Excel vinculado. Puede eliminarlos juntos o borrar filas individualmente desde la tabla principal.`
                                : archivo.anterior
                                ? 'Archivo antiguo sin vínculo con registros. Sus datos se gestionan en Registros sin archivo asociado, en esta misma ventana.'
                                : `${Number(archivo.registros)} registro(s) vinculado(s) · ${Number(archivo.omitidas)} repetido(s) omitido(s)`}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                        ${archivo.sin_archivo ? '' : `<a href="../../Controllers/ValoresController.php?accion=descargar&id=${encodeURIComponent(archivo.id)}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50"><i class="fas fa-download mr-2"></i>Descargar Excel</a>`}
                        <button type="button" data-borrar-archivo="${escapar(archivo.id)}" class="rounded-lg bg-red-50 px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-100"><i class="fas fa-trash-can mr-2"></i>${archivo.sin_archivo ? 'Eliminar registros sin archivo' : archivo.anterior ? 'Eliminar archivo' : 'Eliminar Excel y datos'}</button>
                    </div>
                </article>`).join('') : '<p class="py-8 text-center text-gray-500">No hay archivos Excel guardados.</p>';
        } catch (error) {
            listaArchivos.innerHTML = `<p class="rounded-xl bg-red-50 p-4 text-sm text-red-700">${escapar(error.message)}</p><button type="button" data-reintentar-archivos class="mt-3 rounded-lg border px-4 py-2 text-sm font-bold">Reintentar</button>`;
        }
    }

    document.getElementById('btnGestionArchivos').addEventListener('click', () => {
        gestionArchivos.showModal();
        cargarArchivos();
    });
    document.getElementById('cerrarGestionArchivos').addEventListener('click', () => gestionArchivos.close());

    function pedirBorrado(accion, id, titulo, descripcion, ids = []) {
        if (subiendo || borrando || guardandoRegistro) return;
        borradoPendiente = { accion, id, ids };
        document.getElementById('tituloBorradoValores').textContent = titulo;
        document.getElementById('detalleBorradoValores').textContent = descripcion;
        errorBorrado.classList.add('hidden');
        confirmacion.showModal();
    }

    listaArchivos.addEventListener('click', (evento) => {
        if (evento.target.closest('[data-reintentar-archivos]')) return cargarArchivos();
        const boton = evento.target.closest('[data-borrar-archivo]');
        if (!boton) return;
        const archivo = archivosDisponibles.find((item) => String(item.id) === boton.dataset.borrarArchivo);
        if (!archivo) return;
        if (archivo.sin_archivo) {
            pedirBorrado('eliminar_sin_archivo', archivo.id, '¿Eliminar registros sin archivo?',
                `Se eliminarán ${Number(archivo.registros)} registro(s) sin archivo asociado, incluidos los pagados.\nLa selección incluye todos los registros sin archivo, aunque no aparezcan con los filtros actuales de la tabla.`, archivo.ids);
            return;
        }
        pedirBorrado('eliminar_archivo', archivo.id, '¿Eliminar este Excel?', archivo.anterior
            ? `${archivo.nombre}\nSe eliminará solo este archivo antiguo. Para borrar los datos, use Registros sin archivo asociado en Gestión Archivos.`
            : `${archivo.nombre}\nSe eliminarán el Excel y todos sus registros vinculados (${Number(archivo.registros)} actualmente), incluidos los pagados.`);
    });

    tabla.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-borrar-registro]');
        if (!boton) return;
        boton.closest('details').open = false;
        pedirBorrado('eliminar_registro', boton.dataset.borrarRegistro, '¿Borrar este registro?', `${boton.dataset.descripcion}\n¿Está seguro de que desea eliminar este registro de Valores Diarios?`);
    });
    cancelarBorrado.addEventListener('click', () => confirmacion.close());
    confirmacion.addEventListener('cancel', (evento) => { if (borrando) evento.preventDefault(); });
    aceptarBorrado.addEventListener('click', async () => {
        if (!borradoPendiente || borrando) return;
        borrando = true;
        aceptarBorrado.disabled = cancelarBorrado.disabled = true;
        aceptarBorrado.textContent = 'Eliminando...';
        errorBorrado.classList.add('hidden');
        eventos?.close();
        eventos = null;
        consultaActual?.abort();
        try {
            const cuerpo = new FormData();
            cuerpo.append('accion', borradoPendiente.accion);
            cuerpo.append('id', borradoPendiente.id);
            if (borradoPendiente.accion === 'eliminar_sin_archivo') cuerpo.append('ids', JSON.stringify(borradoPendiente.ids));
            cuerpo.append('csrf', csrf);
            const datos = await solicitarValores('../../Controllers/ValoresController.php', { method: 'POST', body: cuerpo });
            confirmacion.close();
            mostrarAlerta('success', datos.message);
            if (borradoPendiente.accion === 'eliminar_registro' && datos.registro) {
                registroEliminado = datos.registro;
                opcionesTrasBorrado.showModal();
            }
            if (gestionArchivos.open) {
                await cargarArchivos();
                listaArchivos.insertAdjacentHTML('afterbegin', `<p role="status" class="rounded-xl bg-green-50 p-3 text-sm font-bold text-green-800">${escapar(datos.message)}</p>`);
            }
            actualizarTabla();
        } catch (error) {
            errorBorrado.textContent = error.message;
            errorBorrado.classList.remove('hidden');
        } finally {
            borrando = false;
            aceptarBorrado.disabled = cancelarBorrado.disabled = false;
            aceptarBorrado.textContent = 'Sí, eliminar';
            conectarEventos(true);
        }
    });

    function abrirEditorRegistro(modificar) {
        if (guardandoRegistro || borrando || subiendo) return;
        if (modificar && !registroEliminado) return;
        editorDesdeBorrado = opcionesTrasBorrado.open;
        formRegistro.reset();
        formRegistro.elements.namedItem('archivo_id').value = '0';
        errorEditor.classList.add('hidden');
        document.getElementById('tituloEditorValores').textContent = modificar ? 'Modificar y volver a guardar' : 'Agregar nuevo registro';
        cancelarEditor.textContent = editorDesdeBorrado ? 'Volver' : 'Cancelar';
        if (modificar) {
            for (const campo of ['disco', 'fecha', 'valor', 'ruta', 'archivo_id']) {
                formRegistro.elements.namedItem(campo).value = registroEliminado[campo] ?? (campo === 'archivo_id' ? 0 : '');
            }
        }
        opcionesTrasBorrado.close();
        editorRegistro.showModal();
        formRegistro.elements.namedItem('disco').focus();
    }
    document.getElementById('btnAgregarRegistro').addEventListener('click', () => abrirEditorRegistro(false));
    document.getElementById('modificarTrasBorrado').addEventListener('click', () => abrirEditorRegistro(true));
    document.getElementById('crearTrasBorrado').addEventListener('click', () => abrirEditorRegistro(false));
    document.getElementById('terminarTrasBorrado').addEventListener('click', () => opcionesTrasBorrado.close());
    cancelarEditor.addEventListener('click', () => {
        if (guardandoRegistro) return;
        editorRegistro.close();
        if (editorDesdeBorrado) opcionesTrasBorrado.showModal();
    });
    editorRegistro.addEventListener('cancel', (evento) => {
        evento.preventDefault();
        if (!guardandoRegistro) cancelarEditor.click();
    });
    formRegistro.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (guardandoRegistro || borrando || subiendo) return;
        guardandoRegistro = true;
        const cuerpo = new FormData(formRegistro);
        cuerpo.append('accion', 'crear_registro');
        cuerpo.append('csrf', csrf);
        guardarRegistro.disabled = cancelarEditor.disabled = true;
        guardarRegistro.textContent = 'Guardando...';
        errorEditor.classList.add('hidden');
        eventos?.close();
        eventos = null;
        consultaActual?.abort();
        try {
            const datos = await solicitarValores('../../Controllers/ValoresController.php', { method: 'POST', body: cuerpo });
            editorRegistro.close();
            registroEliminado = null;
            mostrarAlerta('success', datos.message);
            // Volver a la primera página de la tabla completa después de guardar.
            const filtros = document.querySelector('form[action="valores.php"]');
            const url = new URL(window.location.href);
            for (const campo of ['disco', 'fecha', 'valor', 'ruta', 'pagina']) url.searchParams.delete(campo);
            for (const campo of ['disco', 'fecha', 'valor', 'ruta']) {
                if (filtros) filtros.elements.namedItem(campo).value = '';
            }
            window.history.replaceState({}, '', url.pathname + url.search);
            actualizarTabla();
        } catch (error) {
            errorEditor.textContent = error.message;
            errorEditor.classList.remove('hidden');
        } finally {
            guardandoRegistro = false;
            guardarRegistro.disabled = cancelarEditor.disabled = false;
            guardarRegistro.textContent = 'Guardar registro';
            conectarEventos(true);
        }
    });

    const cerrarModal = () => modal.classList.add('hidden');
    document.getElementById('btnAbrirCarga')?.addEventListener('click', () => modal.classList.remove('hidden'));
    document.getElementById('btnCerrarCarga')?.addEventListener('click', cerrarModal);
    document.getElementById('btnCancelarCarga')?.addEventListener('click', cerrarModal);
    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) cerrarModal();
    });

    inputArchivo.addEventListener('change', () => {
        nombreArchivo.textContent = inputArchivo.files.length
            ? Array.from(inputArchivo.files, (archivo) => archivo.name).join('\n')
            : 'Seleccione uno o varios archivos .xlsx';
    });

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (subiendo || borrando || guardandoRegistro) return;
        alerta.classList.add('hidden');

        const archivos = Array.from(inputArchivo.files);
        if (!archivos.length) {
            mostrarAlerta('error', 'Seleccione uno o varios archivos .xlsx primero.');
            return;
        }

        btnSubir.disabled = true;
        inputArchivo.disabled = true;
        subiendo = true;
        consultaActual?.abort();
        btnSubir.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Cargando...';
        // Liberar la conexión persistente mientras se suben los archivos evita
        // bloqueos en navegadores móviles con pocos canales HTTP disponibles.
        if (eventos) eventos.close();
        eventos = null;

        let cargados = 0;
        let insertadas = 0;
        let omitidas = 0;
        const errores = [];

        try {
            // Cada petición importa un archivo completo antes de comenzar el siguiente.
            for (const [indice, archivo] of archivos.entries()) {
                btnSubir.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>${indice + 1} / ${archivos.length}`;
                if (!/\.xlsx$/i.test(archivo.name) || archivo.size > 10 * 1024 * 1024) {
                    errores.push(`${archivo.name}: debe ser .xlsx y no superar 10 MB.`);
                    continue;
                }

                const formData = new FormData();
                formData.append('accion', 'subir');
                formData.append('archivo', archivo);
                formData.append('csrf', csrf);

                try {
                    const data = await solicitarValores('../../Controllers/ValoresController.php', { method: 'POST', body: formData });
                    cargados++;
                    insertadas += Number(data.insertadas) || 0;
                    omitidas += Number(data.omitidas) || 0;
                } catch (error) {
                    console.error(error);
                    errores.push(`${archivo.name}: ${error.message}`);
                }
            }

            const resumen = `${cargados} de ${archivos.length} archivo(s) cargado(s). ${insertadas} dato(s) nuevo(s) guardado(s). ${omitidas} repetido(s) omitido(s).`;
            mostrarAlerta(errores.length ? 'error' : 'success', [resumen, ...errores].join('\n'));
            cerrarModal();
            if (!errores.length) {
                form.reset();
                nombreArchivo.textContent = 'Seleccione uno o varios archivos .xlsx';
            }
            if (cargados > 0) {
                const url = new URL(window.location.href);
                // Una carga nueva se muestra desde el inicio, sin filtros que oculten sus filas.
                const filtros = document.querySelector('form[action="valores.php"]');
                for (const campo of ['disco', 'fecha', 'valor', 'ruta', 'pagina']) {
                    url.searchParams.delete(campo);
                    const control = filtros?.elements.namedItem(campo);
                    if (control) control.value = '';
                }
                window.history.replaceState({}, '', url.pathname + url.search);
            }
            actualizarTabla();
        } finally {
            subiendo = false;
            btnSubir.disabled = false;
            inputArchivo.disabled = false;
            btnSubir.innerHTML = '<i class="fas fa-upload mr-2"></i>Cargar';
            if (!eventos) conectarEventos(true);
        }
    });

    const formatearFecha = (fecha) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha || '')) return fecha || '—';
        const [anio, mes, dia] = fecha.split('-');
        return `${dia}/${mes}/${anio}`;
    };

    // ---------- Búsqueda automática en los filtros (debounce 400ms) ----------
    const formularioFiltros = document.querySelector('form[action="valores.php"]');
    if (formularioFiltros) {
        let temporizadorFiltros = null;
        const aplicarFiltros = () => {
            const parametros = new URLSearchParams(new FormData(formularioFiltros));
            [...parametros.entries()].forEach(([nombre, valor]) => {
                if (String(valor).trim() === '') parametros.delete(nombre);
            });
            parametros.delete('pagina');
            const consulta = parametros.toString();
            window.history.replaceState({}, '', `${window.location.pathname}${consulta ? `?${consulta}` : ''}`);
            actualizarTabla();
        };
        const lanzarBusqueda = (evento) => {
            if (!evento.target.closest('form[action="valores.php"]')) return;
            if (temporizadorFiltros) clearTimeout(temporizadorFiltros);
            temporizadorFiltros = setTimeout(aplicarFiltros, 400);
        };
        formularioFiltros.addEventListener('submit', (evento) => {
            evento.preventDefault();
            if (temporizadorFiltros) clearTimeout(temporizadorFiltros);
            aplicarFiltros();
        });
        document.addEventListener('input', lanzarBusqueda);
        document.addEventListener('change', lanzarBusqueda);
    }

    const urlPagina = (pagina) => {
        const url = new URL(window.location.href);
        url.searchParams.set('pagina', pagina);
        return `${url.pathname}?${url.searchParams.toString()}`;
    };

    const enlacePagina = (pagina, contenido, activo = false) => {
        const clases = activo
            ? 'bg-blue-600 text-white shadow'
            : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50';
        return `<a href="${escapar(urlPagina(pagina))}" class="inline-flex min-w-10 items-center justify-center rounded-lg px-3 py-2 text-sm font-bold ${clases}">${contenido}</a>`;
    };

    const renderizarPaginacion = (paginaActual, totalPaginas) => {
        paginaActual = Math.max(1, Number.parseInt(paginaActual, 10) || 1);
        totalPaginas = Math.max(1, Number.parseInt(totalPaginas, 10) || 1);
        if (totalPaginas <= 1) {
            paginacion.innerHTML = '';
            paginacion.classList.add('hidden');
            return;
        }

        let html = '';
        if (paginaActual > 1) html += enlacePagina(paginaActual - 1, '<i class="fas fa-chevron-left mr-1"></i>Anterior');
        for (let pagina = Math.max(1, paginaActual - 2); pagina <= Math.min(totalPaginas, paginaActual + 2); pagina++) {
            html += enlacePagina(pagina, pagina, pagina === paginaActual);
        }
        if (paginaActual < totalPaginas) html += enlacePagina(paginaActual + 1, 'Siguiente<i class="fas fa-chevron-right ml-1"></i>');
        paginacion.innerHTML = html;
        paginacion.classList.remove('hidden');
    };

    const renderizar = (datos) => {
        if (!datos || typeof datos !== 'object' || !Array.isArray(datos.filas)) {
            console.warn('Se ignoró una actualización SSE de valores con formato incompleto.');
            return;
        }

        const filasValidas = datos.filas.filter((fila) => fila && typeof fila === 'object');
        const totalRegistros = Math.max(0, Number.parseInt(datos.total, 10) || 0);
        const archivoExiste = Boolean(datos.archivo_existe);
        total.textContent = totalRegistros;
        estadoArchivo.className = `font-bold ${archivoExiste ? 'text-green-700' : 'text-amber-700'}`;
        estadoArchivo.innerHTML = `<i class="fas ${archivoExiste ? 'fa-circle-check' : 'fa-circle-exclamation'} mr-1"></i>${archivoExiste ? 'Archivo cargado' : 'Sin archivo cargado'}`;
        fechaArchivo.textContent = datos.fecha_subida
            ? `Última carga: ${datos.fecha_subida}`
            : 'Cargue un archivo para visualizar datos';

        if (!filasValidas.length) {
            tabla.innerHTML = `<tr><td colspan="6" class="px-6 py-14 text-center text-gray-500"><i class="fas fa-table-list text-3xl text-gray-300 mb-3"></i><p>${archivoExiste ? 'No se encontraron datos con los filtros seleccionados.' : 'Aún no se han cargado valores.'}</p></td></tr>`;
            rango.textContent = '';
            rango.classList.add('hidden');
        } else {
            tabla.innerHTML = filasValidas.map((fila, indice) => `
                ${indice === 0 || Number(fila.archivo_id) !== Number(filasValidas[indice - 1].archivo_id) ? `
                <tr style="background-color: ${escapar(fila.archivo_color || '#ffffff')}">
                    <td colspan="6" class="px-6 py-3 text-sm text-gray-700">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="min-w-0 break-words font-bold"><i class="fas fa-file-excel mr-2" aria-hidden="true"></i>${escapar(fila.archivo_nombre || 'Registros sin archivo asociado')}</span>
                            ${fila.archivo_fecha_subida ? `<span class="text-xs">Subido el ${escapar(fila.archivo_fecha_subida)}</span>` : ''}
                        </div>
                    </td>
                </tr>` : ''}
                <tr class="transition-colors" style="background-color: ${escapar(fila.archivo_color || '#ffffff')}">
                    <td class="px-6 py-4 whitespace-nowrap text-center font-mono font-bold text-blue-800">${escapar(fila.disco)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">${escapar(formatearFecha(fila.fecha))}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-800">$ ${Number(fila.valor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">${escapar(fila.ruta || '—')}<span class="mt-1 block max-w-xs truncate text-xs text-gray-500" title="${escapar(fila.archivo_nombre || 'Sin archivo asociado')}"><i class="fas fa-file-excel mr-1"></i>${escapar(fila.archivo_nombre || 'Sin archivo asociado')}</span></td>
                    <td class="px-6 py-4 text-center"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ${Number(fila.pagado) === 1 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}">${Number(fila.pagado) === 1 ? 'Pagado' : 'No pagado'}</span></td>
                    <td class="px-6 py-4 text-center">
                        <details class="inline-block text-left">
                            <summary class="list-none cursor-pointer whitespace-nowrap rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50">Acciones <i class="fas fa-chevron-down ml-2 text-xs"></i></summary>
                            <button type="button" data-borrar-registro="${Number(fila.id)}" data-descripcion="${escapar(`Disco ${fila.disco} · ${formatearFecha(fila.fecha)} · $ ${Number(fila.valor).toFixed(2)}`)}" class="mt-1 w-full whitespace-nowrap rounded-lg bg-red-50 px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-100"><i class="fas fa-trash-can mr-2"></i>Borrar registro</button>
                        </details>
                    </td>
                </tr>`).join('');
            const primero = Math.max(1, Number.parseInt(datos.primero, 10) || 1);
            const ultimo = Math.max(primero, Number.parseInt(datos.ultimo, 10) || filasValidas.length);
            rango.textContent = `Mostrando ${primero}–${ultimo} de ${totalRegistros}`;
            rango.classList.remove('hidden');
        }

        renderizarPaginacion(datos.pagina, datos.total_paginas);
    };

    async function actualizarTabla() {
        consultaActual?.abort();
        const consulta = new AbortController();
        consultaActual = consulta;
        const url = new URL('../../Controllers/ValoresStreamController.php', window.location.href);
        url.search = window.location.search;
        url.searchParams.set('consulta', '1');
        try {
            const respuesta = await fetch(url, { signal: consulta.signal, cache: 'no-store' });
            if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);
            const datos = await respuesta.json();
            if (consultaActual === consulta && !consulta.signal.aborted) renderizar(datos);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('No se pudo consultar Valores Diarios.', error);
                mostrarAlerta('error', 'No se pudieron actualizar los datos. Intente filtrar nuevamente.');
            }
        }
    }

    paginacion.addEventListener('click', (evento) => {
        const enlace = evento.target.closest('a');
        if (!enlace || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey) return;
        evento.preventDefault();
        window.history.replaceState({}, '', enlace.href);
        actualizarTabla();
    });

    function conectarEventos(reiniciar = false) {
        if (typeof EventSource === 'undefined') return;
        if (eventos && !reiniciar) return;
        if (eventos) eventos.close();

        const url = new URL('../../Controllers/ValoresStreamController.php', window.location.href);
        // Una conexión estable avisa de cambios; AJAX aplica los filtros vigentes.
        eventos = new EventSource(url.toString());
        eventos.addEventListener('valores', (evento) => {
            try {
                if (typeof evento.data !== 'string' || evento.data.trim() === '') return;
                const datos = JSON.parse(evento.data);
                if (datos && Array.isArray(datos.filas) && !subiendo && !borrando && !guardandoRegistro) actualizarTabla();
            } catch (error) {
                console.error('No se pudo actualizar la tabla de valores.', error);
            }
        });
        eventos.addEventListener('error', () => {
            // EventSource reintenta automáticamente; el resto de botones permanece operativo.
            console.warn('La conexión en tiempo real de Valores Diarios se está reconectando.');
        });
    }

    conectarEventos();
    window.addEventListener('beforeunload', () => {
        eventos?.close();
        consultaActual?.abort();
    });
});
