(() => {
    const aviso = document.getElementById('avisoCumpleanos');
    const mensajes = document.getElementById('mensajesCumpleanos');
    let consultando = false;
    let temporizador;
    document.getElementById('cerrarCumpleanos').addEventListener('click', () => {
        clearTimeout(temporizador);
        aviso.hidden = true;
    });
    async function actualizar() {
        if (document.hidden || consultando) return;
        consultando = true;
        try {
            const cuerpo = new FormData();
            cuerpo.append('csrf_token', aviso.dataset.token);
            const respuesta = await fetch('/Controllers/CumpleanosController.php', { method: 'POST', body: cuerpo, cache: 'no-store' });
            if (!respuesta.ok) return;
            const datos = await respuesta.json();
            if (!Array.isArray(datos.cumpleanos) || !datos.cumpleanos.length) return;
            const propio = datos.cumpleanos.find(persona => persona.propio);
            document.getElementById('tituloCumpleanos').textContent = propio ? '🎂 ¡Feliz cumpleaños!' : '🎂 Cumpleaños de hoy';
            mensajes.replaceChildren();
            for (const persona of (propio ? [propio] : datos.cumpleanos)) {
                const mensaje = document.createElement('p');
                mensaje.style.marginTop = '10px';
                mensaje.textContent = persona.propio
                    ? `¡Feliz cumpleaños, ${persona.nombre}! Te deseamos un día lleno de alegría. 🎉`
                    : `Hoy cumple años ${persona.nombre}. ¡Deseémosle un feliz cumpleaños!`;
                mensajes.appendChild(mensaje);
            }
            aviso.hidden = false;
            clearTimeout(temporizador);
            temporizador = setTimeout(() => { aviso.hidden = true; }, 5000);
        } catch (_) { /* Reintentar cuando regrese la conexión. */ }
        finally { consultando = false; }
    }
    actualizar();
    const intervalo = setInterval(actualizar, 60000);
    document.addEventListener('visibilitychange', actualizar);
    window.addEventListener('beforeunload', () => clearInterval(intervalo));
})();
