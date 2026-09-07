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

    async function iniciarScanner() {
        try {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode('qr-reader');
            } else if (html5QrCode.isScanning) {
                await html5QrCode.stop();
            }

            await html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                async (texto) => {
                    await detenerScanner();
                    await enviarTurno(texto.trim());
                },
                () => {}
            );
        } catch (err) {
            console.error('Error al iniciar la cámara:', err);
            overlay.classList.add('hidden');
            mostrarModal('error', 'Error de cámara', 'No se pudo iniciar la cámara. Verifique que el sitio use HTTPS y que haya concedido permiso de cámara.', '');
        }
    }

    async function detenerScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            try {
                await html5QrCode.stop();
                await html5QrCode.clear();
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
                mostrarModal('success', 'Turno Abierto', data.message, 'Disco ' + data.disco + ' - Hora ' + data.hora);
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
        overlay.classList.remove('hidden');
        await iniciarScanner();
    });

    btnCerrarScanner.addEventListener('click', async () => {
        await detenerScanner();
        overlay.classList.add('hidden');
    });

    btnCerrarModal.addEventListener('click', () => {
        modal.classList.add('hidden');
    });
});