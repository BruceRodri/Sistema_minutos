// Assets/js/pagos.js

const DIAS_SEMANA = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

function esc(texto) {
    return String(texto ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

function formatearFechaISO(iso) {
    if (!iso) return '';
    const [a, m, d] = String(iso).split('-').map(Number);
    if (!a || !m || !d) return String(iso);
    const fecha = new Date(a, m - 1, d);
    return `${DIAS_SEMANA[fecha.getDay()]} ${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${a}`;
}

function formatearFechaCorta(iso) {
    if (!iso) return '';
    const [a, m, d] = String(iso).split('-').map(Number);
    if (!a || !m || !d) return String(iso);
    return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${a}`;
}

function rutaRecibo(pago) {
    const archivo = String(pago.comprobante || '').split('/').pop();
    return '../../Controllers/ComprobanteController.php?pago_id=' + pago.id + '&archivo=' + encodeURIComponent(archivo);
}

function tarjetaPago(pago) {
    const estado = pago.estado === 'anulado' ? 'anulado' : (pago.estado === 'en_espera' ? 'en_espera' : 'aprobado');
    const etiquetaClase = estado === 'anulado' ? 'bg-red-100 text-red-700' : (estado === 'en_espera' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700');
    const etiquetaIcono = estado === 'anulado' ? 'fa-circle-xmark' : (estado === 'en_espera' ? 'fa-clock' : 'fa-check-circle');
    const bordeTarjeta = estado === 'anulado' ? 'border-red-200' : (estado === 'en_espera' ? 'border-amber-200' : 'border-green-200');
    const iconoEstado = estado === 'anulado' ? 'text-red-500' : (estado === 'en_espera' ? 'text-amber-500' : 'text-green-500');
    const verboPago = estado === 'anulado' ? 'Ingresado el' : (estado === 'en_espera' ? 'Subido el' : 'Pagado el');
    const etiquetaEstado = estado === 'anulado' ? 'Anulado' : (estado === 'en_espera' ? 'En espera…' : 'Aprobado');
    const diaClase = estado === 'anulado' ? 'bg-red-600' : (estado === 'en_espera' ? 'bg-amber-500' : 'bg-green-600');

    const fechas = (pago.fechas && pago.fechas.length ? pago.fechas : [pago.fecha_pago]).filter(Boolean).sort();
    const cantidadDias = fechas.length;
    const discos = Array.isArray(pago.discos) ? pago.discos : [];

    let encabezado;
    if (cantidadDias > 1) {
        const desde = fechas[0];
        const hasta = fechas[cantidadDias - 1];
        encabezado = `
                <div class="flex flex-col gap-1">
                    <p class="text-2xl lg:text-3xl font-extrabold text-gray-800">
                        Se pagaron <span class="inline-block align-middle ${diaClase} text-white px-3 py-0.5 rounded-full text-xl lg:text-2xl">${cantidadDias} días</span>
                    </p>
                    <p class="text-base lg:text-lg text-gray-500 font-semibold">del ${formatearFechaCorta(desde)}${hasta !== desde ? ' al ' + formatearFechaCorta(hasta) : ''}</p>
                </div>`;
    } else {
        encabezado = `<p class="text-2xl lg:text-3xl font-extrabold text-gray-800">${esc(formatearFechaISO(fechas[0]))}</p>`;
    }

    const discosHtml = discos.length
        ? `<span class="inline-flex items-center gap-1 mr-2 font-extrabold text-blue-700 text-xl lg:text-2xl"><i class="fas fa-compact-disc"></i>Disco ${esc(discos.join(' · '))}</span>`
        : '';

    const comprobanteHtml = pago.comprobante
        ? `<button type="button" data-ver-recibo="${esc(rutaRecibo(pago))}" class="mt-4 w-full rounded-xl bg-blue-50 text-blue-700 border border-blue-200 py-3 px-4 text-lg font-bold"><i class="fas fa-eye mr-2"></i>Ver comprobante</button>`
        : '';

    const motivoHtml = estado === 'anulado'
        ? `<div class="mt-3 rounded-xl bg-red-50 border border-red-200 p-4"><p class="font-bold text-red-700 text-lg">Motivo: <span class="font-semibold">${esc(pago.motivo_rechazo || 'Sin motivo especificado.')}</span></p></div>`
        : '';

    return `
            <div class="cardPago bg-white rounded-3xl p-6 lg:p-7 shadow-sm border-2 ${bordeTarjeta}" data-discos="${esc(discos.join(' '))}">
                <div class="flex items-center justify-between gap-3 mb-3">
                    ${encabezado}
                    <span class="${etiquetaClase} text-lg font-bold px-4 py-1.5 rounded-full flex items-center gap-2 shrink-0">
                        <i class="fas ${etiquetaIcono}"></i> ${etiquetaEstado}
                    </span>
                </div>
                <p class="text-lg lg:text-xl text-gray-500 mt-1">
                    ${discosHtml}
                    <span class="font-bold text-gray-600">${verboPago} ${esc(formatearFechaISO(pago.fecha_pago))}</span>
                </p>
                <hr class="border-gray-100 my-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-lg lg:text-xl text-gray-500">${cantidadDias} día(s) · <span class="font-bold text-gray-700">$ ${esc(pago.montoFmt)}</span></p>
                    <i class="fas ${etiquetaIcono} ${iconoEstado} text-4xl lg:text-5xl shrink-0"></i>
                </div>
                ${comprobanteHtml}
                ${motivoHtml}
            </div>`;
}

document.addEventListener('DOMContentLoaded', () => {
    const inputDisco = document.getElementById('buscarDisco');
    const limpiarDisco = document.getElementById('limpiarDisco');
    const listaDiscos = document.getElementById('listaDiscos');
    const grilla = document.getElementById('grillaPagos');
    const sinResultados = document.getElementById('sinResultados');

    if (!inputDisco || !grilla) return;

    let tarjetas = [];

    let datosIniciales = [];
    const datosPagos = document.getElementById('datosPagos');
    if (datosPagos) {
        try {
            datosIniciales = JSON.parse(datosPagos.textContent);
        } catch (error) {
            datosIniciales = [];
        }
    }

    let discos = [];
    const datosDiscos = document.getElementById('datosDiscos');
    if (datosDiscos) {
        try {
            discos = JSON.parse(datosDiscos.textContent);
        } catch (error) {
            discos = [];
        }
    }
    if (!Array.isArray(discos) || !discos.length) {
        discos = [...new Set(datosIniciales.flatMap((p) => p.discos || []))];
    }

    function normalizarDiscoBruto(valor) {
        return String(valor || '').replace(/\D+/g, '');
    }

    function discoCoincide(disco, bruto) {
        const b = normalizarDiscoBruto(bruto).replace(/^0+/, '');
        const d = String(disco || '').replace(/^0+/, '');
        return b === '' || d.startsWith(b) || d === b;
    }

    function aplicarFiltro() {
        const bruto = inputDisco.value;
        if (normalizarDiscoBruto(bruto) === '') {
            tarjetas.forEach((c) => c.classList.add('hidden'));
            if (sinResultados) {
                sinResultados.textContent = 'Selecciona un disco para consultar los pagos.';
                sinResultados.classList.remove('hidden');
            }
            return;
        }
        let visibles = 0;
        tarjetas.forEach((c) => {
            const discosTarjeta = (c.dataset.discos || '').split(/\s+/).filter(Boolean);
            const coincide = discosTarjeta.some((disco) => discoCoincide(disco, bruto));
            c.classList.toggle('hidden', !coincide);
            if (coincide) visibles++;
        });
        if (sinResultados) {
            if (visibles === 0) {
                sinResultados.textContent = 'No hay pagos para el disco seleccionado.';
                sinResultados.classList.remove('hidden');
            } else {
                sinResultados.classList.add('hidden');
            }
        }
    }

    window.renderizarPagos = function (datosPagos) {
        const lista = Array.isArray(datosPagos) ? datosPagos : [];
        if (!lista.length) {
            grilla.innerHTML = `
                <div class="lg:col-span-full bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-200">
                    <i class="fas fa-receipt text-gray-300 text-6xl mb-4"></i>
                    <p class="text-2xl text-gray-500">No hay pagos registrados.</p>
                </div>`;
        } else {
            grilla.innerHTML = lista.map(tarjetaPago).join('');
        }
        tarjetas = [...grilla.querySelectorAll('.cardPago')];
        vincularBotonesRecibo();
        aplicarFiltro();
    };

    function renderLista(bruto) {
        if (!listaDiscos) return;
        listaDiscos.innerHTML = '';
        const candidatos = discos.filter((disco) => discoCoincide(disco, bruto));
        if (!candidatos.length) {
            listaDiscos.innerHTML = '<p class="px-4 py-4 text-xl text-gray-400 font-semibold">Sin discos.</p>';
            listaDiscos.classList.remove('hidden');
            return;
        }
        candidatos.slice(0, 5).forEach((disco) => {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'w-full text-left px-5 py-4 flex items-center gap-3 hover:bg-blue-50 transition-colors';
            boton.innerHTML = `<i class="fas fa-compact-disc text-blue-500 text-2xl"></i><span class="font-extrabold text-blue-700 text-2xl">${esc(disco)}</span>`;
            boton.addEventListener('click', () => seleccionarDisco(disco));
            listaDiscos.appendChild(boton);
        });
        if (candidatos.length > 5) {
            const aviso = document.createElement('p');
            aviso.className = 'px-5 py-3 text-sm font-semibold text-gray-400 border-t border-gray-100';
            aviso.textContent = `Hay ${candidatos.length} discos. Escribe el número para filtrar.`;
            listaDiscos.appendChild(aviso);
        }
        listaDiscos.classList.remove('hidden');
    }

    function seleccionarDisco(disco) {
        inputDisco.value = disco;
        if (limpiarDisco) limpiarDisco.classList.remove('hidden');
        if (listaDiscos) listaDiscos.classList.add('hidden');
        aplicarFiltro();
    }

    function limpiarSeleccion() {
        inputDisco.value = '';
        if (limpiarDisco) limpiarDisco.classList.add('hidden');
        if (listaDiscos) listaDiscos.classList.add('hidden');
        aplicarFiltro();
    }

    inputDisco.addEventListener('click', () => renderLista(inputDisco.value));
    inputDisco.addEventListener('focus', () => renderLista(inputDisco.value));
    inputDisco.addEventListener('input', () => renderLista(inputDisco.value));
    inputDisco.addEventListener('blur', () => setTimeout(() => listaDiscos && listaDiscos.classList.add('hidden'), 200));

    if (limpiarDisco) limpiarDisco.addEventListener('click', limpiarSeleccion);

    // Todo el recuadro del disco abre la lista (no solo el input).
    const contenedorDisco = inputDisco.closest('.relative');
    if (contenedorDisco) {
        contenedorDisco.addEventListener('click', (evento) => {
            const origen = evento.target;
            if (origen.closest('#listaDiscos') || origen.closest('#limpiarDisco')) return;
            evento.preventDefault();
            inputDisco.focus();
            renderLista(inputDisco.value);
        });
    }

    window.renderizarPagos(datosIniciales);

    // ---------- Tiempo real (SSE) ----------
    const eventos = new EventSource('../../Controllers/ConductorStreamController.php');
    let ultimoHash = null;

    function aplicarSnapshot(evento) {
        try {
            const datos = JSON.parse(evento.data);
            if (!datos || !Array.isArray(datos.pagos)) return;
            if (datos.hash && datos.hash === ultimoHash) return;
            ultimoHash = datos.hash || null;
            window.renderizarPagos(datos.pagos);
        } catch (error) {
            console.error(error);
        }
    }

    eventos.addEventListener('snapshot', aplicarSnapshot);
    eventos.addEventListener('message', aplicarSnapshot);
    window.addEventListener('beforeunload', () => eventos?.close());
});

function vincularBotonesRecibo() {
    document.querySelectorAll('[data-ver-recibo]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const visor = document.getElementById('visorRecibo');
            const contenido = document.getElementById('contenidoRecibo');
            const descargar = document.getElementById('descargarRecibo');
            if (!visor) return;
            const ruta = boton.dataset.verRecibo;
            contenido.replaceChildren();
            if (descargar) {
                descargar.href = ruta + (ruta.includes('?') ? '&' : '?') + 'descargar=1';
                descargar.download = 'comprobante.png';
            }
            const pdf = /\.pdf$/i.test(ruta);
            const archivo = document.createElement(pdf ? 'iframe' : 'img');
            archivo.src = ruta;
            if (pdf) {
                archivo.title = 'Comprobante de pago';
                archivo.className = 'w-full h-[65vh] border-0';
            } else {
                archivo.alt = 'Comprobante de pago';
                archivo.className = 'w-full h-auto rounded-lg';
                archivo.onerror = () => {
                    contenido.textContent = 'No se pudo cargar la imagen. Usa "Descargar comprobante" para verla.';
                };
            }
            contenido.append(archivo);
            visor.showModal();
        });
    });
}

function configurarDialogoRecibo() {
    const visor = document.getElementById('visorRecibo');
    if (!visor) return;
    const contenido = document.getElementById('contenidoRecibo');
    const cerrar = () => { visor.close(); contenido.replaceChildren(); };
    document.getElementById('cerrarRecibo').addEventListener('click', cerrar);
    visor.addEventListener('click', (evento) => {
        if (evento.target === visor) {
            const rect = visor.getBoundingClientRect();
            if (evento.clientX < rect.left || evento.clientX > rect.right || evento.clientY < rect.top || evento.clientY > rect.bottom) cerrar();
        }
    });
}

document.addEventListener('DOMContentLoaded', configurarDialogoRecibo);