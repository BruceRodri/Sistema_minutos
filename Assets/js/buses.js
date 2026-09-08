// Assets/js/buses.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/BusController.php';

    function mostrarAlerta(elemento, tipo, mensaje) {
        if (!elemento) return;
        elemento.textContent = mensaje;
        elemento.className = 'mb-4 p-3 rounded text-sm text-center ' +
            (tipo === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200');
        elemento.classList.remove('hidden');
    }

    // ---------- Crear Bus ----------
    const formCrearBus = document.getElementById('formCrearBus');
    if (formCrearBus) {
        const alerta = document.getElementById('alerta');
        const btnSubmit = document.getElementById('btnSubmit');

        formCrearBus.addEventListener('submit', async (e) => {
            e.preventDefault();
            alerta.classList.add('hidden');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';

            const formData = new FormData(formCrearBus);
            formData.append('accion', 'crear');

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();

                if (data.status === 'success') {
                    mostrarAlerta(alerta, 'success', data.message);
                    formCrearBus.reset();
                    setTimeout(() => { window.location.href = 'buses.php'; }, 800);
                } else {
                    mostrarAlerta(alerta, 'error', data.message);
                }
            } catch (error) {
                console.error(error);
                mostrarAlerta(alerta, 'error', 'Error de conexión con el servidor.');
            } finally {
                btnSubmit.disabled = false;
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
        let qrGenerado = null;

        document.querySelectorAll('.btnVerQR').forEach((btn) => {
            btn.addEventListener('click', () => {
                const disco = btn.dataset.disco;
                const contenedor = document.getElementById('qrCodigo');
                const etiqueta = document.getElementById('qrDiscoLabel');

                contenedor.innerHTML = '';
                etiqueta.textContent = 'Disco ' + disco;
                qrGenerado = new QRCode(contenedor, {
                    text: disco,
                    width: 200,
                    height: 200,
                    correctLevel: QRCode.CorrectLevel.H
                });

                qrModal.classList.remove('hidden');
            });
        });

        const btnCerrarQR = document.getElementById('btnCerrarQR');
        if (btnCerrarQR) {
            btnCerrarQR.addEventListener('click', () => qrModal.classList.add('hidden'));
        }
    }
});