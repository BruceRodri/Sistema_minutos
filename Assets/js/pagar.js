// Assets/js/pagar.js

document.addEventListener('DOMContentLoaded', () => {
    const urlControlador = '../../Controllers/PagoController.php';
    const MAX_SIZE = 5 * 1024 * 1024;

    function mostrarAlerta(elemento, tipo, mensaje) {
        if (!elemento) return;
        elemento.textContent = mensaje;
        elemento.className = 'p-4 rounded-2xl text-base text-center ' +
            (tipo === 'success'
                ? 'bg-green-100 text-green-800 border border-green-200'
                : 'bg-red-100 text-red-800 border border-red-200');
        elemento.classList.remove('hidden');
    }

    function validarArchivo(archivo) {
        const tipoPermitido = archivo.type.startsWith('image/') || archivo.type === 'application/pdf';
        if (!tipoPermitido) return 'Solo se permiten imágenes y documentos PDF.';
        if (archivo.size > MAX_SIZE) return 'El archivo supera el tamaño máximo de 5 MB.';
        return null;
    }

    async function enviar(formData, alerta, btn) {
        alerta.classList.add('hidden');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xl"></i> Subiendo...';
        try {
            const response = await fetch(urlControlador, { method: 'POST', body: formData });
            const data = await response.json();
            mostrarAlerta(alerta, data.status === 'success' ? 'success' : 'error', data.message);
            if (data.status === 'success') {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (error) {
            console.error(error);
            mostrarAlerta(alerta, 'error', 'Error de conexión con el servidor.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane text-xl"></i> ' +
                (btn.id === 'btnComprobante' ? 'Subir Comprobante' : 'Subir comprobante de los días seleccionados');
        }
    }

    // ---------- Pagar el turno de HOY ----------
    const btnComprobante = document.getElementById('btnComprobante');
    const inputComprobante = document.getElementById('inputComprobante');
    const alerta = document.getElementById('alertaComprobante');

    if (btnComprobante && inputComprobante) {
        btnComprobante.addEventListener('click', () => inputComprobante.click());

        inputComprobante.addEventListener('change', async () => {
            const archivo = inputComprobante.files[0];
            if (!archivo) return;
            const error = validarArchivo(archivo);
            if (error) {
                mostrarAlerta(alerta, 'error', error);
                inputComprobante.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('accion', 'enviar_comprobante');
            formData.append('archivo', archivo);
            await enviar(formData, alerta, btnComprobante);
            inputComprobante.value = '';
        });
    }

    // ---------- Pagar varios días ----------
    const formVarios = document.getElementById('formPagarVarios');
    if (formVarios) {
        const btnPagarVarios = document.getElementById('btnPagarVarios');
        const inputVarios = document.getElementById('inputVarios');
        const alertaVarios = document.getElementById('alertaVarios');
        const totalLabel = document.getElementById('totalVarios');
        const checks = formVarios.querySelectorAll('.checkDia');

        function calcularTotal() {
            let total = 0;
            checks.forEach((c) => {
                if (c.checked) total += parseFloat(c.dataset.valor || 0);
            });
            totalLabel.textContent = '$ ' + total.toFixed(2);
        }

        checks.forEach((c) => c.addEventListener('change', calcularTotal));
        calcularTotal();

        btnPagarVarios.addEventListener('click', () => {
            inputVarios.click();
        });

        inputVarios.addEventListener('change', async () => {
            const archivo = inputVarios.files[0];
            if (!archivo) {
                inputVarios.value = '';
                return;
            }

            const seleccionados = [...checks].filter((c) => c.checked).map((c) => c.value);
            if (seleccionados.length === 0) {
                mostrarAlerta(alertaVarios, 'error', 'Selecciona al menos un día para pagar.');
                inputVarios.value = '';
                return;
            }

            const error = validarArchivo(archivo);
            if (error) {
                mostrarAlerta(alertaVarios, 'error', error);
                inputVarios.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('accion', 'pagar_varios');
            formData.append('archivo', archivo);
            seleccionados.forEach((id) => formData.append('turnos_ids[]', id));
            await enviar(formData, alertaVarios, btnPagarVarios);
            inputVarios.value = '';
        });
    }
});