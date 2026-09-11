// Assets/js/tarjeta_pago.js
// Plantilla única de tarjeta de pago para la app del conductor.
// Se usa tanto en "Pagos realizados" como en "Pagar" (pagos incompletos).

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

function rutaRecibo(pago, indice) {
    const lista = Array.isArray(pago.comprobantes) && pago.comprobantes.length
        ? pago.comprobantes
        : (pago.comprobante ? [pago.comprobante] : []);
    const archivo = String(lista[indice] || lista[0] || '').split('/').pop();
    return '../../Controllers/ComprobanteController.php?pago_id=' + pago.id + '&archivo=' + encodeURIComponent(archivo);
}

function tarjetaPago(pago) {
    const estado = ['anulado', 'en_espera', 'incompleto'].includes(pago.estado) ? pago.estado : 'aprobado';
    const etiquetaClase = estado === 'anulado' ? 'bg-red-100 text-red-700'
        : (estado === 'incompleto' ? 'bg-orange-100 text-orange-700'
            : (estado === 'en_espera' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'));
    const etiquetaIcono = estado === 'anulado' ? 'fa-circle-xmark'
        : (estado === 'incompleto' ? 'fa-triangle-exclamation'
            : (estado === 'en_espera' ? 'fa-clock' : 'fa-check-circle'));
    const bordeTarjeta = estado === 'anulado' ? 'border-red-200'
        : (estado === 'incompleto' ? 'border-orange-300'
            : (estado === 'en_espera' ? 'border-amber-200' : 'border-green-200'));
    const iconoEstado = estado === 'anulado' ? 'text-red-500'
        : (estado === 'incompleto' ? 'text-orange-500'
            : (estado === 'en_espera' ? 'text-amber-500' : 'text-green-500'));
    const verboPago = estado === 'anulado' || estado === 'incompleto' ? 'Ingresado el'
        : (estado === 'en_espera' ? 'Subido el' : 'Pagado el');
    const etiquetaEstado = estado === 'anulado' ? 'Anulado'
        : (estado === 'incompleto' ? 'Pago incompleto'
            : (estado === 'en_espera' ? 'En espera…' : 'Aprobado'));
    const diaClase = estado === 'anulado' ? 'bg-red-600'
        : (estado === 'incompleto' ? 'bg-orange-500'
            : (estado === 'en_espera' ? 'bg-amber-500' : 'bg-green-600'));

    const fechas = (pago.fechas && pago.fechas.length ? pago.fechas : [pago.fecha_pago]).filter(Boolean).sort();
    const cantidadDias = fechas.length;
    const discos = Array.isArray(pago.discos) ? pago.discos : [];

    let encabezado;
    if (cantidadDias > 1) {
        const desde = fechas[0];
        const hasta = fechas[cantidadDias - 1];
        encabezado = `
                <div class="flex flex-col gap-1 min-w-0">
                    <p class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-gray-800 leading-tight break-words">
                        Se pagaron <span class="inline-block align-middle ${diaClase} text-white px-3 lg:px-4 py-0.5 rounded-full text-lg lg:text-2xl">${cantidadDias} días</span>
                    </p>
                    <p class="text-base lg:text-lg text-gray-500 font-semibold break-words">del ${formatearFechaCorta(desde)}${hasta !== desde ? ' al ' + formatearFechaCorta(hasta) : ''}</p>
                </div>`;
    } else {
        encabezado = `<p class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-gray-800 leading-tight break-words min-w-0">${esc(formatearFechaISO(fechas[0]))}</p>`;
    }

    const discosHtml = discos.length
        ? `<span class="inline-flex items-center gap-1 mr-2 font-extrabold text-blue-700 text-xl lg:text-2xl"><i class="fas fa-compact-disc"></i>Disco ${esc(discos.join(' · '))}</span>`
        : '';

    const comprobantes = Array.isArray(pago.comprobantes) && pago.comprobantes.length
        ? pago.comprobantes.filter(Boolean)
        : (pago.comprobante ? [pago.comprobante] : []);

    let comprobanteHtml = '';
    if (estado !== 'anulado' && comprobantes.length) {
        const botones = comprobantes.map((ruta, i) =>
            `<button type="button" data-ver-recibo="${esc(rutaRecibo(pago, i))}" class="w-full rounded-xl bg-blue-50 text-blue-700 border border-blue-200 py-3 px-4 text-lg font-bold"><i class="fas fa-eye mr-2"></i>${comprobantes.length > 1 ? 'Ver comprobante ' + (i + 1) : 'Ver comprobante'}</button>`
        ).join('');
        comprobanteHtml = `<div class="mt-4 flex flex-col gap-2">${botones}</div>`;
    }

    let motivoHtml = '';
    if (estado === 'anulado') {
        motivoHtml = `
            <div class="mt-3 rounded-xl bg-red-50 border border-red-200 p-4">
                <p class="font-bold text-red-700 text-lg">Motivo: <span class="font-semibold">${esc(pago.motivo_rechazo || 'Sin motivo especificado.')}</span></p>
                <p class="text-red-600 text-sm mt-1">Estos días ya están libres. Puedes volver a pagarlos desde la sección <strong>Pagar</strong>.</p>
            </div>`;
    } else if (estado === 'incompleto') {
        motivoHtml = `
            <div class="mt-3 rounded-xl bg-orange-50 border border-orange-200 p-4">
                <p class="font-bold text-orange-700 text-base"><i class="fas fa-triangle-exclamation mr-1"></i>Pago incompleto</p>
                <p class="text-orange-600 text-sm mt-1">${esc(pago.motivo_rechazo || 'Adjunta el comprobante del valor restante.')}</p>
            </div>`;
    }

    const subirRestanteHtml = estado === 'incompleto'
        ? `<button type="button" data-subir-restante="${pago.id}" class="mt-3 w-full rounded-xl bg-amber-400 text-white border border-amber-500 py-3 px-4 text-lg font-bold hover:bg-orange-500 transition-colors"><i class="fas fa-upload mr-2"></i>Subir valor restante</button>`
        : '';

    return `
            <div class="cardPago bg-white rounded-3xl p-6 lg:p-7 shadow-sm border-2 ${bordeTarjeta}" data-discos="${esc(discos.join(' '))}">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    ${encabezado}
                    <span class="${etiquetaClase} text-sm sm:text-lg font-bold px-3 sm:px-4 py-1.5 rounded-full flex items-center gap-2 shrink-0 whitespace-nowrap">
                        <i class="fas ${etiquetaIcono}"></i> ${etiquetaEstado}
                    </span>
                </div>
                <p class="text-lg lg:text-xl text-gray-500 mt-1 break-words">
                    ${discosHtml}
                    <span class="font-bold text-gray-600">${verboPago} ${esc(formatearFechaISO(pago.fecha_pago))}</span>
                </p>
                <hr class="border-gray-100 my-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-lg lg:text-xl text-gray-500 break-words">${cantidadDias} día(s) · <span class="font-bold text-gray-700">$ ${esc(pago.montoFmt)}</span></p>
                    <i class="fas ${etiquetaIcono} ${iconoEstado} text-4xl lg:text-5xl shrink-0"></i>
                </div>
                ${comprobanteHtml}
                ${motivoHtml}
                ${subirRestanteHtml}
            </div>`;
}

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

function mostrarAviso(mensaje, esError) {
    const aviso = document.createElement('div');
    aviso.className = 'fixed bottom-6 right-6 z-[80] px-5 py-3 rounded-2xl shadow-2xl text-lg font-bold text-white ' + (esError ? 'bg-red-600' : 'bg-green-600');
    aviso.textContent = mensaje;
    document.body.appendChild(aviso);
    setTimeout(() => aviso.remove(), 4500);
}

function validarArchivoRestante(archivo) {
    const tipos = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!tipos.includes(archivo.type)) return 'Solo se permiten imágenes JPG, PNG, WEBP y documentos PDF.';
    return '';
}

function inicializarSubirRestante(onCompletado) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp,application/pdf';
    input.className = 'hidden';
    let pagoEnviar = null;
    let botonEnviar = null;

    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-subir-restante]');
        if (!boton) return;
        pagoEnviar = boton.dataset.subirRestante;
        botonEnviar = boton;
        input.value = '';
        input.click();
    });

    input.addEventListener('change', async () => {
        const archivo = input.files[0];
        if (!archivo) return;
        const error = validarArchivoRestante(archivo);
        if (error) {
            mostrarAviso(error, true);
            input.value = '';
            return;
        }
        if (botonEnviar) {
            botonEnviar.disabled = true;
            botonEnviar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subiendo…';
        }
        const formData = new FormData();
        formData.append('accion', 'adjuntar_comprobante');
        formData.append('pago_id', pagoEnviar);
        formData.append('archivo', archivo);
        try {
            const response = await fetch('../../Controllers/PagoController.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                mostrarAviso(data.message || 'Comprobante adjuntado. El pago vuelve a estar en espera.');
                if (typeof onCompletado === 'function') onCompletado(pagoEnviar);
            } else {
                mostrarAviso(data.message || 'No se pudo adjuntar el comprobante.', true);
            }
        } catch (e) {
            mostrarAviso('Error de conexión con el servidor.', true);
        } finally {
            input.value = '';
            // El SSE actualiza la tarjeta al nuevo estado (en espera) en unos segundos.
            pagoEnviar = null;
            botonEnviar = null;
        }
    });

    document.body.appendChild(input);
}