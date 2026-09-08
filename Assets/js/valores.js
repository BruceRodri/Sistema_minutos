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
    let eventos = null;

    const escapar = (valor) => String(valor ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const mostrarAlerta = (tipo, mensaje) => {
        alerta.textContent = mensaje;
        alerta.className = 'mb-5 rounded-xl border p-4 text-sm font-bold text-center ' +
            (tipo === 'success'
                ? 'border-green-200 bg-green-100 text-green-800'
                : 'border-red-200 bg-red-100 text-red-800');
    };

    const cerrarModal = () => modal.classList.add('hidden');
    document.getElementById('btnAbrirCarga').addEventListener('click', () => modal.classList.remove('hidden'));
    document.getElementById('btnCerrarCarga').addEventListener('click', cerrarModal);
    document.getElementById('btnCancelarCarga').addEventListener('click', cerrarModal);
    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) cerrarModal();
    });

    inputArchivo.addEventListener('change', () => {
        nombreArchivo.textContent = inputArchivo.files.length
            ? inputArchivo.files[0].name
            : 'Seleccione un archivo .xlsx';
    });

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        alerta.classList.add('hidden');

        if (!inputArchivo.files.length) {
            mostrarAlerta('error', 'Seleccione un archivo .xlsx primero.');
            return;
        }

        btnSubir.disabled = true;
        btnSubir.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Cargando...';
        const formData = new FormData(form);
        formData.append('accion', 'subir');

        try {
            const response = await fetch('../../Controllers/ValoresController.php', { method: 'POST', body: formData });
            const data = await response.json();
            mostrarAlerta(data.status === 'success' ? 'success' : 'error', data.message);

            if (data.status === 'success') {
                form.reset();
                nombreArchivo.textContent = 'Seleccione un archivo .xlsx';
                cerrarModal();
                conectarEventos(true);
            }
        } catch (error) {
            console.error(error);
            mostrarAlerta('error', 'Error de conexión con el servidor.');
        } finally {
            btnSubir.disabled = false;
            btnSubir.innerHTML = '<i class="fas fa-upload mr-2"></i>Cargar';
        }
    });

    const formatearFecha = (fecha) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha || '')) return fecha || '—';
        const [anio, mes, dia] = fecha.split('-');
        return `${dia}/${mes}/${anio}`;
    };

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
        total.textContent = datos.total;
        estadoArchivo.className = `font-bold ${datos.archivo_existe ? 'text-green-700' : 'text-amber-700'}`;
        estadoArchivo.innerHTML = `<i class="fas ${datos.archivo_existe ? 'fa-circle-check' : 'fa-circle-exclamation'} mr-1"></i>${datos.archivo_existe ? 'Archivo cargado' : 'Sin archivo cargado'}`;
        fechaArchivo.textContent = datos.fecha_subida
            ? `Última carga: ${datos.fecha_subida}`
            : 'Cargue un archivo para visualizar datos';

        if (!datos.filas.length) {
            tabla.innerHTML = `<tr><td colspan="4" class="px-6 py-14 text-center text-gray-500"><i class="fas fa-table-list text-3xl text-gray-300 mb-3"></i><p>${datos.archivo_existe ? 'No se encontraron datos con los filtros seleccionados.' : 'Aún no se ha cargado un archivo de valores.'}</p></td></tr>`;
            rango.textContent = '';
            rango.classList.add('hidden');
        } else {
            tabla.innerHTML = datos.filas.map((fila) => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-center font-mono font-bold text-blue-800">${escapar(fila.disco)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">${escapar(formatearFecha(fila.fecha))}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-800">$ ${Number(fila.valor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">${escapar(fila.ruta || '—')}</td>
                </tr>`).join('');
            rango.textContent = `Mostrando ${datos.primero}–${datos.ultimo} de ${datos.total}`;
            rango.classList.remove('hidden');
        }

        renderizarPaginacion(datos.pagina, datos.total_paginas);
    };

    function conectarEventos(reiniciar = false) {
        if (typeof EventSource === 'undefined') return;
        if (eventos && !reiniciar) return;
        if (eventos) eventos.close();

        const url = new URL('../../Controllers/ValoresStreamController.php', window.location.href);
        const parametros = new URLSearchParams(window.location.search);
        ['disco', 'fecha', 'valor', 'ruta', 'pagina'].forEach((nombre) => {
            const valor = parametros.get(nombre);
            if (valor) url.searchParams.set(nombre, valor);
        });

        eventos = new EventSource(url.toString());
        eventos.addEventListener('valores', (evento) => {
            try {
                renderizar(JSON.parse(evento.data));
            } catch (error) {
                console.error('No se pudo actualizar la tabla de valores.', error);
            }
        });
    }

    conectarEventos();
    window.addEventListener('beforeunload', () => eventos?.close());
});
