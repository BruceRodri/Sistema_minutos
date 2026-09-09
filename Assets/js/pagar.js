// Assets/js/pagar.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/PagoController.php';
    const MAX_SIZE = 5 * 1024 * 1024;
    const datos = JSON.parse(document.getElementById('datosPagar').textContent);
    const pagables = datos.pagables || [];
    const esMovil = () => !window.matchMedia('(min-width: 768px)').matches;

    let discosDisponibles = [];
    let filtroDisco = null;
    let indiceTarjetas = 0;
    let flujoActivo = null;   // 'card' | 'multi'
    let idsAPagar = [];

    // ---------- Utilidades ----------
    function soloNumeros(e) {
        if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Escape', 'Home', 'End'].includes(e.key)) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    }

    function normalizarDiscoBruto(valor) {
        return String(valor || '').replace(/\D+/g, '');
    }

    function discCoincide(disco, bruto) {
        const b = normalizarDiscoBruto(bruto).replace(/^0+/, '');
        const d = String(disco || '').replace(/^0+/, '');
        return d.startsWith(b) || d === b;
    }

    function mostrarAlerta(elemento, tipo, mensaje) {
        if (!elemento) return;
        elemento.textContent = mensaje;
        elemento.className = 'p-5 rounded-2xl text-center text-xl lg:text-2xl font-bold ' +
            (tipo === 'success'
                ? 'bg-green-100 text-green-800 border-2 border-green-200'
                : 'bg-red-100 text-red-800 border-2 border-red-200');
        elemento.classList.remove('hidden');
    }

    function validarArchivo(archivo) {
        const tipoPermitido = archivo.type.startsWith('image/') || archivo.type === 'application/pdf';
        if (!tipoPermitido) return 'Solo se permiten imágenes y documentos PDF.';
        if (archivo.size > MAX_SIZE) return 'El archivo supera el tamaño máximo de 5 MB.';
        return null;
    }

    async function ejecutarPago() {
        const formData = new FormData();
        formData.append('accion', 'pagar_varios');
        idsAPagar.forEach((id) => formData.append('obligaciones_ids[]', id));
        const archivo = inputComprobante.files[0];
        if (!archivo) return;
        formData.append('archivo', archivo);

        const alerta = flujoActivo === 'card' ? alertaTarjetas : alertaVarios;
        if (alerta) {
            alerta.textContent = 'Subiendo comprobante…';
            alerta.className = 'p-5 rounded-2xl text-center text-xl lg:text-2xl font-bold bg-blue-50 text-blue-700 border-2 border-blue-200';
            alerta.classList.remove('hidden');
        }

        try {
            const response = await fetch(urlControlador, { method: 'POST', body: formData });
            const data = await response.json();
            mostrarAlerta(alerta, data.status === 'success' ? 'success' : 'error', data.message);
            if (data.status === 'success') {
                quitarPagados(idsAPagar);
            }
        } catch (error) {
            console.error(error);
            mostrarAlerta(alerta, 'error', 'Error de conexión con el servidor.');
        } finally {
            inputComprobante.value = '';
            flujoActivo = null;
            idsAPagar = [];
        }
    }

    function quitarPagados(ids) {
        const pagados = new Set(ids.map((id) => String(id)));
        if (carrusel) {
            carrusel.querySelectorAll('.cardPagar').forEach((card) => {
                if (pagados.has(String(card.dataset.id))) card.remove();
            });
            todasLasTarjetas = [...carrusel.querySelectorAll('.cardPagar')];
        }
        document.querySelectorAll('.checkDia').forEach((check) => {
            if (pagados.has(String(check.value))) check.closest('.checkDiaFila')?.remove();
        });
        aplicarFiltro();
        renderCarrusel();
        calcularTotal();
    }

    // ---------- Referencias DOM ----------
    const inputDisco = document.getElementById('buscarDisco');
    const listaDiscos = document.getElementById('listaDiscos');
    const limpiarDisco = document.getElementById('limpiarDisco');
    const sinSeleccion = document.getElementById('sinSeleccion');
    const verVarios = document.getElementById('verVarios');
    const bloqueCarrusel = document.getElementById('bloqueCarrusel');
    const carrusel = document.getElementById('carrusel');
    const puntosTarjetas = document.getElementById('puntosTarjetas');
    const sinResultados = document.getElementById('sinResultadosDisco');
    const volverTarjetas = document.getElementById('volverTarjetas');
    const vistaCarousel = document.getElementById('vistaCarousel');
    const vistaMulti = document.getElementById('vistaMulti');
    const filasDia = document.querySelectorAll('.checkDiaFila');
    const totalVarios = document.getElementById('totalVarios');
    const detalleVarios = document.getElementById('detalleVarios');
    const inputComprobante = document.getElementById('inputComprobante');
    const btnPagarVarios = document.getElementById('btnPagarVarios');
    const alertaTarjetas = document.getElementById('alertaTarjetas');
    const alertaVarios = document.getElementById('alertaVarios');
    const modal = document.getElementById('modalConfirmar');
    const modalMensaje = document.getElementById('modalMensaje');
    const modalCancelar = document.getElementById('modalCancelar');
    const modalAceptar = document.getElementById('modalAceptar');

    let todasLasTarjetas = carrusel ? [...carrusel.querySelectorAll('.cardPagar')] : [];

    function tarjetasFiltradas() {
        return todasLasTarjetas.filter((c) => !filtroDisco || discCoincide(c.dataset.disco, filtroDisco));
    }

    // ---------- Lista de discos (solo se abre cuando el usuario interactúa) ----------
    let debounceTimer = null;
    let solicitudDiscos = 0;
    let listaSolicitada = false;

    async function cargarDiscos(q, abrir = false) {
        const solicitud = ++solicitudDiscos;
        const body = new URLSearchParams();
        body.append('accion', 'listar_discos');
        body.append('q', q);
        try {
            const response = await fetch(urlControlador, { method: 'POST', body });
            const data = await response.json();
            if (solicitud !== solicitudDiscos) return;
            if (data.status === 'success') discosDisponibles = data.discos || [];
            if (abrir && listaSolicitada) renderListaDiscos();
        } catch (error) {
            console.error(error);
        }
    }

    function renderListaDiscos() {
        if (!listaDiscos) return;
        listaDiscos.innerHTML = '';
        if (!discosDisponibles.length) {
            listaDiscos.innerHTML = '<p class="px-4 py-4 text-xl text-gray-400 font-semibold">Sin discos disponibles.</p>';
            listaDiscos.classList.remove('hidden');
            return;
        }
        discosDisponibles.slice(0, 5).forEach((disco) => {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'w-full text-left px-5 py-4 flex items-center gap-3 hover:bg-blue-50 transition-colors';
            boton.innerHTML = `<i class="fas fa-compact-disc text-blue-500 text-2xl"></i><span class="font-extrabold text-blue-700 text-2xl">${disco}</span>`;
            boton.addEventListener('click', () => seleccionarDisco(disco));
            listaDiscos.appendChild(boton);
        });
        if (discosDisponibles.length > 5) {
            const aviso = document.createElement('p');
            aviso.className = 'px-5 py-3 text-sm font-semibold text-gray-400 border-t border-gray-100';
            aviso.textContent = `Hay ${discosDisponibles.length} discos. Escribe el número para filtrar.`;
            listaDiscos.appendChild(aviso);
        }
        listaDiscos.classList.remove('hidden');
    }

    function abrirLista() {
        listaSolicitada = true;
        cargarDiscos(inputDisco.value, true);
    }

    function abrirListaConBusqueda(q) {
        listaSolicitada = true;
        cargarDiscos(q, true);
    }

    // ---------- Selección y limpieza de disco ----------
    function seleccionarDisco(disco) {
        listaSolicitada = false;
        solicitudDiscos++;
        filtroDisco = normalizarDiscoBruto(disco);
        inputDisco.value = disco;
        limpiarDisco.classList.remove('hidden');
        listaDiscos.classList.add('hidden');
        indiceTarjetas = 0;
        if (vistaMulti.classList.contains('hidden') === false) {
            vistaMulti.classList.add('hidden');
            vistaCarousel.classList.remove('hidden');
        }
        desmarcarTodos();
        aplicarFiltro();
        renderCarrusel();
    }

    function limpiarSeleccion() {
        listaSolicitada = false;
        solicitudDiscos++;
        filtroDisco = null;
        inputDisco.value = '';
        limpiarDisco.classList.add('hidden');
        listaDiscos.classList.add('hidden');
        indiceTarjetas = 0;
        vistaMulti.classList.add('hidden');
        vistaCarousel.classList.remove('hidden');
        desmarcarTodos();
        aplicarFiltro();
        renderCarrusel();
    }

    // ---------- Filtrado por disco (tarjetas y lista múltiple) ----------
    function aplicarFiltro() {
        if (filtroDisco === null) {
            sinSeleccion.classList.remove('hidden');
            verVarios.classList.add('hidden');
            bloqueCarrusel.classList.add('hidden');
            return;
        }

        sinSeleccion.classList.add('hidden');

        const visibles = tarjetasFiltradas();
        visibles.forEach((c) => c.classList.remove('hidden'));
        todasLasTarjetas.forEach((c) => { if (!visibles.includes(c)) c.classList.add('hidden'); });

        if (!visibles.length) {
            verVarios.classList.add('hidden');
            bloqueCarrusel.classList.remove('hidden');
            sinResultados.classList.remove('hidden');
            return;
        }

        verVarios.classList.remove('hidden');
        bloqueCarrusel.classList.remove('hidden');
        sinResultados.classList.add('hidden');
        indiceTarjetas = Math.min(indiceTarjetas, visibles.length - 1);

        filasDia.forEach((fila) => {
            const discoFila = fila.querySelector('.checkDia').dataset.disco;
            const ok = discCoincide(discoFila, filtroDisco);
            fila.classList.toggle('hidden', !ok);
            if (!ok) fila.querySelector('.checkDia').checked = false;
        });
        calcularTotal();
    }

    function desmarcarTodos() {
        document.querySelectorAll('.checkDia').forEach((c) => { c.checked = false; });
        calcularTotal();
    }

    // ---------- Carrusel: deslizar en móvil, cuadrícula en escritorio ----------
    function renderPuntos() {
        if (!puntosTarjetas) return;
        const visibles = tarjetasFiltradas();
        const n = visibles.length;
        puntosTarjetas.innerHTML = '';
        if (esMovil() && n > 1) {
            puntosTarjetas.classList.remove('hidden');
            for (let i = 0; i < n; i++) {
                const punto = document.createElement('span');
                punto.style.width = '10px';
                punto.style.height = '10px';
                punto.style.borderRadius = '9999px';
                punto.style.background = '#93c5fd';
                punto.style.transition = 'background-color 0.2s ease';
                puntosTarjetas.appendChild(punto);
            }
        } else {
            puntosTarjetas.classList.add('hidden');
        }
    }

    function actualizarPuntoActivo() {
        if (!carrusel || !puntosTarjetas) return;
        const visibles = tarjetasFiltradas();
        const n = visibles.length;
        if (n <= 1) return;
        const paso = visibles.length > 1 ? visibles[1].offsetLeft - visibles[0].offsetLeft : 0;
        let indice = paso > 0 ? Math.round(carrusel.scrollLeft / paso) : 0;
        indice = Math.max(0, Math.min(indice, n - 1));
        [...puntosTarjetas.children].forEach((punto, i) => {
            punto.style.background = i === indice ? '#1d4ed8' : '#93c5fd';
        });
    }

    function renderCarrusel() {
        const visibles = tarjetasFiltradas();
        todasLasTarjetas.forEach((c) => {
            c.classList.toggle('hidden', !visibles.includes(c));
            c.style.marginInline = visibles.length === 1 && esMovil() ? 'auto' : '';
        });
        if (carrusel) carrusel.scrollLeft = 0;
        renderPuntos();
        actualizarPuntoActivo();
    }

    if (carrusel) carrusel.addEventListener('scroll', actualizarPuntoActivo, { passive: true });
    window.addEventListener('resize', renderCarrusel);

    // ---------- Validación estricta: solo números ----------
    if (inputDisco) {
        inputDisco.addEventListener('keydown', soloNumeros);
        inputDisco.addEventListener('focus', () => {
            abrirLista();
        });
        inputDisco.addEventListener('input', () => {
            if (inputDisco.value.replace(/\D/g, '') !== inputDisco.value) {
                inputDisco.value = inputDisco.value.replace(/\D/g, '');
            }
            abrirListaConBusqueda(inputDisco.value);
        });

    }

    if (limpiarDisco) limpiarDisco.addEventListener('click', limpiarSeleccion);

    // Todo el recuadro del disco abre la lista (no solo el input).
    const contenedorDisco = inputDisco ? inputDisco.closest('.relative') : null;
    if (contenedorDisco && inputDisco) {
        contenedorDisco.addEventListener('click', (evento) => {
            const origen = evento.target;
            if (origen.closest('#listaDiscos') || origen.closest('#limpiarDisco')) return;
            if (document.activeElement !== inputDisco) inputDisco.focus();
            else if (!listaSolicitada) abrirLista();
        });
    }

    const cerrarListaDiscos = () => {
        listaSolicitada = false;
        solicitudDiscos++;
        listaDiscos.classList.add('hidden');
    };
    document.addEventListener('pointerdown', (evento) => {
        if (contenedorDisco && !contenedorDisco.contains(evento.target)) cerrarListaDiscos();
    });
    if (contenedorDisco) contenedorDisco.addEventListener('focusout', (evento) => {
        if (evento.relatedTarget && !contenedorDisco.contains(evento.relatedTarget)) cerrarListaDiscos();
    });
    if (inputDisco) inputDisco.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') cerrarListaDiscos();
    });

    // ---------- Cambio entre carrusel y pago múltiple ----------
    if (verVarios && volverTarjetas) {
        verVarios.addEventListener('click', () => {
            vistaCarousel.classList.add('hidden');
            vistaMulti.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        volverTarjetas.addEventListener('click', () => {
            vistaMulti.classList.add('hidden');
            vistaCarousel.classList.remove('hidden');
            desmarcarTodos();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ---------- Pago múltiple: total dinámico ----------
    function calcularTotal() {
        if (!totalVarios) return;
        const checksVisibles = [...document.querySelectorAll('.checkDia')]
            .filter((c) => !c.closest('.checkDiaFila').classList.contains('hidden'));
        let total = 0;
        checksVisibles.forEach((c) => { if (c.checked) total += parseFloat(c.dataset.valor || 0); });
        totalVarios.textContent = total.toFixed(2);
        detalleVarios.textContent = total > 0
            ? `${checksVisibles.filter((c) => c.checked).length} día(s) seleccionado(s) · Disco ${inputDisco.value}`
            : 'Selecciona al menos un día.';
    }

    function esc(texto) {
        return String(texto ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function resaltar(texto) {
        return `<span class="font-extrabold text-blue-700">${esc(texto)}</span>`;
    }

    function mensajeConfirmar(titulo, disco, fecha, valor) {
        return `${titulo} del ${resaltar('DISCO ' + disco)} del ${resaltar(fecha)} por $ ${resaltar(valor)}?`;
    }

    document.querySelectorAll('.checkDia').forEach((c) => c.addEventListener('change', calcularTotal));

    if (btnPagarVarios) {
        btnPagarVarios.addEventListener('click', () => {
            const seleccionados = [...document.querySelectorAll('.checkDia')]
                .filter((c) => !c.closest('.checkDiaFila').classList.contains('hidden') && c.checked);
            if (!seleccionados.length) {
                mostrarAlerta(alertaVarios, 'error', 'Selecciona al menos un día para pagar.');
                return;
            }
            const total = seleccionados.reduce((suma, c) => suma + parseFloat(c.dataset.valor || 0), 0);
            const disco = seleccionados[0].dataset.disco;
            let mensaje;
            if (seleccionados.length === 1) {
                mensaje = mensajeConfirmar('¿Está seguro de registrar el pago', seleccionados[0].dataset.disco, seleccionados[0].dataset.fecha, seleccionados[0].dataset.valorfmt);
            } else {
                const fechas = seleccionados.map((c) => esc(c.dataset.fecha)).join(', ');
                mensaje = `¿Está seguro de registrar el pago de ${resaltar(`${seleccionados.length} día(s)`)} del ${resaltar('DISCO ' + disco)} (${fechas}) por un total de $ ${resaltar(total.toFixed(2))}?`;
            }
            abrirModal(mensaje, 'multi', seleccionados.map((c) => c.value));
        });
    }

    // ---------- Confirmación vía modal ----------
    function abrirModal(mensaje, flujo, ids) {
        modalMensaje.innerHTML = mensaje;
        flujoActivo = flujo;
        idsAPagar = ids;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        flujoActivo = null;
        idsAPagar = [];
    }

    if (modalCancelar) modalCancelar.addEventListener('click', cerrarModal);
    if (document.getElementById('modalFondo')) {
        document.getElementById('modalFondo').addEventListener('click', cerrarModal);
        modalAceptar.addEventListener('click', () => {
            const flujo = flujoActivo;
            const ids = [...idsAPagar];
            cerrarModal();
            if (!flujo || !ids.length) return;
            flujoActivo = flujo;
            idsAPagar = ids;
            [alertaTarjetas, alertaVarios].forEach((a) => { if (a) a.classList.add('hidden'); });
            inputComprobante.click();
        });
    }

    // ---------- Tarjeta del carrusel: confirmar y luego subir comprobante ----------
    todasLasTarjetas.forEach((card) => {
        card.addEventListener('click', () => {
            const mensaje = mensajeConfirmar('¿Está seguro de registrar el pago pendiente', card.dataset.disco, card.dataset.fecha, card.dataset.valor);
            abrirModal(mensaje, 'card', [card.dataset.id]);
        });
    });

    // ---------- Subida del comprobante ----------
    if (inputComprobante) {
        inputComprobante.addEventListener('change', async () => {
            const archivo = inputComprobante.files[0];
            if (!archivo) return;
            const error = validarArchivo(archivo);
            if (error) {
                mostrarAlerta(flujoActivo === 'card' ? alertaTarjetas : alertaVarios, 'error', error);
                inputComprobante.value = '';
                flujoActivo = null;
                idsAPagar = [];
                return;
            }
            await ejecutarPago();
        });
    }

    // ---------- Estado inicial: no se muestra nada hasta elegir un disco ----------
    cargarDiscos('');
});
