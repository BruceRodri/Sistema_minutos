// Assets/js/socios.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/SocioController.php';

    const buscarSocio = document.getElementById('buscarSocio');
    if (buscarSocio) {
        const filas = [...document.querySelectorAll('[data-busqueda-socio]')];
        const normalizarBusqueda = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        buscarSocio.addEventListener('input', () => {
            const terminos = normalizarBusqueda(buscarSocio.value).split(/\s+/).filter(Boolean);
            let visibles = 0;
            filas.forEach((fila) => {
                const texto = normalizarBusqueda(fila.dataset.busquedaSocio);
                const coincide = terminos.every((termino) => texto.includes(termino));
                fila.classList.toggle('hidden', !coincide);
                if (coincide) visibles++;
            });
            document.getElementById('sinCoincidenciasSocios').classList.toggle('hidden', visibles > 0 || filas.length === 0);
            document.getElementById('resultadoBusquedaSocios').textContent = terminos.length
                ? visibles + ' socios encontrados.'
                : '';
        });
    }

    // ---------- Asignar un disco disponible ----------
    const modalAgregarDisco = document.getElementById('modalAgregarDisco');
    const formAgregarDisco = document.getElementById('formAgregarDisco');

    if (modalAgregarDisco && formAgregarDisco) {
        document.querySelectorAll('.btnAgregarDisco:not(:disabled)').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('agregarUsuarioId').value = btn.dataset.usuarioId;
                document.getElementById('agregarBusId').value = '';
                document.getElementById('modalSocioLabel').textContent = btn.dataset.socio;
                modalAgregarDisco.classList.remove('hidden');
            });
        });

        document.getElementById('btnCancelarAgregarDisco').addEventListener('click', () => {
            modalAgregarDisco.classList.add('hidden');
        });

        formAgregarDisco.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formAgregarDisco);
            formData.append('accion', 'asignar_disco');

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error de conexión con el servidor.');
            }
        });
    }

    // ---------- Deshabilitar / Habilitar disco ----------
    document.querySelectorAll('.btnToggleDisco').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const estado = btn.dataset.estado;
            const nuevoEstado = (estado == 1) ? 0 : 1;
            const accionTexto = (nuevoEstado == 1) ? 'habilitar' : 'deshabilitar';

            if (!confirm(`¿Está seguro de ${accionTexto} el disco ${btn.dataset.disco}?`)) return;

            const formData = new FormData();
            formData.append('accion', 'cambiar_estado_disco');
            formData.append('bus_id', btn.dataset.busId);
            formData.append('estado', nuevoEstado);

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error de conexión con el servidor.');
            }
        });
    });

    // ---------- Modal Cambiar Socio ----------
    const modalCambiarSocio = document.getElementById('modalCambiarSocio');
    const formCambiarSocio = document.getElementById('formCambiarSocio');

    if (modalCambiarSocio && formCambiarSocio) {
        const buscador = document.getElementById('buscarSocioCambio');
        const selector = document.getElementById('cambioUsuario');
        const resumen = document.getElementById('coincidenciasSocio');
        const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim().replace(/\s+/g, ' ');
        const sugerencias = document.getElementById('sugerenciasSocio');
        const socios = JSON.parse(document.getElementById('datosSociosCambio').textContent);
        const filtrarSocios = () => {
            selector.value = '';
            sugerencias.replaceChildren();
            const terminos = normalizar(buscador.value).split(' ').filter(Boolean);
            if (!terminos.length) {
                resumen.textContent = 'Escribe para buscar un socio.';
                return;
            }
            const coincidencias = socios.filter((socio) =>
                terminos.every((termino) => normalizar(socio.cedula + ' ' + socio.nombre).includes(termino))
            );
            const visibles = coincidencias.slice(0, 10);
            visibles.forEach((socio) => {
                const fila = document.createElement('li');
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'w-full px-3 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-300 transition-colors';
                const nombre = document.createElement('span');
                nombre.className = 'block text-sm font-semibold text-gray-800';
                nombre.textContent = socio.nombre;
                const cedula = document.createElement('span');
                cedula.className = 'block text-xs text-gray-500 mt-1';
                cedula.textContent = socio.cedula;
                boton.append(nombre, cedula);
                boton.addEventListener('click', () => {
                    selector.value = socio.id;
                    buscador.value = socio.cedula + ' - ' + socio.nombre;
                    sugerencias.replaceChildren();
                    resumen.textContent = 'Socio seleccionado.';
                    buscador.focus();
                });
                fila.append(boton);
                sugerencias.append(fila);
            });
            resumen.textContent = coincidencias.length
                ? 'Mostrando ' + visibles.length + ' de ' + coincidencias.length + ' coincidencias.'
                : 'No se encontraron socios con esa cédula o nombre.';
        };
        buscador.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' && sugerencias.firstElementChild) {
                event.preventDefault();
                sugerencias.firstElementChild.firstElementChild.focus();
            }
        });
        buscador.addEventListener('input', filtrarSocios);
        filtrarSocios();
        document.querySelectorAll('.btnCambiarSocio').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('cambioBusId').value = btn.dataset.busId;
                document.getElementById('modalDiscoLabel').textContent = 'Disco ' + btn.dataset.disco;
                buscador.value = '';
                filtrarSocios();
                modalCambiarSocio.classList.remove('hidden');
                buscador.focus();
            });
        });

        document.getElementById('btnCancelarCambio').addEventListener('click', () => {
            modalCambiarSocio.classList.add('hidden');
        });

        formCambiarSocio.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!selector.value) {
                resumen.textContent = 'Selecciona un socio de las sugerencias antes de guardar.';
                buscador.focus();
                return;
            }

            const formData = new FormData(formCambiarSocio);
            formData.append('accion', 'cambiar_socio');

            try {
                const response = await fetch(urlControlador, { method: 'POST', body: formData });
                const data = await response.json();
                alert(data.message);
                if (data.status === 'success') window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error de conexión con el servidor.');
            }
        });
    }
});
