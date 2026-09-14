(() => {
    const modal = document.getElementById('modalFrasesPago');
    const form = document.getElementById('formFrasePago');
    const lista = document.getElementById('listaFrasesPago');
    const aviso = document.getElementById('avisoFrasesPago');
    const titulo = document.getElementById('tituloFormFrase');
    const url = '../../Controllers/FrasePagoController.php';
    let guardando = false;
    function nueva() {
        form.reset();
        form.elements.id.value = '0';
        form.elements.estado.disabled = false;
        titulo.textContent = 'Agregar frase';
    }
    function mostrar(frases) {
        lista.replaceChildren();
        for (const [estado, etiqueta] of [['incompleto', 'Incompleto'], ['anulado', 'Anulado']]) {
            const grupo = document.createElement('section');
            const encabezado = document.createElement('h3');
            grupo.className = estado === 'incompleto'
                ? 'rounded-xl border border-amber-200 bg-amber-50/50 p-3'
                : 'rounded-xl border border-red-200 bg-red-50/50 p-3';
            encabezado.className = estado === 'incompleto'
                ? 'flex items-center gap-2 text-sm font-bold mb-3 text-amber-700'
                : 'flex items-center gap-2 text-sm font-bold mb-3 text-red-700';
            const icono = document.createElement('i');
            icono.className = estado === 'incompleto' ? 'fas fa-triangle-exclamation' : 'fas fa-circle-xmark';
            icono.setAttribute('aria-hidden', 'true');
            encabezado.append(icono, document.createTextNode(etiqueta));
            grupo.append(encabezado);
            const frasesEstado = frases.filter(f => f.estado === estado);
            if (!frasesEstado.length) {
                const vacio = document.createElement('p');
                vacio.className = 'text-sm text-gray-500';
                vacio.textContent = 'No hay frases para este estado. Puedes agregar una nueva.';
                grupo.append(vacio);
            }
            for (const frase of frasesEstado) {
                const fila = document.createElement('div');
                fila.className = 'mb-2 last:mb-0 flex flex-col items-start gap-2 rounded-xl border border-white bg-white p-3 shadow-sm';
                const texto = document.createElement('p');
                texto.className = 'text-sm leading-relaxed text-gray-600 whitespace-pre-wrap break-words min-w-0 w-full';
                texto.textContent = frase.texto;
                const editar = document.createElement('button');
                editar.type = 'button';
                editar.className = 'self-end rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100 focus:ring-2 focus:ring-blue-300';
                editar.textContent = 'Editar';
                editar.addEventListener('click', () => {
                    if (guardando) return;
                    form.elements.id.value = frase.id;
                    form.elements.estado.value = frase.estado;
                    form.elements.estado.disabled = true;
                    form.elements.texto.value = frase.texto;
                    titulo.textContent = 'Editar frase';
                    form.elements.texto.focus();
                });
                const eliminar = document.createElement('button');
                eliminar.type = 'button';
                eliminar.className = 'rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 transition-colors hover:bg-red-100 focus:ring-2 focus:ring-red-300 disabled:opacity-50';
                eliminar.textContent = 'Eliminar';
                eliminar.addEventListener('click', async () => {
                    if (guardando || !window.confirm(`¿Eliminar esta frase de ${etiqueta}?\n\n${frase.texto}`)) return;
                    guardando = true;
                    eliminar.disabled = true;
                    const guardar = form.querySelector('[type="submit"]');
                    guardar.disabled = true;
                    aviso.textContent = 'Eliminando…';
                    const cuerpo = new FormData();
                    cuerpo.set('accion', 'eliminar');
                    cuerpo.set('id', frase.id);
                    cuerpo.set('csrf_token', form.elements.csrf_token.value);
                    try {
                        const respuesta = await fetch(url, { method: 'POST', body: cuerpo });
                        const datos = await respuesta.json();
                        if (!respuesta.ok || datos.status !== 'success') throw new Error(datos.message);
                        if (String(form.elements.id.value) === String(frase.id)) nueva();
                        mostrar(datos.frases);
                        aviso.textContent = 'Frase eliminada. Dejará de aparecer al abrir o recargar Pagos.';
                    } catch (error) {
                        aviso.textContent = error.message || 'No se pudo eliminar la frase.';
                    } finally {
                        guardando = false;
                        eliminar.disabled = false;
                        guardar.disabled = false;
                    }
                });
                const acciones = document.createElement('div');
                acciones.className = 'self-end flex flex-wrap gap-2';
                acciones.append(editar, eliminar);
                fila.append(texto, acciones);
                grupo.append(fila);
            }
            lista.append(grupo);
        }
    }
    document.getElementById('abrirFrasesPago').addEventListener('click', async () => {
        nueva();
        lista.replaceChildren();
        modal.showModal();
        aviso.textContent = 'Cargando frases…';
        try {
            const respuesta = await fetch(url, { cache: 'no-store' });
            const datos = await respuesta.json();
            if (!respuesta.ok || datos.status !== 'success') throw new Error(datos.message);
            mostrar(datos.frases);
            aviso.textContent = '';
        } catch (error) { aviso.textContent = error.message || 'No se pudieron cargar las frases.'; }
    });
    document.getElementById('cerrarFrasesPago').addEventListener('click', () => { if (!guardando) modal.close(); });
    modal.addEventListener('cancel', e => { if (guardando) e.preventDefault(); });
    // El backdrop de un dialog recibe los eventos con el propio dialog como destino.
    const fueraDelModal = e => {
        const rect = modal.getBoundingClientRect();
        return e.target === modal && (e.clientX < rect.left || e.clientX > rect.right ||
            e.clientY < rect.top || e.clientY > rect.bottom);
    };
    let inicioFuera = false;
    modal.addEventListener('pointerdown', e => { inicioFuera = fueraDelModal(e); });
    modal.addEventListener('click', e => {
        if (inicioFuera && fueraDelModal(e) && !guardando) modal.close();
        inicioFuera = false;
    });
    document.getElementById('nuevaFrasePago').addEventListener('click', () => { if (!guardando) nueva(); });
    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (guardando) return;
        const datosForm = new FormData(form);
        datosForm.set('estado', form.elements.estado.value);
        guardando = true;
        const boton = form.querySelector('[type="submit"]');
        boton.disabled = true;
        form.elements.texto.readOnly = true;
        aviso.textContent = 'Guardando…';
        try {
            const respuesta = await fetch(url, { method: 'POST', body: datosForm });
            const datos = await respuesta.json();
            if (!respuesta.ok || datos.status !== 'success') throw new Error(datos.message);
            mostrar(datos.frases);
            nueva();
            aviso.textContent = 'Frase guardada. Ya está disponible al abrir el módulo de pagos.';
        } catch (error) { aviso.textContent = error.message || 'No se pudo guardar la frase.'; }
        finally { guardando = false; boton.disabled = false; form.elements.texto.readOnly = false; }
    });
})();
