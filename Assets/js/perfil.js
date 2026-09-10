(() => {
    const form = document.getElementById('formClavePerfil');
    const aviso = document.getElementById('avisoClavePerfil');
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
