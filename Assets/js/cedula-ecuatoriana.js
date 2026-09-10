(function (global) {
    'use strict';

    const normalizar = (valor) => String(valor || '').replace(/\D/g, '').slice(0, 10);

    const validar = (cedula) => {
        const numero = normalizar(cedula);
        if (numero.length !== 10) return false;

        const provincia = Number(numero.slice(0, 2));
        const tercerDigito = Number(numero[2]);
        if (provincia < 1 || provincia > 24 || tercerDigito > 5) return false;

        let suma = 0;
        for (let i = 0; i < 9; i += 1) {
            let valor = Number(numero[i]) * (i % 2 === 0 ? 2 : 1);
            if (valor > 9) valor -= 9;
            suma += valor;
        }

        return (10 - (suma % 10)) % 10 === Number(numero[9]);
    };

    const mostrarEstado = (input, tipo, mensaje) => {
        const destino = input.dataset.mensajeCedula
            ? document.getElementById(input.dataset.mensajeCedula)
            : null;
        input.classList.remove('border-red-500', 'bg-red-50', 'focus:border-red-500', 'focus:ring-red-200', 'border-green-500', 'bg-green-50', 'focus:border-green-500', 'focus:ring-green-200');

        if (tipo === 'error') {
            input.classList.add('border-red-500', 'bg-red-50', 'focus:border-red-500', 'focus:ring-red-200');
        } else if (tipo === 'success') {
            input.classList.add('border-green-500', 'bg-green-50', 'focus:border-green-500', 'focus:ring-green-200');
        }

        if (destino) {
            destino.textContent = mensaje || '';
            destino.className = tipo === 'error'
                ? 'mt-1.5 text-xs font-bold text-red-600'
                : tipo === 'success'
                    ? 'mt-1.5 text-xs font-bold text-green-600'
                    : 'hidden';
        }
    };

    const validarCampo = (input, mostrarVacio = false) => {
        input.value = normalizar(input.value);
        let mensaje = '';

        if (input.value === '') {
            mensaje = mostrarVacio ? 'Ingrese el número de cédula.' : '';
        } else if (input.value.length !== 10) {
            mensaje = 'La cédula debe tener exactamente 10 dígitos.';
        } else if (!validar(input.value)) {
            mensaje = 'El número ingresado no es una cédula ecuatoriana válida.';
        }

        input.setCustomValidity(mensaje);
        mostrarEstado(input, mensaje ? 'error' : (input.value ? 'success' : ''), mensaje || (input.value ? 'Cédula ecuatoriana válida.' : ''));
        return mensaje === '' && input.value !== '';
    };

    const inicializar = (raiz = document) => {
        raiz.querySelectorAll('input[data-validar-cedula]').forEach((input) => {
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('maxlength', '10');
            input.addEventListener('input', () => validarCampo(input));
            input.addEventListener('blur', () => validarCampo(input, true));
        });
    };

    global.CedulaEcuador = { normalizar, validar, validarCampo, mostrarEstado };
    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', () => inicializar());
    }
}(typeof window !== 'undefined' ? window : globalThis));

