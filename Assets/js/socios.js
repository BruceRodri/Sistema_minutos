// Assets/js/socios.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/SocioController.php';

    // ---------- Deshabilitar / Habilitar disco ----------
    document.querySelectorAll('.btnToggleDisco').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const estado = btn.dataset.estado;
            const nuevoEstado = (estado == 1) ? 0 : 1;
            const accionTexto = (nuevoEstado == 1) ? 'habilitar' : 'deshabilitar';

            if (!confirm(`¿Está seguro de ${accionTexto} el disco ${btn.dataset.disco}?`)) return;

            const formData = new FormData();
            formData.append('accion', 'cambiar_estado_disco');
            formData.append('bus_id', btn.dataset.busId);
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

    // ---------- Modal Cambiar Socio ----------
    const modalCambiarSocio = document.getElementById('modalCambiarSocio');
    const formCambiarSocio = document.getElementById('formCambiarSocio');

    if (modalCambiarSocio && formCambiarSocio) {
        document.querySelectorAll('.btnCambiarSocio').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('cambioBusId').value = btn.dataset.busId;
                document.getElementById('modalDiscoLabel').textContent = 'Disco ' + btn.dataset.disco;
                document.getElementById('cambioUsuario').value = '';
                modalCambiarSocio.classList.remove('hidden');
            });
        });

        document.getElementById('btnCancelarCambio').addEventListener('click', () => {
            modalCambiarSocio.classList.add('hidden');
        });

        formCambiarSocio.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(formCambiarSocio);
            formData.append('accion', 'cambiar_socio');

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
});