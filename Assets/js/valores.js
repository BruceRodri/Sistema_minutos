// Assets/js/valores.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formSubirValores');
    const inputArchivo = document.getElementById('inputArchivo');
    const nombreArchivo = document.getElementById('nombreArchivo');
    const btnSubir = document.getElementById('btnSubir');
    const alerta = document.getElementById('alerta');

    function mostrarAlerta(tipo, mensaje) {
        alerta.textContent = mensaje;
        alerta.className = 'mb-6 p-4 rounded-xl text-sm font-bold text-center ' +
            (tipo === 'success'
                ? 'bg-green-100 text-green-800 border border-green-200'
                : 'bg-red-100 text-red-800 border border-red-200');
        alerta.classList.remove('hidden');
    }

    if (inputArchivo) {
        inputArchivo.addEventListener('change', () => {
            nombreArchivo.textContent = inputArchivo.files.length
                ? inputArchivo.files[0].name
                : 'Selecciona un archivo .xlsx';
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        alerta.classList.add('hidden');

        if (!inputArchivo.files.length) {
            mostrarAlerta('error', 'Selecciona un archivo .xlsx primero.');
            return;
        }

        btnSubir.disabled = true;
        btnSubir.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Subiendo...';

        const formData = new FormData(form);
        formData.append('accion', 'subir');

        try {
            const response = await fetch('../../Controllers/ValoresController.php', { method: 'POST', body: formData });
            const data = await response.json();
            mostrarAlerta(data.status === 'success' ? 'success' : 'error', data.message);
            if (data.status === 'success') {
                setTimeout(() => window.location.reload(), 1200);
            }
        } catch (error) {
            console.error(error);
            mostrarAlerta('error', 'Error de conexión con el servidor.');
        } finally {
            btnSubir.disabled = false;
            btnSubir.innerHTML = '<i class="fas fa-upload mr-2"></i> Subir archivo';
        }
    });
});