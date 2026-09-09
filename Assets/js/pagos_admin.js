// Assets/js/pagos_admin.js
document.addEventListener('DOMContentLoaded', () => {
    const URL_CONTROLADOR = '../../Controllers/AdminPagoController.php';

    const modalAlertaComprobante = document.getElementById('modalAlertaComprobante');
    const modalAprobar = document.getElementById('modalAprobar');
    const modalDesaprobar = document.getElementById('modalDesaprobar');
    const motivoRechazo = document.getElementById('motivoRechazo');
    const contadorMotivo = document.getElementById('contadorMotivo');
    const errorAprobar = document.getElementById('errorAprobar');
    const errorDesaprobar = document.getElementById('errorDesaprobar');

    const tablaPagos = document.getElementById('tablaPagos');
    let actualizacionPendiente = null;
    const aplicarActualizacion = () => {
        if (!actualizacionPendiente || document.querySelector('[data-pago][data-editando]') ||
            [modalAprobar, modalDesaprobar, modalAlertaComprobante].some((modal) => modal && !modal.classList.contains('hidden'))) return;
        tablaPagos.innerHTML = actualizacionPendiente.html;
        tablaPagos.dataset.hash = actualizacionPendiente.hash;
        actualizacionPendiente = null;
        inicializarCodigos();
    };
    tablaPagos.addEventListener('input', (evento) => {
        const raiz = evento.target.closest('[data-pago]');
        if (raiz && raiz.dataset.bloqueado !== '1') raiz.dataset.editando = '1';
    });
    if (typeof EventSource !== 'undefined') {
        const eventos = new EventSource('../../Controllers/PagosStreamController.php' + window.location.search);
        eventos.addEventListener('pagos', (evento) => {
            try {
                const datos = JSON.parse(evento.data);
                if (datos.hash === tablaPagos.dataset.hash) return;
                actualizacionPendiente = datos;
                aplicarActualizacion();
            } catch (error) { console.error('No se pudieron actualizar los pagos.', error); }
        });
        window.addEventListener('beforeunload', () => eventos.close());
    }

    let pagoAprobar = null;
    let pagoDesaprobar = null;

    function abrirModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        aplicarActualizacion();
    }

    async function enviarAccion(datos) {
        const formData = new FormData();
        Object.entries(datos).forEach(([clave, valor]) => formData.append(clave, valor));
        const response = await fetch(URL_CONTROLADOR, { method: 'POST', body: formData });
        return response.json();
    }

    // ---------- Búsqueda automática en los filtros (debounce 400ms) ----------
    const formularioFiltros = document.querySelector('form[action="pagos.php"]');
    let temporizadorFiltros = null;
    const aplicarFiltrosAutomaticos = () => {
        if (temporizadorFiltros) clearTimeout(temporizadorFiltros);
        temporizadorFiltros = setTimeout(() => {
            if (formularioFiltros) formularioFiltros.requestSubmit();
        }, 400);
    };
    if (formularioFiltros) {
        formularioFiltros.querySelectorAll('input, select').forEach((control) => {
            const enviar = () => aplicarFiltrosAutomaticos();
            if (control.tagName === 'SELECT') {
                control.addEventListener('change', enviar);
            } else {
                control.addEventListener('input', enviar);
            }
        });
    }

    // ---------- Códigos de comprobante (inputs + botón "+") ----------
    function actualizarBotonesQuitar(contenedor) {
        const raiz = contenedor.closest('[data-pago]');
        const bloqueado = !!(raiz && raiz.dataset.bloqueado === '1');
        const unico = contenedor.querySelectorAll('input').length <= 1;
        contenedor.querySelectorAll('[data-quitar-codigo]').forEach((boton) => {
            boton.disabled = bloqueado || unico;
            boton.title = bloqueado ? 'Comprobante bloqueado' : (unico ? 'Debe quedar al menos un comprobante' : 'Quitar');
        });
    }

    function agregarInputCodigo(contenedor, valor) {
        const fila = document.createElement('div');
        fila.className = 'flex items-center gap-1.5';
        const input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.pattern = '[0-9]+';
        input.addEventListener('input', () => { input.value = input.value.replace(/[^0-9]/g, ''); });
        input.value = valor || '';
        input.placeholder = 'Código del comprobante';
        input.className = 'flex-1 min-w-0 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.title = 'Quitar';
        quitar.dataset.quitarCodigo = '';
        quitar.className = 'text-gray-400 hover:text-red-500 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shrink-0';
        quitar.innerHTML = '<i class="fas fa-times-circle"></i>';
        quitar.addEventListener('click', () => {
            if (contenedor.querySelectorAll('input').length <= 1) return;
            contenedor.closest('[data-pago]').dataset.editando = '1';
            fila.remove();
            actualizarBotonesQuitar(contenedor);
        });
        fila.appendChild(input);
        fila.appendChild(quitar);
        contenedor.appendChild(fila);
        actualizarBotonesQuitar(contenedor);
        return input;
    }

    function inicializarCodigos() {
        document.querySelectorAll('[data-pago]').forEach((raiz) => {
            if (raiz.dataset.inicializado) return;
            raiz.dataset.inicializado = '1';

            const contenedor = raiz.querySelector('.contenedorCodigos');
            const codigosGuardados = (raiz.dataset.codigos || '')
                .split(' | ')
                .map((c) => c.trim())
                .filter(Boolean);
            const inicial = codigosGuardados.length > 0 ? codigosGuardados : [''];
            inicial.forEach((codigo) => agregarInputCodigo(contenedor, codigo));

            if (raiz.dataset.bloqueado === '1') {
                contenedor.querySelectorAll('input').forEach((input) => { input.disabled = true; });
                return;
            }

            raiz.querySelectorAll('[data-agregar-codigo]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    raiz.dataset.editando = '1';
                    const input = agregarInputCodigo(contenedor, '');
                    input.focus();
                });
            });

            raiz.querySelectorAll('[data-guardar-codigos]').forEach((boton) => {
                boton.addEventListener('click', () => guardarCodigos(raiz));
            });
        });
    }

    async function guardarCodigos(raiz) {
        const pagoId = raiz.dataset.pago;
        const contenedor = raiz.querySelector('.contenedorCodigos');
        const estado = raiz.querySelector('[data-estado-codigos]');
        const boton = raiz.querySelector('[data-guardar-codigos]');
        if (!pagoId || !contenedor || !boton) return;

        const codigos = [...contenedor.querySelectorAll('input')]
            .map((input) => input.value.trim())
            .filter(Boolean);

        if (estado) estado.textContent = '';
        boton.disabled = true;
        const textoOriginal = boton.textContent;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Guardando…';
        try {
            if (codigos.length === 0 || codigos.some((codigo) => !/^[0-9]+$/.test(codigo))) {
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
                if (estado) {
                    estado.textContent = 'Ingresa al menos un número de comprobante, solo con dígitos.';
                    estado.className = 'text-xs font-bold text-red-600';
                }
                return;
            }
            const formData = new FormData();
            formData.append('accion', 'guardar_comprobantes');
            formData.append('pago_id', pagoId);
            codigos.forEach((codigo) => formData.append('codigos[]', codigo));
            const response = await fetch(URL_CONTROLADOR, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                delete raiz.dataset.editando;
                raiz.dataset.codigos = data.codigos || '';
                actualizacionPendiente = null;
                if (estado) {
                    estado.textContent = 'Guardado.';
                    estado.className = 'text-xs font-bold text-green-600';
                }
                setTimeout(() => {
                    if (estado) estado.textContent = '';
                }, 2500);
            } else {
                if (estado) {
                    estado.textContent = data.message || 'No se pudo guardar.';
                    estado.className = 'text-xs font-bold text-red-600';
                }
            }
        } catch (e) {
            if (estado) {
                estado.textContent = 'Error de conexión con el servidor.';
                estado.className = 'text-xs font-bold text-red-600';
            }
        } finally {
            boton.innerHTML = textoOriginal;
            boton.disabled = false;
        }
    }

    inicializarCodigos();

    document.addEventListener('click', async (evento) => {
        const editar = evento.target.closest('[data-editar-estado]');
        if (editar) {
            editar.nextElementSibling.classList.toggle('hidden');
            return;
        }
        const espera = evento.target.closest('[data-espera]');
        if (espera) {
            if (!confirm('¿Cambiar este pago a En espera?')) return;
            espera.disabled = true;
            try {
                const data = await enviarAccion({ accion: 'en_espera', pago_id: espera.dataset.espera });
                if (data.status === 'success') window.location.reload();
                else alert(data.message);
            } catch (error) { alert('No se pudo actualizar el estado.'); }
            finally { espera.disabled = false; }
            return;
        }

        const botonAprobar = evento.target.closest('[data-aprobar]');
        if (botonAprobar) {
            const raiz = botonAprobar.closest('tr').querySelector('[data-pago]');
            const codigos = [...raiz.querySelectorAll('input')].map((input) => input.value.trim()).filter(Boolean);
            if (!codigos.length || codigos.some((codigo) => !/^[0-9]+$/.test(codigo))) {
                if (modalAlertaComprobante) abrirModal(modalAlertaComprobante);
                return;
            }
            pagoAprobar = botonAprobar.dataset.aprobar;
            if (errorAprobar) errorAprobar.classList.add('hidden');
            abrirModal(modalAprobar);
            return;
        }

        const botonDesaprobar = evento.target.closest('[data-desaprobar]');
        if (botonDesaprobar) {
            pagoDesaprobar = botonDesaprobar.dataset.desaprobar;
            if (motivoRechazo) {
                motivoRechazo.value = '';
                if (contadorMotivo) contadorMotivo.textContent = '0';
            }
            if (errorDesaprobar) errorDesaprobar.classList.add('hidden');
            abrirModal(modalDesaprobar);
            return;
        }

        const cerrar = evento.target.closest('[data-cerrar]');
        if (cerrar) {
            const modal = document.getElementById(cerrar.dataset.cerrar);
            if (modal) cerrarModal(modal);
        }
    });

    if (modalAlertaComprobante) {
        document.getElementById('btnCerrarAlertaComprobante').addEventListener('click', () => cerrarModal(modalAlertaComprobante));
    }

    const confirmarAprobar = document.getElementById('confirmarAprobar');
    if (confirmarAprobar) {
        confirmarAprobar.addEventListener('click', async () => {
            if (!pagoAprobar) return;
            const boton = confirmarAprobar;
            boton.disabled = true;
            const textoOriginal = boton.textContent;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Procesando…';
            try {
                const raiz = [...document.querySelectorAll('[data-pago]')].find((fila) => fila.dataset.pago === pagoAprobar);
                const codigos = [...raiz.querySelectorAll('input')].map((input) => input.value.trim()).filter(Boolean).join(' | ');
                const data = await enviarAccion({ accion: 'aprobar', pago_id: pagoAprobar, codigos });
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    if (errorAprobar) {
                        errorAprobar.textContent = data.message || 'No se pudo aprobar el pago.';
                        errorAprobar.classList.remove('hidden');
                    }
                    boton.innerHTML = textoOriginal;
                    boton.disabled = false;
                }
            } catch (e) {
                if (errorAprobar) {
                    errorAprobar.textContent = 'Error de conexión con el servidor.';
                    errorAprobar.classList.remove('hidden');
                }
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
            }
        });
    }

    document.getElementById('cancelarAprobar').addEventListener('click', () => {
        cerrarModal(modalAprobar);
        pagoAprobar = null;
    });

    if (motivoRechazo) {
        motivoRechazo.addEventListener('input', () => {
            if (contadorMotivo) contadorMotivo.textContent = String(motivoRechazo.value.length);
        });
    }

    const confirmarDesaprobar = document.getElementById('confirmarDesaprobar');
    if (confirmarDesaprobar) {
        confirmarDesaprobar.addEventListener('click', async () => {
            if (!pagoDesaprobar) return;
            const motivo = motivoRechazo ? motivoRechazo.value.trim() : '';
            const mostrarError = (mensaje) => {
                if (errorDesaprobar) {
                    errorDesaprobar.textContent = mensaje;
                    errorDesaprobar.classList.remove('hidden');
                }
            };
            if (!motivo) {
                mostrarError('El motivo del rechazo es obligatorio.');
                if (motivoRechazo) motivoRechazo.focus();
                return;
            }

            const boton = confirmarDesaprobar;
            boton.disabled = true;
            const textoOriginal = boton.textContent;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Procesando…';
            try {
                const data = await enviarAccion({ accion: 'desaprobar', pago_id: pagoDesaprobar, motivo });
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    mostrarError(data.message || 'No se pudo anular el pago.');
                    boton.innerHTML = textoOriginal;
                    boton.disabled = false;
                }
            } catch (e) {
                mostrarError('Error de conexión con el servidor.');
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
            }
        });
    }

    document.getElementById('cancelarDesaprobar').addEventListener('click', () => {
        cerrarModal(modalDesaprobar);
        pagoDesaprobar = null;
    });
});