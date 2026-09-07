// Assets/js/turnos.js

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btnToggleBus').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const busId = btn.dataset.busId;
            const estado = btn.dataset.estado;
            const disco = btn.dataset.disco;
            const nuevoEstado = (estado == 1) ? 0 : 1;
            const accionTexto = (nuevoEstado == 1) ? 'habilitar' : 'deshabilitar';

            if (!confirm(`¿Está seguro de ${accionTexto} el bus con disco ${disco}?`)) return;

            const formData = new FormData();
            formData.append('accion', 'cambiar_estado_bus');
            formData.append('bus_id', busId);
            formData.append('estado', nuevoEstado);

            try {
                const response = await fetch('../Controllers/TurnoController.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.message);
                window.location.reload();
            } catch (error) {
                console.error('Error en la petición AJAX:', error);
                alert('Error de conexión con el servidor.');
            }
        });
    });
});