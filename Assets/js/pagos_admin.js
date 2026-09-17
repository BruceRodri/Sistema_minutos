// Assets/js/pagos_admin.js
document.addEventListener('DOMContentLoaded', () => {
    const URL_CONTROLADOR = '../../Controllers/AdminPagoController.php';

    const modalAlertaComprobante = document.getElementById('modalAlertaComprobante');
    const modalAprobar = document.getElementById('modalAprobar');
    const modalDesaprobar = document.getElementById('modalDesaprobar');
    const modalIncompleto = document.getElementById('modalIncompleto');
    const motivoRechazo = document.getElementById('motivoRechazo');
    const contadorMotivo = document.getElementById('contadorMotivo');
    const motivoIncompleto = document.getElementById('motivoIncompleto');
    const contadorMotivoIncompleto = document.getElementById('contadorMotivoIncompleto');
    const errorAprobar = document.getElementById('errorAprobar');
    const errorDesaprobar = document.getElementById('errorDesaprobar');
    const errorIncompleto = document.getElementById('errorIncompleto');

    const tablaPagos = document.getElementById('tablaPagos');
    let actualizacionPendiente = null;
    const aplicarActualizacion = () => {
        if (!actualizacionPendiente || document.querySelector('[data-menu-acciones][aria-expanded="true"]') || document.querySelector('.acciones-pago[open], #modalExcedente[open]') || document.querySelector('[data-pago][data-editando]') ||
            [modalAprobar, modalDesaprobar, modalIncompleto, modalAlertaComprobante].some((modal) => modal && !modal.classList.contains('hidden'))) return;
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
    let pagoIncompleto = null;

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
        const fijo = !!(raiz && raiz.dataset.cantidadFija === '1');
        const unico = contenedor.querySelectorAll('input').length <= 1;
        contenedor.querySelectorAll('[data-quitar-codigo]').forEach((boton) => {
            boton.disabled = bloqueado || fijo || unico;
            boton.title = bloqueado ? 'Comprobante bloqueado'
                : (fijo ? 'Un número por archivo subido' : (unico ? 'Debe quedar al menos un comprobante' : 'Quitar'));
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
            const raiz = contenedor.closest('[data-pago]');
            if (raiz && raiz.dataset.cantidadFija === '1') return;
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
            const bloqueado = raiz.dataset.bloqueado === '1';
            const cantidad = parseInt(raiz.dataset.cantidadComprobantes || '0', 10);
            const codigosGuardados = (raiz.dataset.codigos || '')
                .split(' | ')
                .map((c) => c.trim())
                .filter(Boolean);

            const total = bloqueado ? Math.max(codigosGuardados.length, 1) : Math.max(cantidad, 1);
            for (let i = 0; i < total; i++) {
                agregarInputCodigo(contenedor, codigosGuardados[i] || '');
            }

            if (bloqueado) {
                contenedor.querySelectorAll('input').forEach((input) => { input.disabled = true; });
                return;
            }

            if (cantidad > 0) {
                raiz.dataset.cantidadFija = '1';
                raiz.querySelectorAll('[data-agregar-codigo]').forEach((boton) => {
                    boton.disabled = true;
                    boton.title = 'Ingresa exactamente un número por archivo subido';
                });
                actualizarBotonesQuitar(contenedor);
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

        const cantidad = parseInt(raiz.dataset.cantidadComprobantes || '0', 10);
        const codigos = [...contenedor.querySelectorAll('input')]
            .map((input) => input.value.trim())
            .filter(Boolean);

        if (estado) estado.textContent = '';
        boton.disabled = true;
        const textoOriginal = boton.textContent;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Guardando…';
        try {
            if (cantidad < 1) {
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
                if (estado) {
                    estado.textContent = 'Este pago no tiene comprobantes subidos para asociar.';
                    estado.className = 'text-xs font-bold text-red-600';
                }
                return;
            }
            if (codigos.length !== cantidad || codigos.some((codigo) => !/^[0-9]+$/.test(codigo))) {
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
                if (estado) {
                    estado.textContent = 'Debe ingresar un número de comprobante para cada uno de los ' + cantidad + ' archivos subidos.';
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
                    estado.textContent = 'Validado. Se guardará al aprobar.';
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

    let filaExcedente = null;
    const modalExcedente = document.getElementById('modalExcedente');
    const formExcedente = document.getElementById('formExcedente');
    document.getElementById('cancelarExcedente').onclick = () => modalExcedente.close();
    const menuAcciones = document.createElement('div');
    menuAcciones.id = 'menuOpcionesPago';
    menuAcciones.setAttribute('role', 'menu');
    menuAcciones.setAttribute('aria-label', 'Acciones del pago');
    menuAcciones.className = 'fixed z-[90] w-60 space-y-1 overflow-y-auto rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl';
    menuAcciones.hidden = true;
    document.body.appendChild(menuAcciones);
    let origenMenu = null;
    function cerrarMenuAcciones(devolverFoco = false) {
        const origen = origenMenu;
        menuAcciones.hidden = true;
        origen?.setAttribute('aria-expanded', 'false');
        origenMenu = null;
        if (devolverFoco) origen?.focus();
    }
    tablaPagos.addEventListener('click', evento => {
        const boton = evento.target.closest('[data-menu-acciones]');
        if (!boton) return;
        const yaAbierto = origenMenu === boton;
        cerrarMenuAcciones();
        if (yaAbierto) return;
        origenMenu = boton;
        boton.setAttribute('aria-expanded','true');
        boton.setAttribute('aria-controls',menuAcciones.id);
        menuAcciones.replaceChildren(boton.parentElement.querySelector('template').content.cloneNode(true));
        menuAcciones.hidden = false;
        menuAcciones.style.maxHeight = Math.max(100, window.innerHeight - 16) + 'px';
        const rect = boton.getBoundingClientRect();
        const altura = menuAcciones.offsetHeight;
        menuAcciones.style.left = Math.max(8, Math.min(rect.right - menuAcciones.offsetWidth, window.innerWidth - menuAcciones.offsetWidth - 8)) + 'px';
        menuAcciones.style.top = Math.max(8, rect.bottom + altura + 8 <= window.innerHeight ? rect.bottom + 4 : rect.top - altura - 4) + 'px';
        menuAcciones.querySelector('button')?.focus();
    });
    document.addEventListener('click', evento => {
        if (!menuAcciones.contains(evento.target) && !evento.target.closest('[data-menu-acciones]')) cerrarMenuAcciones();
    });
    window.addEventListener('resize', () => cerrarMenuAcciones());
    document.addEventListener('scroll', evento => {
        if (!menuAcciones.contains(evento.target)) cerrarMenuAcciones();
    }, true);
    menuAcciones.addEventListener('keydown', evento => {
        const botones = [...menuAcciones.querySelectorAll('button')];
        const i = botones.indexOf(document.activeElement);
        if (evento.key === 'Escape') { evento.preventDefault(); cerrarMenuAcciones(true); }
        else if (evento.key === 'Tab') cerrarMenuAcciones(true);
        else if (['ArrowDown','ArrowUp','Home','End'].includes(evento.key)) {
            evento.preventDefault();
            const siguiente = evento.key === 'Home' ? 0 : evento.key === 'End' ? botones.length - 1 : (i + (evento.key === 'ArrowDown' ? 1 : -1) + botones.length) % botones.length;
            botones[siguiente]?.focus();
        }
    });
    menuAcciones.addEventListener('click', evento => {
        const opcion = evento.target.closest('[data-ejecutar-accion]');
        if (!opcion || !origenMenu) return;
        const accion = opcion.dataset.ejecutarAccion;
        const fila = origenMenu.closest('tr');
        cerrarMenuAcciones(true);
        if (accion === 'detalle') fila.querySelector('dialog').showModal();
        else if (['aprobar','incompleto','desaprobar','espera','registrar-excedente'].includes(accion)) {
            const boton = fila.querySelector('[data-' + accion + ']');
            if (boton) boton.click();
        }
    });
    formExcedente.addEventListener('submit', async evento => {
        evento.preventDefault();
        if (!filaExcedente) return;
        const boton = formExcedente.querySelector('[type="submit"]');
        boton.disabled = true;
        const body = new FormData(formExcedente);
        body.append('accion', 'registrar_excedente');
        body.append('pago_id', filaExcedente.querySelector('[data-pago]').dataset.pago);
        body.append('csrf_token', document.getElementById('csrfSaldoAdmin').value);
        filaExcedente.querySelectorAll('[data-pago] input').forEach(input => body.append('codigos[]',input.value.trim()));
        try {
            const respuesta = await fetch(URL_CONTROLADOR, {method:'POST',body});
            const data = await respuesta.json();
            if (data.status === 'success') window.location.reload();
            else document.getElementById('errorExcedente').textContent = data.message;
        } catch (_) { document.getElementById('errorExcedente').textContent = 'No se pudo guardar. Intenta nuevamente.'; }
        finally { boton.disabled = false; }
    });

    document.addEventListener('click', async (evento) => {
        if (evento.target.closest('[data-cerrar-acciones]')) { evento.target.closest('dialog').close(); return; }
        const excedente = evento.target.closest('[data-registrar-excedente]');
        if (excedente) {
            filaExcedente = excedente.closest('tr');
            formExcedente.reset();
            document.getElementById('errorExcedente').textContent = '';
            modalExcedente.showModal();
            return;
        }
        if (evento.target.closest('[data-aprobar],[data-incompleto],[data-desaprobar],[data-espera]')) {
            evento.target.closest('dialog')?.close();
        }
        const resolverDiferencia = evento.target.closest('[data-resolver-diferencia]');
        if (resolverDiferencia) {
            const panel = resolverDiferencia.closest('[data-panel-diferencia]');
            const estado = panel?.querySelector('[data-estado-diferencia]');
            const saldo = panel?.querySelector('[data-saldo-diferencia]')?.value;
            const nota = panel?.querySelector('[data-nota-diferencia]')?.value.trim();
            resolverDiferencia.disabled = true;
            try {
                const data = await enviarAccion({ accion: 'resolver_diferencia', pago_id: resolverDiferencia.dataset.resolverDiferencia, saldo_favor: saldo, nota, csrf_token:document.getElementById('csrfSaldoAdmin').value });
                if (estado) {
                    estado.textContent = data.message;
                    estado.className = 'mt-1 text-xs font-bold ' + (data.status === 'success' ? 'text-green-700' : 'text-red-600');
                }
            } catch (_) {
                if (estado) { estado.textContent = 'No se pudo guardar la revisión.'; estado.className = 'mt-1 text-xs font-bold text-red-600'; }
            } finally { resolverDiferencia.disabled = false; }
            return;
        }

        const mensajeRapido = evento.target.closest('[data-mensaje-rapido]');
        if (mensajeRapido) {
            const campo = document.getElementById(mensajeRapido.dataset.mensajeRapido);
            if (!campo) return;
            campo.value = mensajeRapido.textContent.trim().slice(0, campo.maxLength);
            campo.dispatchEvent(new Event('input', { bubbles: true }));
            const error = campo === motivoRechazo ? errorDesaprobar : errorIncompleto;
            if (error) error.classList.add('hidden');
            campo.focus();
            return;
        }

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
            const cantidad = parseInt(raiz.dataset.cantidadComprobantes || '0', 10);
            const codigos = [...raiz.querySelectorAll('input')].map((input) => input.value.trim()).filter(Boolean);
            if (cantidad < 1 || codigos.length !== cantidad || codigos.some((codigo) => !/^[0-9]+$/.test(codigo))) {
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

        const botonIncompleto = evento.target.closest('[data-incompleto]');
        if (botonIncompleto) {
            pagoIncompleto = botonIncompleto.dataset.incompleto;
            if (motivoIncompleto) {
                motivoIncompleto.value = '';
                if (contadorMotivoIncompleto) contadorMotivoIncompleto.textContent = '0';
            }
            if (errorIncompleto) errorIncompleto.classList.add('hidden');
            abrirModal(modalIncompleto);
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
                const cantidad = parseInt(raiz.dataset.cantidadComprobantes || '0', 10);
                const codigos = [...raiz.querySelectorAll('input')].map((input) => input.value.trim()).filter(Boolean);
                const mostrarError = (mensaje) => {
                    if (errorAprobar) {
                        errorAprobar.textContent = mensaje;
                        errorAprobar.classList.remove('hidden');
                    }
                };
                if (cantidad < 1 || codigos.length !== cantidad || codigos.some((codigo) => !/^[0-9]+$/.test(codigo))) {
                    mostrarError('Debe ingresar un número de comprobante para cada uno de los ' + cantidad + ' archivos subidos.');
                    boton.innerHTML = textoOriginal;
                    boton.disabled = false;
                    return;
                }
                const formData = new FormData();
                formData.append('accion', 'aprobar');
                formData.append('pago_id', pagoAprobar);
                codigos.forEach((codigo) => formData.append('codigos[]', codigo));
                const response = await fetch(URL_CONTROLADOR, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    mostrarError(data.message || 'No se pudo aprobar el pago.');
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

    if (motivoIncompleto) {
        motivoIncompleto.addEventListener('input', () => {
            if (contadorMotivoIncompleto) contadorMotivoIncompleto.textContent = String(motivoIncompleto.value.length);
        });
    }

    const confirmarIncompleto = document.getElementById('confirmarIncompleto');
    if (confirmarIncompleto) {
        confirmarIncompleto.addEventListener('click', async () => {
            if (!pagoIncompleto) return;
            const motivo = motivoIncompleto ? motivoIncompleto.value.trim() : '';
            const mostrarError = (mensaje) => {
                if (errorIncompleto) {
                    errorIncompleto.textContent = mensaje;
                    errorIncompleto.classList.remove('hidden');
                }
            };
            if (!motivo) {
                mostrarError('El motivo del pago incompleto es obligatorio.');
                if (motivoIncompleto) motivoIncompleto.focus();
                return;
            }

            const boton = confirmarIncompleto;
            boton.disabled = true;
            const textoOriginal = boton.textContent;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Procesando…';
            try {
                const data = await enviarAccion({ accion: 'incompleto', pago_id: pagoIncompleto, motivo });
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    mostrarError(data.message || 'No se pudo marcar el pago como incompleto.');
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

    document.getElementById('cancelarIncompleto').addEventListener('click', () => {
        cerrarModal(modalIncompleto);
        pagoIncompleto = null;
    });
});
