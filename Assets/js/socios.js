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

    // ---------- Asignar discos (búsqueda + lista múltiple) ----------
    const modalAgregarDisco = document.getElementById('modalAgregarDisco');
    const formAgregarDisco = document.getElementById('formAgregarDisco');

    if (modalAgregarDisco && formAgregarDisco) {
        const inputBuscarDisco = document.getElementById('buscarDisco');
        const estadoBusqueda = document.getElementById('estadoBusquedaDisco');
        const sugerencias = document.getElementById('sugerenciasDisco');
        const tbody = document.getElementById('tbodyDiscosAgregar');
        const tabla = document.getElementById('tablaDiscosAgregar');
        const btnAsignar = document.getElementById('btnAsignarDiscos');
        const inputUsuario = document.getElementById('agregarUsuarioId');
        const labelSocio = document.getElementById('modalSocioLabel');
        const btnCancelar = document.getElementById('btnCancelarAgregarDisco');

        let discosSeleccionados = [];
        let temporizadorBusqueda = null;

        const normalizar = (texto) => String(texto || '').replace(/\D+/g, '');

        const cerrarSugerencias = () => {
            sugerencias.classList.add('hidden');
            sugerencias.replaceChildren();
        };

        function renderTabla() {
            tbody.replaceChildren();
            if (!discosSeleccionados.length) {
                tabla.classList.add('hidden');
                btnAsignar.disabled = true;
                return;
            }
            tabla.classList.remove('hidden');
            btnAsignar.disabled = false;
            discosSeleccionados.forEach((disco) => {
                const fila = document.createElement('tr');

                const celDisco = document.createElement('td');
                celDisco.className = 'px-3 py-2 text-sm font-semibold text-gray-800';
                celDisco.textContent = 'Disco ' + disco.disco;

                const celPlaca = document.createElement('td');
                celPlaca.className = 'px-3 py-2 text-sm font-mono text-gray-600 whitespace-nowrap';
                celPlaca.textContent = disco.placa;

                const celBoton = document.createElement('td');
                celBoton.className = 'px-3 py-2 text-right';
                const btnQuitar = document.createElement('button');
                btnQuitar.type = 'button';
                btnQuitar.dataset.discoId = String(disco.id);
                btnQuitar.title = 'Quitar disco';
                btnQuitar.className = 'btnQuitarDisco text-red-600 hover:text-red-800 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors';
                btnQuitar.innerHTML = '<i class="fas fa-times"></i>';
                celBoton.append(btnQuitar);

                fila.append(celDisco, celPlaca, celBoton);
                tbody.append(fila);
            });
        }

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btnQuitarDisco');
            if (!btn) return;
            discosSeleccionados = discosSeleccionados.filter((d) => String(d.id) !== btn.dataset.discoId);
            renderTabla();
            inputBuscarDisco.focus();
        });

        inputBuscarDisco.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const primera = sugerencias.querySelector('button');
                if (primera) primera.click();
            }
        });

        inputBuscarDisco.addEventListener('input', () => {
            clearTimeout(temporizadorBusqueda);
            const q = inputBuscarDisco.value.trim();
            if (!q) {
                cerrarSugerencias();
                estadoBusqueda.textContent = '';
                return;
            }
            estadoBusqueda.textContent = 'Buscando...';
            temporizadorBusqueda = setTimeout(async () => {
                const formData = new FormData();
                formData.append('accion', 'buscar_discos');
                formData.append('q', q);
                let discos = [];
                try {
                    const response = await fetch(urlControlador, { method: 'POST', body: formData });
                    const data = await response.json();
                    discos = data.discos || [];
                } catch (error) {
                    console.error(error);
                    estadoBusqueda.textContent = 'Error al buscar discos.';
                    cerrarSugerencias();
                    return;
                }

                const disponibles = discos.filter((d) => !discosSeleccionados.some((s) => String(s.id) === String(d.id)));
                estadoBusqueda.textContent = disponibles.length
                    ? 'Mostrando ' + disponibles.length + ' disco(s) disponibles.'
                    : 'No hay discos disponibles que coincidan con tu búsqueda.';
                sugerencias.replaceChildren();

                disponibles.forEach((disco) => {
                    const fila = document.createElement('li');
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'w-full px-3 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-300 transition-colors';
                    const nombre = document.createElement('span');
                    nombre.className = 'block text-sm font-semibold text-gray-800';
                    nombre.textContent = 'Disco ' + disco.disco;
                    const placa = document.createElement('span');
                    placa.className = 'block text-xs text-gray-500 mt-1 font-mono';
                    placa.textContent = disco.placa;
                    boton.append(nombre, placa);
                    boton.addEventListener('click', () => {
                        discosSeleccionados.push({ id: disco.id, disco: disco.disco, placa: disco.placa });
                        renderTabla();
                        cerrarSugerencias();
                        inputBuscarDisco.value = '';
                        estadoBusqueda.textContent = '';
                        inputBuscarDisco.focus();
                    });
                    fila.append(boton);
                    sugerencias.append(fila);
                });

                sugerencias.classList.toggle('hidden', disponibles.length === 0);
            }, 250);
        });

        document.addEventListener('click', (e) => {
            if (modalAgregarDisco.classList.contains('hidden')) return;
            if (!modalAgregarDisco.contains(e.target)) cerrarSugerencias();
        });

        document.querySelectorAll('.btnAgregarDisco:not(:disabled)').forEach((btn) => {
            btn.addEventListener('click', () => {
                inputUsuario.value = btn.dataset.usuarioId;
                labelSocio.textContent = btn.dataset.socio;
                discosSeleccionados = [];
                renderTabla();
                inputBuscarDisco.value = '';
                estadoBusqueda.textContent = '';
                cerrarSugerencias();
                modalAgregarDisco.classList.remove('hidden');
                inputBuscarDisco.focus();
            });
        });

        btnCancelar.addEventListener('click', () => {
            modalAgregarDisco.classList.add('hidden');
        });

        formAgregarDisco.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!discosSeleccionados.length) {
                estadoBusqueda.textContent = 'Busca y agrega al menos un disco a la lista.';
                inputBuscarDisco.focus();
                return;
            }

            const formData = new FormData();
            formData.append('accion', 'asignar_discos');
            formData.append('usuario_id', inputUsuario.value);
            discosSeleccionados.forEach((disco) => formData.append('bus_ids[]', disco.id));

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
