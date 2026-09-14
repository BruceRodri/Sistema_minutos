(() => {
    const aviso = document.getElementById('avisoCumpleanos');
    const mensajes = document.getElementById('mensajesCumpleanos');
    const cerrar = document.getElementById('cerrarCumpleanos');
    if (!aviso || !mensajes || !cerrar) return;
    const esPopup = aviso.tagName === 'DIALOG';
    let capaConfetiCumpleanos = null;
    let temporizadorConfetiCumpleanos = null;
    function detenerConfetiCumpleanos() {
        clearTimeout(temporizadorConfetiCumpleanos);
        capaConfetiCumpleanos?.remove();
        capaConfetiCumpleanos = null;
    }
    function celebrarCumpleanos(modal) {
        detenerConfetiCumpleanos();
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !Element.prototype.animate) return;
        const capa = document.createElement('div');
        capa.setAttribute('aria-hidden', 'true');
        capa.style.cssText = 'position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:10';
        const colores = ['#22c55e', '#2563eb', '#fbbf24', '#f472b6', '#a78bfa', '#38bdf8'];
        const ancho = modal.clientWidth;
        const alto = modal.clientHeight;
        const radio = Math.hypot(ancho, alto) * 0.48;
        for (let i = 0; i < 96; i++) {
            const pieza = document.createElement('span');
            // Distribuir la explosión en un círculo completo desde el centro de la ventana.
            const angulo = (i / 96) * Math.PI * 2 + (Math.random() - 0.5) * 0.08;
            const distancia = radio * (0.45 + Math.random() * 0.55);
            const giro = Math.random() * 360;
            const rotacion = (Math.random() - 0.5) * 1080;
            pieza.style.cssText = `position:absolute;top:50%;left:50%;margin:-6px 0 0 -4px;width:8px;height:13px;background:${colores[i % colores.length]};border-radius:${i % 3 === 0 ? '50%' : '2px'}`;
            capa.appendChild(pieza);
            const fotogramas = Array.from({ length: 21 }, (_, paso) => {
                const t = paso / 20;
                const expansion = 1 - Math.pow(1 - t, 3);
                const x = Math.cos(angulo) * distancia * expansion;
                const y = Math.sin(angulo) * distancia * expansion + alto * 0.3 * t * t;
                return {
                    offset: t,
                    transform: `translate(${x}px, ${y}px) rotate(${giro + rotacion * t}deg)`,
                    opacity: t < 0.65 ? 1 : (1 - t) / 0.35
                };
            });
            pieza.animate(fotogramas, {
                duration: 1800 + Math.random() * 700,
                easing: 'linear',
                fill: 'both'
            });
        }
        modal.appendChild(capa);
        capaConfetiCumpleanos = capa;
        temporizadorConfetiCumpleanos = setTimeout(detenerConfetiCumpleanos, 2600);
    }
    window.addEventListener('pagehide', detenerConfetiCumpleanos);
    function ocultarAviso() {
        detenerConfetiCumpleanos();
        if (esPopup) { if (aviso.open) aviso.close(); }
        else aviso.hidden = true;
    }
    function mostrarAviso() {
        if (esPopup) {
            if (!aviso.open) {
                aviso.showModal();
                celebrarCumpleanos(aviso);
            }
        }
        else aviso.hidden = false;
    }
    let consultando = false;
    let confirmando = false;
    let fechaMostrada = null;
    let revision = 0;
    let fechaPendiente = null;
    async function solicitar(accion, fecha) {
        const cuerpo = new FormData();
        cuerpo.append('csrf_token', aviso.dataset.token);
        cuerpo.append('accion', accion);
        if (fecha) cuerpo.append('fecha', fecha);
        const respuesta = await fetch(aviso.dataset.url, { method: 'POST', body: cuerpo, cache: 'no-store' });
        if (!respuesta.ok) throw new Error('No se pudo actualizar el aviso de cumpleaños.');
        return respuesta.json();
    }
    async function confirmar() {
        if (!fechaPendiente || confirmando) return;
        confirmando = true;
        const fecha = fechaPendiente;
        try {
            await solicitar('confirmar', fecha);
            if (fechaPendiente === fecha) fechaPendiente = null;
        } catch (_) { /* Reintentar sin perder el aviso al regresar la conexión. */ }
        finally { confirmando = false; }
    }
    function cerrarAviso() {
        if (!fechaMostrada) return;
        revision++;
        fechaPendiente = fechaMostrada;
        ocultarAviso();
        confirmar();
    }
    cerrar.addEventListener('click', cerrarAviso);
    document.getElementById('aceptarCumpleanos')?.addEventListener('click', cerrarAviso);
    if (esPopup) {
        aviso.addEventListener('cancel', evento => {
            evento.preventDefault();
            cerrarAviso();
        });
        let inicioFuera = false;
        const fuera = evento => {
            const rect = aviso.getBoundingClientRect();
            return evento.target === aviso && (evento.clientX < rect.left || evento.clientX > rect.right ||
                evento.clientY < rect.top || evento.clientY > rect.bottom);
        };
        aviso.addEventListener('pointerdown', evento => { inicioFuera = fuera(evento); });
        aviso.addEventListener('click', evento => {
            if (inicioFuera && fuera(evento)) cerrarAviso();
            inicioFuera = false;
        });
    }
    async function actualizar() {
        if (document.hidden || consultando || confirmando) return;
        consultando = true;
        const revisionConsulta = revision;
        try {
            const datos = await solicitar('consultar');
            if (document.hidden || revisionConsulta !== revision) return;
            if (fechaPendiente && fechaPendiente !== datos.fecha) fechaPendiente = null;
            if (fechaPendiente === datos.fecha) { await confirmar(); return; }
            if (!Array.isArray(datos.cumpleanos)) return;
            if (!datos.cumpleanos.length) {
                ocultarAviso();
                fechaMostrada = null;
                return;
            }
            const propio = datos.cumpleanos.find(persona => persona.propio);
            document.getElementById('tituloCumpleanos').textContent = propio ? '¡Feliz cumpleaños!' : 'Cumpleaños de hoy';
            mensajes.replaceChildren();
            // El servidor devuelve únicamente las personas que este rol puede ver.
            for (const persona of datos.cumpleanos) {
                const mensaje = document.createElement('p');
                mensaje.style.marginTop = '10px';
                mensaje.textContent = persona.propio
                    ? `¡Feliz cumpleaños, ${persona.nombre}! Te deseamos un día lleno de alegría. 🎉`
                    : `Hoy cumple años ${persona.nombre}. ¡Deseémosle un feliz cumpleaños!`;
                mensajes.appendChild(mensaje);
            }
            fechaMostrada = datos.fecha;
            mostrarAviso();
        } catch (_) { /* Reintentar cuando regrese la conexión. */ }
        finally { consultando = false; }
    }
    actualizar();
    const intervalo = setInterval(actualizar, 60000);
    document.addEventListener('visibilitychange', actualizar);
    window.addEventListener('online', actualizar);
    window.addEventListener('beforeunload', () => clearInterval(intervalo));
})();
