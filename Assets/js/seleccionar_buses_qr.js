// Selección independiente de los filtros del listado principal.
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalSeleccionarBusesQR');
    if (!modal) return;
    const buses = JSON.parse(document.getElementById('datosBusesQR').textContent);
    const seleccionados = new Map();
    const abrir = document.getElementById('btnSeleccionarBusesQR');
    const cerrar = document.getElementById('btnCerrarSeleccionBusesQR');
    const buscar = document.getElementById('buscarBusQR');
    const resultados = document.getElementById('resultadosBusesQR');
    const tabla = document.getElementById('tablaBusesQR');
    const estadoBusqueda = document.getElementById('estadoBusquedaBusesQR');
    const estado = document.getElementById('estadoImpresionBusesQR');
    const imprimir = document.getElementById('btnImprimirBusesQR');
    let generando = false;
    let overflowAnterior = '';

    const actualizarResultados = () => {
        const consulta = buscar.value.trim().toLocaleLowerCase('es');
        const coincidencias = buses.filter(bus => !seleccionados.has(bus.id) &&
            (bus.disco.toLocaleLowerCase('es').includes(consulta) || bus.placa.toLocaleLowerCase('es').includes(consulta)));
        resultados.replaceChildren();
        estadoBusqueda.textContent = coincidencias.length
            ? `${coincidencias.length} coincidencias. Seleccione un bus para agregarlo.`
            : 'No hay buses disponibles que coincidan con la búsqueda.';
        coincidencias.forEach(bus => {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-blue-50 focus:bg-blue-50';
            boton.textContent = `Disco ${bus.disco} · ${bus.placa} — Agregar`;
            boton.disabled = generando;
            boton.addEventListener('click', () => {
                seleccionados.set(bus.id, bus);
                buscar.value = '';
                estado.textContent = '';
                actualizarTabla();
                actualizarResultados();
                buscar.focus();
            });
            resultados.append(boton);
        });
    };

    const actualizarTabla = () => {
        tabla.replaceChildren();
        document.getElementById('cantidadBusesQR').textContent = seleccionados.size;
        imprimir.disabled = generando || seleccionados.size === 0;
        if (!seleccionados.size) {
            const fila = tabla.insertRow();
            const celda = fila.insertCell();
            celda.colSpan = 3;
            celda.className = 'p-4 text-center text-gray-500';
            celda.textContent = 'Agregue buses desde los resultados de búsqueda.';
        }
        seleccionados.forEach(bus => {
            const fila = tabla.insertRow();
            [bus.disco, bus.placa].forEach(texto => {
                const celda = fila.insertCell();
                celda.className = 'p-3';
                celda.textContent = texto;
            });
            const celda = fila.insertCell();
            celda.className = 'p-3';
            const quitar = document.createElement('button');
            quitar.type = 'button';
            quitar.className = 'rounded px-3 py-2 font-bold text-red-700 hover:bg-red-50';
            quitar.textContent = 'Quitar';
            quitar.setAttribute('aria-label', `Quitar bus disco ${bus.disco}`);
            quitar.disabled = generando;
            quitar.addEventListener('click', () => {
                seleccionados.delete(bus.id);
                estado.textContent = '';
                actualizarTabla();
                actualizarResultados();
                buscar.focus();
            });
            celda.append(quitar);
        });
    };

    const cerrarModal = () => {
        if (generando) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = overflowAnterior;
        abrir.focus();
    };
    abrir.addEventListener('click', () => {
        overflowAnterior = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        actualizarTabla();
        actualizarResultados();
        buscar.focus();
    });
    cerrar.addEventListener('click', cerrarModal);
    modal.addEventListener('click', evento => {
        if (evento.target === modal) cerrarModal();
    });
    modal.addEventListener('keydown', evento => {
        if (evento.key === 'Escape') cerrarModal();
        if (evento.key !== 'Tab') return;
        const controles = [...modal.querySelectorAll('button:not(:disabled), input:not(:disabled)')];
        const primero = controles[0];
        const ultimo = controles[controles.length - 1];
        if (!primero) { evento.preventDefault(); return; }
        if (evento.shiftKey && document.activeElement === primero) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && document.activeElement === ultimo) {
            evento.preventDefault();
            primero.focus();
        }
    });
    buscar.addEventListener('input', actualizarResultados);

    imprimir.addEventListener('click', async () => {
        if (generando || !seleccionados.size) return;
        generando = true;
        buscar.disabled = true;
        cerrar.disabled = true;
        modal.setAttribute('aria-busy', 'true');
        actualizarTabla();
        actualizarResultados();
        try {
            if (!window.jspdf?.jsPDF || typeof QRCode === 'undefined') {
                throw new Error('No se cargaron las herramientas de generación.');
            }
            const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true });
            pdf.setProperties({ title: 'QR de buses seleccionados - Ejecuttrans' });
            const elegidos = [...seleccionados.values()];
            for (const [indice, bus] of elegidos.entries()) {
                estado.textContent = `Preparando QR ${indice + 1} de ${elegidos.length}…`;
                // Se usa un contenedor separado para no alterar el QR individual del módulo.
                const contenedor = document.createElement('div');
                new QRCode(contenedor, { text: bus.disco, width: 480, height: 480, correctLevel: QRCode.CorrectLevel.H });
                const qr = contenedor.querySelector('canvas');
                if (!qr) throw new Error('No se pudo generar el QR.');
                try {
                    await window.QRConLogo.aplicar(qr, '../../Assets/images/logo-ejecuttrans.png');
                } catch (error) {
                    console.error('No se pudo agregar el logo al QR:', error);
                }
                const etiqueta = document.createElement('canvas');
                etiqueta.width = 600;
                etiqueta.height = 740;
                const contexto = etiqueta.getContext('2d');
                contexto.fillStyle = '#fff';
                contexto.fillRect(0, 0, 600, 740);
                contexto.fillStyle = '#111';
                contexto.textAlign = 'center';
                contexto.font = '32px Arial, sans-serif';
                contexto.fillText('EJECUTTRANS', 300, 82, 540);
                contexto.imageSmoothingEnabled = false;
                contexto.drawImage(qr, 110, 142, 380, 380);
                contexto.font = 'bold 53px Arial, sans-serif';
                contexto.fillText(`Disco ${bus.disco}`, 300, 635, 540);
                contexto.font = '34px Arial, sans-serif';
                contexto.fillText(`Placa: ${bus.placa}`, 300, 702, 540);
                // Seis etiquetas por A4: dos columnas y tres filas, un 20 % más grandes.
                if (indice > 0 && indice % 6 === 0) pdf.addPage();
                const posicion = indice % 6;
                const x = 20.25 + (posicion % 2) * 97.5;
                const y = 10 + Math.floor(posicion / 2) * 93.8;
                pdf.addImage(etiqueta.toDataURL('image/png'), 'PNG', x, y, 72, 88.8);
                pdf.setDrawColor(170);
                pdf.setLineWidth(0.2);
                pdf.rect(x, y, 72, 88.8);
                // Permite actualizar el progreso también en selecciones grandes.
                await new Promise(resolve => setTimeout(resolve, 0));
            }
            await pdf.save('QR_buses_seleccionados.pdf', { returnPromise: true });
            estado.textContent = `PDF generado con ${elegidos.length} buses. Abra el archivo descargado para imprimirlo.`;
        } catch (error) {
            console.error('Error al generar el PDF de buses:', error);
            estado.textContent = 'No se pudo generar el PDF. Intente nuevamente; si el problema continúa, recargue la página.';
        } finally {
            generando = false;
            buscar.disabled = false;
            cerrar.disabled = false;
            modal.removeAttribute('aria-busy');
            actualizarTabla();
            actualizarResultados();
            imprimir.focus();
        }
    });
});
