// Assets/js/pagos_admin.js
document.addEventListener('DOMContentLoaded', () => {
    const URL_CONTROLADOR = '../../Controllers/AdminPagoController.php';

    const modalComprobante = document.getElementById('modalComprobante');
    const contenedorComprobante = document.getElementById('contenedorComprobante');
    const modalAprobar = document.getElementById('modalAprobar');
    const modalDesaprobar = document.getElementById('modalDesaprobar');
    const motivoRechazo = document.getElementById('motivoRechazo');
    const contadorMotivo = document.getElementById('contadorMotivo');
    const errorAprobar = document.getElementById('errorAprobar');
    const errorDesaprobar = document.getElementById('errorDesaprobar');

    let pagoAprobar = null;
    let pagoDesaprobar = null;

    function abrirModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function esImagen(ruta) {
        return /\.(jpe?g|png|webp|gif)$/i.test(ruta);
    }

    function mostrarComprobante(ruta) {
        if (!contenedorComprobante) return;
        contenedorComprobante.innerHTML = '';
        if (esImagen(ruta)) {
            const img = document.createElement('img');
            img.src = ruta;
            img.alt = 'Comprobante de pago';
            img.className = 'max-w-full max-h-[70vh] object-contain rounded-lg shadow-lg';
            img.onerror = () => {
                contenedorComprobante.innerHTML = '<p class="text-gray-500 p-8"><i class="fas fa-triangle-exclamation mr-2 text-amber-500"></i>No se pudo cargar el comprobante.</p>';
            };
            contenedorComprobante.appendChild(img);
        } else {
            const frame = document.createElement('iframe');
            frame.src = ruta;
            frame.title = 'Comprobante de pago';
            frame.className = 'w-full h-[70vh] rounded-lg bg-white shadow-lg';
            frame.onerror = () => {
                contenedorComprobante.innerHTML = '<p class="text-gray-500 p-8">No se pudo cargar el comprobante.</p>';
            };
            contenedorComprobante.appendChild(frame);
        }
        abrirModal(modalComprobante);
    }

    async function enviarAccion(datos) {
        const formData = new FormData();
        Object.entries(datos).forEach(([clave, valor]) => formData.append(clave, valor));
        const response = await fetch(URL_CONTROLADOR, { method: 'POST', body: formData });
        return response.json();
    }

    // ---------- Códigos de comprobante (inputs + botón "+") ----------
    function agregarInputCodigo(contenedor, valor) {
        const fila = document.createElement('div');
        fila.className = 'flex items-center gap-1.5';
        const input = document.createElement('input');
        input.type = 'text';
        input.value = valor || '';
        input.placeholder = 'Código del comprobante';
        input.className = 'flex-1 min-w-0 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200';
        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.title = 'Quitar';
        quitar.className = 'text-gray-400 hover:text-red-500 transition-colors shrink-0';
        quitar.innerHTML = '<i class="fas fa-times-circle"></i>';
        quitar.addEventListener('click', () => fila.remove());
        fila.appendChild(input);
        fila.appendChild(quitar);
        contenedor.appendChild(fila);
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

            raiz.querySelectorAll('[data-agregar-codigo]').forEach((boton) => {
                boton.addEventListener('click', () => {
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
            if (codigos.length === 0) {
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
                if (estado) {
                    estado.textContent = 'Ingresa al menos un código.';
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

    document.addEventListener('click', (evento) => {
        const botonVer = evento.target.closest('[data-ver-comprobante]');
        if (botonVer) {
            mostrarComprobante(botonVer.dataset.verComprobante);
            return;
        }

        const botonAprobar = evento.target.closest('[data-aprobar]');
        if (botonAprobar) {
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

    const confirmarAprobar = document.getElementById('confirmarAprobar');
    if (confirmarAprobar) {
        confirmarAprobar.addEventListener('click', async () => {
            if (!pagoAprobar) return;
            const boton = confirmarAprobar;
            boton.disabled = true;
            const textoOriginal = boton.textContent;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Procesando…';
            try {
                const data = await enviarAccion({ accion: 'aprobar', pago_id: pagoAprobar });
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