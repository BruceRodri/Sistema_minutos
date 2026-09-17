(() => {
    const form = document.getElementById('formClavePerfil');
    const aviso = document.getElementById('avisoClavePerfil');
    document.querySelectorAll('[data-toggle-password]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const input = document.getElementById(boton.dataset.togglePassword);
            if (!input) return;
            const mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';
            boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
            boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
            const icono = boton.querySelector('i');
            if (icono) {
                icono.classList.toggle('fa-eye', !mostrar);
                icono.classList.toggle('fa-eye-slash', mostrar);
            }
        });
    });

    if (!form || !aviso) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (form.elements.nueva.value !== form.elements.confirmacion.value) {
            aviso.textContent = 'Las contraseñas nuevas no coinciden.';
            return;
        }
        const boton = form.querySelector('button');
        boton.disabled = true;
        aviso.textContent = 'Guardando…';
        try {
            const respuesta = await fetch('../../Controllers/PerfilController.php', { method: 'POST', body: new FormData(form) });
            const datos = await respuesta.json();
            aviso.textContent = datos.message;
            if (datos.status === 'success') form.reset();
        } catch (_) {
            aviso.textContent = 'Error de conexión. Intenta nuevamente.';
        } finally { boton.disabled = false; }
    });
})();
