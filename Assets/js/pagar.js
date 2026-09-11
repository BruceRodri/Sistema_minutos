// Assets/js/pagar.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/PagoController.php';
    const datos = JSON.parse(document.getElementById('datosPagar').textContent);
    const pagables = datos.pagables || [];
    const esMovil = () => !window.matchMedia('(min-width: 768px)').matches;

    function esc(texto) {
        return String(texto ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    let incompletos = datos.incompletos || [];
    let ultimosPendientes = Array.isArray(pagables) ? pagables : [];

    inicializarSubirRestante((pagoId) => {
        incompletos = incompletos.filter((p) => String(p.id) !== String(pagoId));
        window.renderizarPendientes(ultimosPendientes);
    });

    let discos = Array.isArray(datos.discos) ? datos.discos : [];
    let indiceTarjetas = 0;
    let flujoActivo = null;   // 'card' | 'multi'
    let idsAPagar = [];
    let idsIncompletosApagar = [];

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

    function discExacto(disco, bruto) {
        const b = normalizarDiscoBruto(bruto).replace(/^0+/, '');
        const d = String(disco || '').replace(/^0+/, '');
        return b !== '' && d === b;
    }

    function mostrarAlerta(elemento, tipo, mensaje) {
        if (!elemento) return;
        clearTimeout(Number(elemento.dataset.timer) || 0);
        elemento.textContent = mensaje;
        elemento.className = 'p-5 rounded-2xl text-center text-xl lg:text-2xl font-bold ' +
            (tipo === 'success'
                ? 'bg-green-100 text-green-800 border-2 border-green-200'
                : 'bg-red-100 text-red-800 border-2 border-red-200');
        elemento.classList.remove('hidden');
        elemento.dataset.timer = setTimeout(() => { elemento.classList.add('hidden'); }, 5000);
    }

    function validarArchivo(archivo) {
        const tipoPermitido = archivo.type.startsWith('image/') || archivo.type === 'application/pdf';
        if (!tipoPermitido) return 'Solo se permiten imágenes y documentos PDF.';
        return null;
    }

    // Convierte un pago incompleto al mismo formato de tarjetas del carrusel.
    function itemIncompleto(pagoInc) {
        const disco = (Array.isArray(pagoInc.discos) && String(pagoInc.discos[0] || '') !== '')
            ? pagoInc.discos[0]
            : '';
        return {
            _incompleto: true,
            id: 'inc-' + pagoInc.id,
            pagoId: pagoInc.id,
            disco: disco,
            fechaLegible: pagoInc.fechaPagoLegible || formatearFechaISO(pagoInc.fecha_pago),
            valor: typeof pagoInc.monto === 'number' ? pagoInc.monto : parseFloat(pagoInc.monto || 0),
            valorFmt: pagoInc.montoFmt || Number(pagoInc.monto || 0).toFixed(2),
            motivo: pagoInc.motivo_rechazo || 'Adjunta el comprobante del valor restante.',
            ruta: ''
        };
    }

    async function ejecutarPago() {
        const archivo = inputComprobante.files[0];
        if (!archivo) return;
        const alerta = flujoActivo === 'card' ? alertaTarjetas : alertaVarios;
        if (alerta) {
            clearTimeout(Number(alerta.dataset.timer) || 0);
            alerta.textContent = 'Subiendo comprobante…';
            alerta.className = 'p-5 rounded-2xl text-center text-xl lg:text-2xl font-bold bg-blue-50 text-blue-700 border-2 border-blue-200';
            alerta.classList.remove('hidden');
        }

        let errores = [];
        let completados = 0;

        if (idsIncompletosApagar.length && idsAPagar.length) {
            const formData = new FormData();
            formData.append('accion', 'completar_y_pagar');
            idsIncompletosApagar.forEach((id) => formData.append('pago_ids[]', id));
            idsAPagar.forEach((id) => formData.append('obligaciones_ids[]', id));
            formData.append('archivo', archivo);
            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    completados++;
                    incompletos = incompletos.filter((p) => !idsIncompletosApagar.some((id) => String(p.id) === String(id)));
                    quitarPagados(idsAPagar);
                } else {
                    errores.push(data.message);
                }
            } catch (error) {
                console.error(error);
                errores.push('Error de conexión con el servidor.');
            }
        } else if (idsIncompletosApagar.length) {
            for (const pagoId of idsIncompletosApagar) {
                const formData = new FormData();
                formData.append('accion', 'adjuntar_comprobante');
                formData.append('pago_id', pagoId);
                formData.append('archivo', archivo);
                try {
                    const response = await fetch(urlControlador, { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.status === 'success') {
                        completados++;
                        incompletos = incompletos.filter((p) => String(p.id) !== String(pagoId));
                    } else {
                        errores.push(data.message);
                    }
                } catch (error) {
                    console.error(error);
                    errores.push('Error de conexión con el servidor.');
                }
            }
        }

        if (idsAPagar.length) {
            const formData = new FormData();
            formData.append('accion', 'pagar_varios');
            idsAPagar.forEach((id) => formData.append('obligaciones_ids[]', id));
            formData.append('archivo', archivo);
            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    completados++;
                    quitarPagados(idsAPagar);
                } else {
                    errores.push(data.message);
                }
            } catch (error) {
                console.error(error);
                errores.push('Error de conexión con el servidor.');
            }
        }

        if (completados > 0) {
            const mensaje = idsAPagar.length && idsIncompletosApagar.length
                ? 'Pago incompleto completado y días agregados en un solo pago.'
                : (idsAPagar.length
                    ? 'Pago registrado correctamente.'
                    : 'Comprobante adjuntado. El pago vuelve a estar en espera.');
            mostrarAlerta(alerta, 'success', mensaje);
            if (idsIncompletosApagar.length) window.renderizarPendientes(ultimosPendientes);
            if (window.marcarCambioPendiente) window.marcarCambioPendiente(8000);
        } else if (errores.length) {
            mostrarAlerta(alerta, 'error', errores[0]);
        }

        inputComprobante.value = '';
        flujoActivo = null;
        idsAPagar = [];
        idsIncompletosApagar = [];
    }

    function quitarPagados(ids) {
        const pagados = new Set(ids.map((id) => String(id)));
        ultimosPendientes = ultimosPendientes.filter((p) => !pagados.has(String(p.id)));
        if (carrusel) {
            carrusel.querySelectorAll('.cardPagar').forEach((card) => {
                if (pagados.has(String(card.dataset.id)) || (card.dataset.pagoId && pagados.has(String(card.dataset.pagoId)))) {
                    card.remove();
                }
            });
            todasLasTarjetas = [...carrusel.querySelectorAll('.cardPagar')];
        }
        document.querySelectorAll('.checkGlob').forEach((check) => {
            if (pagados.has(String(check.value))) check.closest('.checkGlobFila')?.remove();
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
    const bannerIncompletos = document.getElementById('bannerIncompletos');
    const carrusel = document.getElementById('carrusel');
    const puntosTarjetas = document.getElementById('puntosTarjetas');
    const sinResultados = document.getElementById('sinResultadosDisco');
    const volverTarjetas = document.getElementById('volverTarjetas');
    const vistaCarousel = document.getElementById('vistaCarousel');
    const vistaMulti = document.getElementById('vistaMulti');
    let filasDia = [...document.querySelectorAll('.checkGlobFila')];
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

    function brutoActual() {
        return inputDisco ? normalizarDiscoBruto(inputDisco.value) : '';
    }

    function tarjetasFiltradas() {
        const bruto = brutoActual();
        return (
            bruto === ''
                ? []
                : todasLasTarjetas.filter((c) => discExacto(c.dataset.disco, bruto))
        );
    }

    // ---------- Plantillas de tarjeta del carrusel ----------
    function tarjetaCompleta(p) {
        if (p._incompleto) {
            return `
                <article data-id="${p.id}" data-incompleto="1" data-pago-id="${p.pagoId}"
                         data-disco="${esc(p.disco)}"
                         data-fecha="${esc(p.fechaLegible)}"
                         data-valor="${esc(p.valorFmt)}"
                         class="cardPagar snap-center shrink-0 w-[82%] max-w-[340px] md:w-auto md:max-w-none md:shrink min-h-0 bg-gradient-to-br from-orange-400 to-orange-600 rounded-3xl p-7 lg:p-9 shadow-xl text-white cursor-pointer active:scale-95 transition-transform">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-orange-100 text-sm lg:text-base font-bold uppercase tracking-widest"><i class="fas fa-triangle-exclamation mr-1"></i>Pago incompleto</p>
                            <p class="text-2xl lg:text-3xl font-bold mt-1">${esc(p.fechaLegible)}</p>
                        </div>
                        <span class="bg-white text-orange-600 font-extrabold px-4 py-2 rounded-full text-lg lg:text-xl shadow max-w-full break-all">Disco ${esc(p.disco)}</span>
                    </div>
                    <div class="mt-9 lg:mt-11 text-center">
                        <div class="text-7xl lg:text-8xl font-extrabold leading-none">
                            <span class="align-top text-4xl lg:text-5xl">$</span>${esc(p.valorFmt)}
                        </div>
                    </div>
                    <div class="mt-7 rounded-2xl bg-white/15 border border-white/30 px-4 py-3">
                        <p class="text-sm lg:text-base font-bold text-white break-words"><i class="fas fa-triangle-exclamation mr-1"></i>${esc(p.motivo)}</p>
                    </div>
                    <button type="button" data-subir-restante="${p.pagoId}"
                        class="mt-5 w-full flex items-center justify-center gap-2 rounded-xl bg-white py-3 text-lg font-extrabold text-orange-600 hover:bg-orange-100 transition-colors">
                        <i class="fas fa-upload"></i>Subir valor restante
                    </button>
                </article>`;
        }
        return `
            <article data-id="${p.id}"
                     data-disco="${esc(p.disco)}"
                     data-fecha="${esc(p.fechaLegible)}"
                     data-valor="${esc(p.valorFmt)}"
                     class="cardPagar snap-center shrink-0 w-[82%] max-w-[340px] md:w-auto md:max-w-none md:shrink min-h-0 bg-gradient-to-br from-blue-500 to-blue-700 rounded-3xl p-7 lg:p-9 shadow-xl text-white cursor-pointer active:scale-95 transition-transform">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-blue-100 text-sm lg:text-base font-bold uppercase tracking-widest">${p.hoy ? 'Pago de hoy' : 'Pago pendiente'}</p>
                        <p class="text-2xl lg:text-3xl font-bold mt-1">${esc(p.fechaLegible)}</p>
                    </div>
                    <span class="bg-white text-blue-700 font-extrabold px-4 py-2 rounded-full text-lg lg:text-xl shadow max-w-full break-all">Disco ${esc(p.disco)}</span>
                </div>
                <div class="mt-9 lg:mt-11 text-center">
                    <div class="text-7xl lg:text-8xl font-extrabold leading-none">
                        <span class="align-top text-4xl lg:text-5xl">$</span>${esc(p.valorFmt)}
                    </div>
                </div>
                <div class="mt-9 lg:mt-11 flex items-center gap-3 justify-center">
                    <i class="fas fa-route text-2xl"></i>
                    <span class="text-xl lg:text-2xl font-bold truncate">${esc(p.ruta)}</span>
                </div>
                <span class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-white py-3 text-lg font-extrabold text-blue-700"><i class="fas fa-money-bill-wave"></i>Pagar</span>
            </article>`;
    }

    // ---------- Plantillas de fila para el pago múltiple ----------
    function filaMulti(p) {
        if (p._incompleto) {
            return `
                <label class="checkIncFila checkGlobFila flex items-center gap-4 p-5 lg:p-6 cursor-pointer hover:bg-orange-50 transition-colors bg-orange-50/60">
                    <input type="checkbox" class="checkGlob w-7 h-7 lg:w-8 lg:h-8 accent-orange-500 shrink-0"
                           value="${p.pagoId}"
                           data-tipo="inc"
                           data-valor="${esc(p.valor)}"
                           data-disco="${esc(p.disco)}"
                           data-fecha="${esc(p.fechaLegible)}"
                           data-valorfmt="${esc(p.valorFmt)}">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-orange-700 text-xl lg:text-2xl"><i class="fas fa-triangle-exclamation mr-1"></i>${esc(p.fechaLegible)}</p>
                        <p class="text-sm lg:text-base text-orange-600 truncate mt-1">Pago incompleto · ${esc(p.motivo)}</p>
                    </div>
                    <p class="font-extrabold text-orange-700 text-2xl lg:text-3xl whitespace-nowrap">$ ${esc(p.valorFmt)}</p>
                </label>`;
        }
        return `
            <label class="checkDiaFila checkGlobFila flex items-center gap-4 p-5 lg:p-6 cursor-pointer hover:bg-blue-50 transition-colors">
                <input type="checkbox" class="checkDia checkGlob w-7 h-7 lg:w-8 lg:h-8 accent-blue-600 shrink-0"
                       value="${p.id}"
                       data-tipo="oblig"
                       data-valor="${esc(p.valor)}"
                       data-disco="${esc(p.disco)}"
                       data-fecha="${esc(p.fechaLegible)}"
                       data-valorfmt="${esc(p.valorFmt)}">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800 text-xl lg:text-2xl">${esc(p.fechaLegible)}</p>
                    <p class="text-lg text-gray-500 truncate mt-1">
                        <i class="fas fa-route mr-1"></i>${esc(p.ruta)}
                        <span class="mx-1">·</span>Disco ${esc(p.disco)}
                    </p>
                </div>
                <p class="font-extrabold text-blue-700 text-2xl lg:text-3xl whitespace-nowrap">$ ${esc(p.valorFmt)}</p>
            </label>`;
    }

    // ---------- Lista de discos (filtro en vivo, sin peticiones al servidor) ----------
    let listaSolicitada = false;

    function renderListaDiscos() {
        if (!listaDiscos) return;
        listaDiscos.innerHTML = '';
        const candidatos = discos.filter((disco) => discCoincide(disco, inputDisco.value));
        if (!candidatos.length) {
            listaDiscos.innerHTML = '<p class="px-4 py-4 text-xl text-gray-400 font-semibold">Sin discos disponibles.</p>';
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

    function abrirLista() {
        listaSolicitada = true;
        renderListaDiscos();
    }

    function abrirListaConBusqueda() {
        listaSolicitada = true;
        renderListaDiscos();
    }

    // ---------- Selección y limpieza de disco ----------
    function seleccionarDisco(disco) {
        listaSolicitada = false;
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
        const bruto = brutoActual();
        if (bannerIncompletos) bannerIncompletos.classList.add('hidden');
        if (bruto === '') {
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

        if (bannerIncompletos) {
            const hayIncompletos = visibles.some((c) => c.dataset.incompleto === '1');
            bannerIncompletos.classList.toggle('hidden', !hayIncompletos);
        }

        verVarios.classList.toggle('hidden', visibles.length <= 1);
        bloqueCarrusel.classList.remove('hidden');
        sinResultados.classList.add('hidden');
        indiceTarjetas = Math.min(indiceTarjetas, visibles.length - 1);

        filasDia.forEach((fila) => {
            const discoFila = fila.querySelector('.checkGlob').dataset.disco;
            const ok = discExacto(discoFila, bruto);
            fila.classList.toggle('hidden', !ok);
            if (!ok) fila.querySelector('.checkGlob').checked = false;
        });
        calcularTotal();
    }

    function desmarcarTodos() {
        document.querySelectorAll('.checkGlob').forEach((c) => { c.checked = false; });
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
            if (limpiarDisco) limpiarDisco.classList.toggle('hidden', inputDisco.value === '');
            abrirListaConBusqueda();
            aplicarFiltro();
            renderCarrusel();
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
        const checksVisibles = [...document.querySelectorAll('.checkGlob')]
            .filter((c) => !c.closest('.checkGlobFila').classList.contains('hidden'));
        let total = 0;
        checksVisibles.forEach((c) => { if (c.checked) total += parseFloat(c.dataset.valor || 0); });
        totalVarios.textContent = total.toFixed(2);
        const cant = checksVisibles.filter((c) => c.checked).length;
        detalleVarios.textContent = total > 0
            ? `${cant} seleccionado(s) · Disco ${inputDisco.value}`
            : 'Selecciona al menos un día.';
    }

    function resaltar(texto) {
        return `<span class="font-extrabold text-blue-700">${esc(texto)}</span>`;
    }

    function mensajeConfirmar(titulo, disco, fecha, valor) {
        return `${titulo} del ${resaltar('DISCO ' + disco)} del ${resaltar(fecha)} por $ ${resaltar(valor)}?`;
    }

    const listaDias = document.getElementById('listaDiasPagables');

    // ---------- Render principal: incompletos primero + pendientes ----------
    window.renderizarPendientes = function (pendientes) {
        ultimosPendientes = Array.isArray(pendientes) ? pendientes : [];
        const incItems = incompletos.map(itemIncompleto);
        const todos = [...incItems, ...ultimosPendientes];

        if (carrusel) {
            carrusel.innerHTML = todos.map(tarjetaCompleta).join('');
        }
        if (listaDias) {
            if (!todos.length) {
                listaDias.innerHTML = '<div class="p-8 text-center"><i class="fas fa-circle-check text-green-500 text-4xl mb-3"></i><p class="text-2xl text-gray-600">No hay pagos pendientes para este disco.</p></div>';
            } else {
                listaDias.innerHTML = todos.map(filaMulti).join('');
            }
        }
        todasLasTarjetas = carrusel ? [...carrusel.querySelectorAll('.cardPagar')] : [];
        filasDia = [...document.querySelectorAll('.checkGlobFila')];
        desmarcarTodos();
        aplicarFiltro();
        renderCarrusel();
        calcularTotal();
    };

    if (listaDias) {
        listaDias.addEventListener('change', (evento) => {
            if (evento.target.classList.contains('checkGlob')) calcularTotal();
        });
    }

    if (btnPagarVarios) {
        btnPagarVarios.addEventListener('click', () => {
            const seleccionados = [...document.querySelectorAll('.checkGlob')]
                .filter((c) => !c.closest('.checkGlobFila').classList.contains('hidden') && c.checked);
            if (!seleccionados.length) {
                mostrarAlerta(alertaVarios, 'error', 'Selecciona al menos un día para pagar.');
                return;
            }
            const idsObligacion = seleccionados.filter((c) => c.dataset.tipo !== 'inc').map((c) => c.value);
            const idsIncompletos = seleccionados.filter((c) => c.dataset.tipo === 'inc').map((c) => c.value);
            const total = seleccionados.reduce((suma, c) => suma + parseFloat(c.dataset.valor || 0), 0);
            const disco = seleccionados[0].dataset.disco;

            let mensaje;
            if (idsIncompletos.length && !idsObligacion.length) {
                mensaje = `¿Está seguro de completar ${resaltar(`${idsIncompletos.length} pago(s) incompleto(s)`)} del ${resaltar('DISCO ' + disco)} por un total de $ ${resaltar(total.toFixed(2))}?`;
            } else if (idsIncompletos.length && idsObligacion.length) {
                mensaje = `¿Está seguro de completar ${resaltar(`${idsIncompletos.length} pago(s) incompleto(s)`)} y pagar ${resaltar(`${idsObligacion.length} día(s)`)} del ${resaltar('DISCO ' + disco)} por un total de $ ${resaltar(total.toFixed(2))}?`;
            } else if (idsObligacion.length === 1) {
                mensaje = mensajeConfirmar('¿Está seguro de registrar el pago', seleccionados[0].dataset.disco, seleccionados[0].dataset.fecha, seleccionados[0].dataset.valorfmt);
            } else {
                const fechas = seleccionados.map((c) => esc(c.dataset.fecha)).join(', ');
                mensaje = `¿Está seguro de registrar el pago de ${resaltar(`${idsObligacion.length} día(s)`)} del ${resaltar('DISCO ' + disco)} (${fechas}) por un total de $ ${resaltar(total.toFixed(2))}?`;
            }
            abrirModal(mensaje, 'multi', idsObligacion, idsIncompletos);
        });
    }

    // ---------- Confirmación vía modal ----------
    function abrirModal(mensaje, flujo, ids, idsIncompletos) {
        modalMensaje.innerHTML = mensaje;
        flujoActivo = flujo;
        idsAPagar = Array.isArray(ids) ? ids : [];
        idsIncompletosApagar = Array.isArray(idsIncompletos) ? idsIncompletos : [];
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        flujoActivo = null;
        idsAPagar = [];
        idsIncompletosApagar = [];
    }

    if (modalCancelar) modalCancelar.addEventListener('click', cerrarModal);
    if (document.getElementById('modalFondo')) {
        document.getElementById('modalFondo').addEventListener('click', cerrarModal);
        modalAceptar.addEventListener('click', () => {
            const flujo = flujoActivo;
            const ids = [...idsAPagar];
            const idsInc = [...idsIncompletosApagar];
            cerrarModal();
            if (!flujo || (!ids.length && !idsInc.length)) return;
            flujoActivo = flujo;
            idsAPagar = ids;
            idsIncompletosApagar = idsInc;
            [alertaTarjetas, alertaVarios].forEach((a) => { if (a) a.classList.add('hidden'); });
            inputComprobante.click();
        });
    }

    // ---------- Tarjeta del carrusel: incompleto sube restante, pendiente paga ----------
    if (carrusel) {
        carrusel.addEventListener('click', (evento) => {
            const card = evento.target.closest('.cardPagar');
            if (!card) return;
            if (card.dataset.incompleto === '1') {
                if (!evento.target.closest('[data-subir-restante]')) {
                    const boton = card.querySelector('[data-subir-restante]');
                    if (boton) boton.click();
                }
                return;
            }
            const mensaje = mensajeConfirmar('¿Está seguro de registrar el pago pendiente', card.dataset.disco, card.dataset.fecha, card.dataset.valor);
            abrirModal(mensaje, 'card', [card.dataset.id]);
        });
    }

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
                idsIncompletosApagar = [];
                return;
            }
            await ejecutarPago();
        });
    }

    // ---------- Tiempo real (SSE) ----------
    let silencioHasta = 0;
    window.marcarCambioPendiente = (milisegundos) => { silencioHasta = Date.now() + (milisegundos || 5000); };

    const eventos = new EventSource('../../Controllers/ConductorStreamController.php');
    let ultimoHash = null;

    function aplicarSnapshot(evento) {
        if (Date.now() < silencioHasta) return;
        try {
            const datos = JSON.parse(evento.data);
            if (!datos || !Array.isArray(datos.pendientes)) return;
            if (datos.hash && datos.hash === ultimoHash) return;
            ultimoHash = datos.hash || null;
            if (Array.isArray(datos.discos)) discos = datos.discos;
            incompletos = datos.incompletos || [];
            window.renderizarPendientes(datos.pendientes);
        } catch (error) {
            console.error(error);
        }
    }

    eventos.addEventListener('snapshot', aplicarSnapshot);
    eventos.addEventListener('message', aplicarSnapshot);
    window.addEventListener('beforeunload', () => eventos?.close());

    // Render inicial (evita pantalla vacía antes del primer snapshot del SSE).
    window.renderizarPendientes(pagables);
});