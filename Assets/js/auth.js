// Assets/js/auth.js

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const alertaError = document.getElementById('alertaError');
    const btnSubmit = document.getElementById('btnSubmit');
    const inputCedula = document.getElementById('cedula');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Evita que la página se recargue

        if (!window.CedulaEcuador.validarCampo(inputCedula, true) || !loginForm.reportValidity()) {
            return;
        }

        // Ocultar alerta y cambiar texto del botón
        alertaError.classList.add('hidden');
        btnSubmit.textContent = 'Validando...';
        btnSubmit.disabled = true;

        // Recolectar datos del formulario
        const formData = new FormData(loginForm);

        try {
            // Enviar petición POST al controlador
            const response = await fetch('Controllers/AuthController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Redirigir según el rol
                window.location.href = data.redirect;
            } else if (data.status === 'require_change') {
                // Redirigir a pantalla de cambio de clave
                window.location.href = data.redirect;
            } else {
                // Mostrar error (ej: credenciales incorrectas)
                alertaError.textContent = data.message;
                alertaError.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error en la petición AJAX:', error);
            alertaError.textContent = 'Error de conexión con el servidor.';
            alertaError.classList.remove('hidden');
        } finally {
            // Restaurar el botón
            btnSubmit.textContent = 'Ingresar';
            btnSubmit.disabled = false;
        }
    });
});
