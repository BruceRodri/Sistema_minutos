// Assets/js/buses.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/BusController.php';

    // ---------- Búsqueda automática en los filtros (debounce 400ms) ----------
    const formularioFiltros = document.querySelector('form[action="buses.php"]');
    if (formularioFiltros) {
        let temporizadorFiltros = null;
        const lanzarBusqueda = (evento) => {
            if (!evento.target.closest('form[action="buses.php"]')) return;
            if (temporizadorFiltros) clearTimeout(temporizadorFiltros);
            temporizadorFiltros = setTimeout(() => {
                formularioFiltros.requestSubmit();
            }, 400);
        };
        document.addEventListener('input', lanzarBusqueda);
        document.addEventListener('change', lanzarBusqueda);
    }

    function mostrarAlerta(elemento, tipo, mensaje) {
        if (!elemento) return;
        elemento.textContent = mensaje;
        elemento.className = 'mb-4 p-3 rounded text-sm text-center ' +
            (tipo === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200');
        elemento.classList.remove('hidden');
    }

    // ---------- Crear Bus ----------
    const formCrearBus = document.getElementById('formCrearBus');
    const modalCrearBus = document.getElementById('modalCrearBus');
    const btnNuevoBus = document.getElementById('btnNuevoBus');
    if (modalCrearBus && btnNuevoBus) {
        const cerrarModal = () => {
            if (document.getElementById('btnSubmit').disabled) return;
            modalCrearBus.classList.add('hidden');
            modalCrearBus.classList.remove('flex');
            btnNuevoBus.focus();
        };
        btnNuevoBus.addEventListener('click', () => {
            formCrearBus.reset();
            document.getElementById('alerta').classList.add('hidden');
            modalCrearBus.classList.remove('hidden');
            modalCrearBus.classList.add('flex');
            document.getElementById('disco').focus();
        });
        document.getElementById('btnCerrarCrearBus').addEventListener('click', cerrarModal);
        document.getElementById('btnCancelarCrearBus').addEventListener('click', cerrarModal);
        modalCrearBus.addEventListener('click', (event) => {
            if (event.target === modalCrearBus) cerrarModal();
        });
        modalCrearBus.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') cerrarModal();
            if (event.key !== 'Tab') return;
            const elementos = [...modalCrearBus.querySelectorAll('button, input')].filter((el) => !el.disabled);
            const primero = elementos[0];
            const ultimo = elementos[elementos.length - 1];
            if (event.shiftKey && document.activeElement === primero) {
                event.preventDefault();
                ultimo.focus();
            } else if (!event.shiftKey && document.activeElement === ultimo) {
                event.preventDefault();
                primero.focus();
            }
        });
    }
    if (formCrearBus) {
        const alerta = document.getElementById('alerta');
        const btnSubmit = document.getElementById('btnSubmit');

        formCrearBus.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (btnSubmit.disabled) return;
            let creado = false;
            alerta.classList.add('hidden');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';

            const formData = new FormData(formCrearBus);
            formData.append('accion', 'crear');

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();

                if (data.status === 'success') {
                    creado = true;
                    mostrarAlerta(alerta, 'success', data.message);
                    formCrearBus.reset();
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    mostrarAlerta(alerta, 'error', data.message);
                }
            } catch (error) {
                console.error(error);
                mostrarAlerta(alerta, 'error', 'Error de conexión con el servidor.');
            } finally {
                btnSubmit.disabled = creado;
                btnSubmit.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Bus';
            }
        });
    }

    // ---------- Modal Editar ----------
    const modalEditar = document.getElementById('modalEditar');
    const formEditarBus = document.getElementById('formEditarBus');

    document.querySelectorAll('.btnEditarBus').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.getElementById('editId').value = btn.dataset.id;
            document.getElementById('editDisco').value = btn.dataset.disco;
            document.getElementById('editPlaca').value = btn.dataset.placa;
            modalEditar.classList.remove('hidden');
        });
    });

    const btnCancelarEditar = document.getElementById('btnCancelarEditar');
    if (btnCancelarEditar) {
        btnCancelarEditar.addEventListener('click', () => modalEditar.classList.add('hidden'));
    }

    if (formEditarBus) {
        formEditarBus.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(formEditarBus);
            formData.append('accion', 'editar');

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error de conexión con el servidor.');
            }
        });
    }

    // ---------- Eliminar Bus ----------
    document.querySelectorAll('.btnEliminarBus').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm(`¿Está seguro de eliminar el bus con disco ${btn.dataset.disco}?`)) return;

            const formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id', btn.dataset.id);

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error de conexión con el servidor.');
            }
        });
    });

    // ---------- Habilitar / Deshabilitar ----------
    document.querySelectorAll('.btnToggleBus').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const estado = btn.dataset.estado;
            const nuevoEstado = (estado == 1) ? 0 : 1;
            const accionTexto = (nuevoEstado == 1) ? 'habilitar' : 'deshabilitar';

            if (!confirm(`¿Está seguro de ${accionTexto} el bus con disco ${btn.dataset.disco}?`)) return;

            const formData = new FormData();
            formData.append('accion', 'cambiar_estado');
            formData.append('id', btn.dataset.busId);
            formData.append('estado', nuevoEstado);

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error de conexión con el servidor.');
            }
        });
    });

    // ---------- Ver QR ----------
    const qrModal = document.getElementById('qrModal');
    if (qrModal) {
        let discoQrActual = '';
        let placaQrActual = '';
        const btnDescargarQR = document.getElementById('btnDescargarQR');

        const cargarImagenQr = (imagen) => new Promise((resolve, reject) => {
            if (imagen.complete && imagen.naturalWidth > 0) {
                resolve(imagen);
                return;
            }
            imagen.addEventListener('load', () => resolve(imagen), { once: true });
            imagen.addEventListener('error', reject, { once: true });
        });

        const dibujarTextoEspaciado = (contexto, texto, centroX, y, espacio) => {
            const caracteres = Array.from(texto);
            const ancho = caracteres.reduce((total, caracter) => total + contexto.measureText(caracter).width, 0)
                + Math.max(0, caracteres.length - 1) * espacio;
            let x = centroX - ancho / 2;

            caracteres.forEach((caracter) => {
                contexto.fillText(caracter, x, y);
                x += contexto.measureText(caracter).width + espacio;
            });
        };

        const crearEtiquetaQr = async () => {
            const contenedor = document.getElementById('qrCodigo');
            const qrCanvas = contenedor?.querySelector('canvas');
            const qrImagen = contenedor?.querySelector('img');
            const fuenteQr = qrCanvas || (qrImagen ? await cargarImagenQr(qrImagen) : null);

            if (!fuenteQr || !discoQrActual) {
                throw new Error('No hay un código QR disponible.');
            }

            // La impresión múltiple usa etiquetas de 60 mm de ancho: 3 columnas
            // dentro de 190 mm, con dos separaciones de 5 mm.
            const escala = 10;
            const mm = (valor) => valor * escala;
            const etiqueta = document.createElement('canvas');
            etiqueta.width = mm(60);
            etiqueta.height = mm(74);
            const contexto = etiqueta.getContext('2d');

            contexto.fillStyle = '#ffffff';
            contexto.fillRect(0, 0, etiqueta.width, etiqueta.height);
            contexto.fillStyle = '#111111';
            contexto.textBaseline = 'alphabetic';

            contexto.font = '32px Arial, sans-serif';
            dibujarTextoEspaciado(contexto, 'EJECUTTRANS', etiqueta.width / 2, mm(8.2), 5);

            // Equivale al bloque de 44 mm con 3 mm de relleno de imprimir_qrs.php.
            const qrExterior = mm(44);
            const rellenoQr = mm(3);
            const qrX = (etiqueta.width - qrExterior) / 2 + rellenoQr;
            const qrY = mm(11.2) + rellenoQr;
            const ladoQr = qrExterior - (rellenoQr * 2);
            contexto.imageSmoothingEnabled = false;
            contexto.drawImage(fuenteQr, qrX, qrY, ladoQr, ladoQr);

            contexto.textAlign = 'center';
            contexto.font = 'bold 53px Arial, sans-serif';
            contexto.fillText(`Disco ${discoQrActual}`, etiqueta.width / 2, mm(63.5));

            contexto.font = '34px Arial, sans-serif';
            contexto.fillText(`Placa: ${placaQrActual || 'Sin placa'}`, etiqueta.width / 2, mm(70.2));

            return etiqueta;
        };

        document.querySelectorAll('.btnVerQR').forEach((btn) => {
            btn.addEventListener('click', () => {
                const disco = btn.dataset.disco;
                const placa = btn.dataset.placa || 'Sin placa';
                const contenedor = document.getElementById('qrCodigo');
                const etiqueta = document.getElementById('qrDiscoLabel');
                const etiquetaPlaca = document.getElementById('qrPlacaLabel');

                contenedor.innerHTML = '';
                etiqueta.textContent = 'Disco ' + disco;
                if (etiquetaPlaca) etiquetaPlaca.textContent = 'Placa: ' + placa;
                discoQrActual = disco;
                placaQrActual = placa;
                if (btnDescargarQR) btnDescargarQR.disabled = false;
                new QRCode(contenedor, {
                    text: disco,
                    width: 200,
                    height: 200,
                    correctLevel: QRCode.CorrectLevel.H
                });

                qrModal.classList.remove('hidden');
            });
        });

        btnDescargarQR?.addEventListener('click', async () => {
            btnDescargarQR.disabled = true;
            try {
                const etiqueta = await crearEtiquetaQr();
                const discoSeguro = /^\d+$/.test(discoQrActual)
                    ? discoQrActual.padStart(3, '0')
                    : discoQrActual.replace(/[^a-z0-9_-]+/gi, '_');
                const enlace = document.createElement('a');
                enlace.href = etiqueta.toDataURL('image/png');
                enlace.download = `QR_Disco_${discoSeguro}.png`;
                document.body.appendChild(enlace);
                enlace.click();
                enlace.remove();
            } catch (error) {
                console.error('No se pudo generar la etiqueta QR:', error);
                window.alert('No se pudo preparar la etiqueta QR para descargar.');
            } finally {
                btnDescargarQR.disabled = false;
            }
        });

        const btnCerrarQR = document.getElementById('btnCerrarQR');
        if (btnCerrarQR) {
            btnCerrarQR.addEventListener('click', () => qrModal.classList.add('hidden'));
        }
    }
});
