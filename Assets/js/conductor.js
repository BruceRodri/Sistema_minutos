// Assets/js/conductor.js

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('scannerOverlay');
    const modal = document.getElementById('modalConfirmacion');
    const modalIcono = document.getElementById('modalIcono');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalMensaje = document.getElementById('modalMensaje');
    const modalInfo = document.getElementById('modalInfo');
    const btnAbrirTurno = document.getElementById('btnAbrirTurno');
    const btnCerrarScanner = document.getElementById('btnCerrarScanner');
    const btnCerrarModal = document.getElementById('btnCerrarModal');

    let html5QrCode = null;
    let procesandoLectura = false;
    let iniciandoScanner = false;

    const configuracionScanner = {
        fps: 20,
        // Analizar el cuadro completo permite leer también códigos fuera del centro.
        disableFlip: false
    };

    async function iniciarScanner() {
        if (iniciandoScanner) return;
        iniciandoScanner = true;
        btnAbrirTurno.disabled = true;

        try {
            procesandoLectura = false;
            // Esperar a que el panel sea visible antes de medir el video.
            await new Promise((resolve) => requestAnimationFrame(resolve));
            if (overlay.classList.contains('hidden')) return;

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode('qr-reader', { verbose: false });
            } else if (html5QrCode.isScanning) {
                await html5QrCode.stop();
            }

            await html5QrCode.start(
                { facingMode: 'environment' },
                configuracionScanner,
                async (texto) => {
                    if (procesandoLectura) return;
                    procesandoLectura = true;

                    overlay.classList.add('hidden');
                    const detencionScanner = detenerScanner();
                    await enviarTurno(texto.trim());
                    await detencionScanner;
                },
                () => {}
            );
            if (overlay.classList.contains('hidden')) await detenerScanner();
        } catch (err) {
            console.error('Error al iniciar la cámara:', err);
            overlay.classList.add('hidden');
            mostrarModal('error', 'Error de cámara', 'No se pudo iniciar la cámara. Verifique que el sitio use HTTPS y que haya concedido permiso de cámara.', '');
        } finally {
            iniciandoScanner = false;
            btnAbrirTurno.disabled = false;
        }
    }

    async function detenerScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            try {
                await html5QrCode.stop();
            } catch (e) {
                console.error(e);
            }
        }
    }

    async function enviarTurno(disco) {
        const formData = new FormData();
        formData.append('accion', 'abrir_turno');
        formData.append('disco', disco);

        try {
            const response = await fetch('../../Controllers/TurnoController.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                const partesFecha = data.fecha.split('-');
                const fechaFormateada = partesFecha.length === 3
                    ? `${partesFecha[2]}/${partesFecha[1]}/${partesFecha[0]}`
                    : data.fecha;

                mostrarModal(
                    'success',
                    'Turno abierto',
                    'Que tengas un excelente día.',
                    `Disco ${data.disco} · ${fechaFormateada} · ${data.hora}`
                );
            } else {
                mostrarModal('error', 'No se pudo abrir', data.message, '');
            }
        } catch (error) {
            console.error('Error en la petición AJAX:', error);
            mostrarModal('error', 'Error', 'Error de conexión con el servidor. Intente nuevamente.', '');
        }
    }

    function mostrarModal(tipo, titulo, mensaje, info) {
        if (tipo === 'success') {
            modalIcono.className = 'mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-green-600';
            modalIcono.innerHTML = '<i class="fas fa-check text-white text-4xl"></i>';
        } else {
            modalIcono.className = 'mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-red-600';
            modalIcono.innerHTML = '<i class="fas fa-times text-white text-4xl"></i>';
        }
        modalTitulo.textContent = titulo;
        modalMensaje.textContent = mensaje;
        modalInfo.textContent = info;
        modal.classList.remove('hidden');
    }

    btnAbrirTurno.addEventListener('click', async () => {
        if (iniciandoScanner) return;
        overlay.classList.remove('hidden');
        await iniciarScanner();
    });

    btnCerrarScanner.addEventListener('click', async () => {
        overlay.classList.add('hidden');
        await detenerScanner();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            overlay.classList.add('hidden');
            detenerScanner();
        }
    });

    btnCerrarModal.addEventListener('click', () => {
        modal.classList.add('hidden');
    });
});
