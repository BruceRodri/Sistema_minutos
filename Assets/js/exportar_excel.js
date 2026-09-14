document.addEventListener('DOMContentLoaded', () => {
    const modulo = location.pathname.split('/').pop().replace('.php', '');
    if (!['dashboard','buses','usuarios','socios','turnos','valores','pagos'].includes(modulo)) return;
    const cabecera = document.querySelector('main > header') || document.querySelector('main header');
    if (!cabecera) return;
    const grupo = document.createElement('div');
    grupo.className = 'flex flex-wrap gap-2';
    for (const seccion of (modulo === 'pagos' ? ['pagos','manuales'] : [modulo])) {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-100';
        boton.innerHTML = '<i class="fas fa-file-excel" aria-hidden="true"></i>';
        boton.append(document.createTextNode(modulo === 'pagos' ? (seccion === 'manuales' ? 'Excel manuales' : 'Excel pagos') : 'Exportar Excel'));
        boton.addEventListener('click', () => {
            const url = new URL(location.href);
            url.searchParams.set('exportar','1');
            url.searchParams.set('seccion_exp', seccion);
            url.searchParams.delete('pagina');
            // Usar los valores actuales de filtros, incluso antes del envío automático.
            document.querySelectorAll('form[method="GET" i] input[name], form[method="GET" i] select[name]').forEach(control => {
                if (control.name !== 'pagina') url.searchParams.set(control.name, control.value);
            });
            const form = document.createElement('form');
            form.method = 'POST'; form.action = url; form.hidden = true;
            if (modulo === 'usuarios' || modulo === 'socios') {
                const ids = [...document.querySelectorAll('tr[data-export-id]')].filter(f => !f.classList.contains('hidden')).map(f => f.dataset.exportId);
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'ids_filtrados'; input.value = JSON.stringify(ids); form.append(input);
            }
            document.body.append(form); form.submit(); form.remove();
        });
        grupo.append(boton);
    }
    cabecera.classList.add('flex-wrap','gap-3','py-3');
    cabecera.classList.remove('h-16');

    // Agrupar todos los botones a la derecha, junto al de Excel y en el mismo orden.
    const contenedorAcciones = cabecera.querySelector(':scope > div:not(:first-child)') || cabecera.querySelector(':scope > button');
    if (contenedorAcciones && contenedorAcciones.tagName === 'DIV') {
        contenedorAcciones.classList.add('items-center');
        contenedorAcciones.append(grupo);
    } else {
        const contenedor = document.createElement('div');
        contenedor.className = 'flex flex-wrap items-center gap-2';
        if (contenedorAcciones) {
            contenedorAcciones.before(contenedor);
            contenedor.append(contenedorAcciones);
        } else {
            cabecera.append(contenedor);
        }
        contenedor.append(grupo);
    }
});
