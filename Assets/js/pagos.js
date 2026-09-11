// Assets/js/pagos.js
// La plantilla de tarjeta (tarjetaPago) y sus utilidades viven en tarjeta_pago.js.

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
    inputDisco.addEventListener('input', () => {
        inputDisco.value = normalizarDiscoBruto(inputDisco.value);
        if (limpiarDisco) limpiarDisco.classList.toggle('hidden', inputDisco.value === '');
        renderLista(inputDisco.value);
        aplicarFiltro();
    });
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

    inicializarSubirRestante();

    // ---------- Tiempo real (SSE) ----------
    const eventos = new EventSource('../../Controllers/ConductorStreamController.php');
    let ultimoHash = null;

    function aplicarSnapshot(evento) {
        try {
            const datos = JSON.parse(evento.data);
            if (!datos || !Array.isArray(datos.pagos)) return;
            if (datos.hash && datos.hash === ultimoHash) return;
            ultimoHash = datos.hash || null;
            if (Array.isArray(datos.discos)) discos = datos.discos;
            window.renderizarPagos(datos.pagos);
        } catch (error) {
            console.error(error);
        }
    }

    eventos.addEventListener('snapshot', aplicarSnapshot);
    eventos.addEventListener('message', aplicarSnapshot);
    window.addEventListener('beforeunload', () => eventos?.close());
});
