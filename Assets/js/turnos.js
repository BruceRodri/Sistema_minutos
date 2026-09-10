// Assets/js/turnos.js

document.addEventListener('DOMContentLoaded', () => {
    const modalFlota = document.getElementById('modalFlota');
    const botonAbrirFlota = document.getElementById('abrirModalFlota');
    const detalleUnidad = document.getElementById('detalleUnidad');
    const botonDeshabilitarTurno = document.getElementById('deshabilitarTurnoFlota');
    const comentarioDeshabilitar = document.getElementById('comentarioDeshabilitarTurno');
    const contadorComentario = document.getElementById('contadorComentarioTurno');
    const modalHabilitarTurno = document.getElementById('modalHabilitarTurno');
    const confirmarHabilitarTurno = document.getElementById('confirmarHabilitarTurno');
    let elementoFocoAnterior = null;
    let unidadSeleccionada = null;
    let botonOrigenHabilitar = null;

    const cerrarModalFlota = () => {
        if (!modalFlota) return;
        modalFlota.classList.add('hidden');
        modalFlota.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        detalleUnidad?.classList.add('hidden');
        unidadSeleccionada = null;
        elementoFocoAnterior?.focus();
    };

    const abrirModalFlota = () => {
        if (!modalFlota) return;
        elementoFocoAnterior = document.activeElement;
        modalFlota.classList.remove('hidden');
        modalFlota.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        modalFlota.querySelector('[data-cerrar-modal-flota]')?.focus();
    };

    botonAbrirFlota?.addEventListener('click', abrirModalFlota);
    modalFlota?.querySelectorAll('[data-cerrar-modal-flota]').forEach((elemento) => {
        elemento.addEventListener('click', cerrarModalFlota);
    });

    modalFlota?.addEventListener('click', (evento) => {
        const unidad = evento.target.closest('.unidadFlota');
        if (!unidad || unidad.dataset.ocupado !== '1' || !detalleUnidad) return;

        document.getElementById('detalleDisco').textContent = `Unidad ${unidad.dataset.disco}${unidad.dataset.placa ? ` · ${unidad.dataset.placa}` : ''}`;
        document.getElementById('detalleNombre').textContent = unidad.dataset.nombre || 'Conductor sin nombre';
        document.getElementById('detalleCodigo').textContent = `Código de conductor: ${unidad.dataset.codigo || 'Sin código'}`;
        document.getElementById('detalleHora').textContent = unidad.dataset.hora ? `Turno abierto a las ${unidad.dataset.hora}` : '';
        botonDeshabilitarTurno.dataset.turnoId = unidad.dataset.turnoId || '';
        botonDeshabilitarTurno.dataset.disco = unidad.dataset.disco || '';
        comentarioDeshabilitar.value = '';
        contadorComentario.textContent = '0/500';
        botonDeshabilitarTurno.disabled = true;
        unidadSeleccionada = unidad;
        detalleUnidad.classList.remove('hidden');
        detalleUnidad.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    comentarioDeshabilitar?.addEventListener('input', () => {
        const comentario = comentarioDeshabilitar.value;
        contadorComentario.textContent = `${comentario.length}/500`;
        botonDeshabilitarTurno.disabled = comentario.trim() === '';
    });

    botonDeshabilitarTurno?.addEventListener('click', async () => {
        const turnoId = botonDeshabilitarTurno.dataset.turnoId;
        const disco = botonDeshabilitarTurno.dataset.disco;
        const comentario = comentarioDeshabilitar.value.trim();
        if (!turnoId || !unidadSeleccionada) return;
        if (!comentario) {
            comentarioDeshabilitar.focus();
            return;
        }
        if (!confirm(`¿Deshabilitar el turno de la unidad ${disco}? El bus y el conductor quedarán libres.`)) return;

        botonDeshabilitarTurno.disabled = true;
        const textoOriginal = botonDeshabilitarTurno.innerHTML;
        botonDeshabilitarTurno.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deshabilitando...';

        const formData = new FormData();
        formData.append('accion', 'deshabilitar_turno');
        formData.append('turno_id', turnoId);
        formData.append('comentario', comentario);

        try {
            const response = await fetch('../../Controllers/TurnoController.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'No se pudo deshabilitar el turno.');
            }

            unidadSeleccionada.dataset.ocupado = '0';
            unidadSeleccionada.dataset.turnoId = '';
            unidadSeleccionada.setAttribute('aria-disabled', 'true');
            unidadSeleccionada.className = 'unidadFlota group relative flex min-h-24 cursor-default flex-col items-center justify-center rounded-2xl border-2 border-gray-300 bg-gray-200 p-3 text-gray-600 transition-all';
            unidadSeleccionada.querySelector('i').className = 'fas fa-bus text-xl text-gray-400';
            const etiquetas = unidadSeleccionada.querySelectorAll('span');
            etiquetas[1].textContent = 'Sin turno';
            etiquetas[1].className = 'text-[10px] font-bold uppercase tracking-wide text-gray-500';

            const unidades = [...document.querySelectorAll('#gridFlota .unidadFlota')];
            const ocupados = unidades.filter((unidad) => unidad.dataset.ocupado === '1').length;
            document.getElementById('contadorFlota').textContent = `${ocupados}/${unidades.length}`;
            document.getElementById('resumenFlota').textContent = `${ocupados} de ${unidades.length} unidades en turno`;
            detalleUnidad.classList.add('hidden');
            unidadSeleccionada = null;
            alert(data.message);
        } catch (error) {
            alert(error.message || 'Error de conexión con el servidor.');
        } finally {
            botonDeshabilitarTurno.disabled = comentarioDeshabilitar.value.trim() === '';
            botonDeshabilitarTurno.innerHTML = textoOriginal;
        }
    });

    const cerrarModalHabilitar = () => {
        if (!modalHabilitarTurno) return;
        modalHabilitarTurno.classList.add('hidden');
        modalHabilitarTurno.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        botonOrigenHabilitar?.focus();
    };

    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('.btnHabilitarTurno');
        if (!boton || !modalHabilitarTurno) return;

        botonOrigenHabilitar = boton;
        confirmarHabilitarTurno.dataset.turnoId = boton.dataset.turnoId || '';
        document.getElementById('advertenciaUnidad').textContent = `Unidad ${boton.dataset.disco || '—'}`;
        const codigo = boton.dataset.codigo ? ` · Código ${boton.dataset.codigo}` : '';
        document.getElementById('advertenciaConductor').textContent = `${boton.dataset.nombre || 'Conductor sin nombre'}${codigo}`;
        modalHabilitarTurno.classList.remove('hidden');
        modalHabilitarTurno.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        confirmarHabilitarTurno.focus();
    });

    modalHabilitarTurno?.querySelectorAll('[data-cerrar-habilitar-turno]').forEach((elemento) => {
        elemento.addEventListener('click', cerrarModalHabilitar);
    });

    confirmarHabilitarTurno?.addEventListener('click', async () => {
        const turnoId = confirmarHabilitarTurno.dataset.turnoId;
        if (!turnoId) return;

        confirmarHabilitarTurno.disabled = true;
        const textoOriginal = confirmarHabilitarTurno.innerHTML;
        confirmarHabilitarTurno.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Habilitando...';

        const formData = new FormData();
        formData.append('accion', 'habilitar_turno');
        formData.append('turno_id', turnoId);

        try {
            const response = await fetch('../../Controllers/TurnoController.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'No se pudo habilitar nuevamente el turno.');
            }

            if (botonOrigenHabilitar) {
                botonOrigenHabilitar.disabled = true;
                botonOrigenHabilitar.innerHTML = '<i class="fas fa-circle-check mr-1.5"></i>Habilitado';
            }
            cerrarModalHabilitar();
            alert(data.message);
        } catch (error) {
            alert(error.message || 'Error de conexión con el servidor.');
        } finally {
            confirmarHabilitarTurno.disabled = false;
            confirmarHabilitarTurno.innerHTML = textoOriginal;
        }
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape') return;
        if (modalHabilitarTurno && !modalHabilitarTurno.classList.contains('hidden')) cerrarModalHabilitar();
        else if (modalFlota && !modalFlota.classList.contains('hidden')) cerrarModalFlota();
    });

    // ---------- Búsqueda automática en los filtros (debounce 400ms) ----------
    const formularioFiltros = document.querySelector('form[action="turnos.php"]');
    if (formularioFiltros) {
        let temporizadorFiltros = null;
        const lanzarBusqueda = (evento) => {
            if (!evento.target.closest('form[action="turnos.php"]')) return;
            if (temporizadorFiltros) clearTimeout(temporizadorFiltros);
            temporizadorFiltros = setTimeout(() => {
                formularioFiltros.requestSubmit();
            }, 400);
        };
        document.addEventListener('input', lanzarBusqueda);
        document.addEventListener('change', lanzarBusqueda);
    }

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
                const response = await fetch('../../Controllers/TurnoController.php', {
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
