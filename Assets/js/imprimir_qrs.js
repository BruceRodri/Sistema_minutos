(async () => {
    const boton = document.getElementById('imprimir');
    const mensaje = document.getElementById('mensaje');
    const codigos = [...document.querySelectorAll('.qr')];
    const logoQr = '../../Assets/images/logo-ejecuttrans.png';
    if (!codigos.length) return;

    try {
        if (typeof QRCode === 'undefined') throw new Error('No se pudo cargar el generador QR.');
        await Promise.all(codigos.map(async (contenedor) => {
            new QRCode(contenedor, {
                text: contenedor.dataset.disco,
                width: 240,
                height: 240,
                correctLevel: QRCode.CorrectLevel.H
            });
            // Una imagen fija evita que la conversión interna de QRCode altere la impresión.
            const canvas = contenedor.querySelector('canvas');
            if (!canvas) throw new Error('No se pudo generar un código QR.');
            try {
                await window.QRConLogo.aplicar(canvas, logoQr);
            } catch (errorLogo) {
                // El QR sigue disponible aun si el recurso visual no carga.
                console.error('No se pudo agregar el logo al QR:', errorLogo);
            }
            const imagen = new Image();
            imagen.alt = 'QR del disco ' + contenedor.dataset.disco;
            imagen.src = canvas.toDataURL('image/png');
            await imagen.decode();
            contenedor.replaceChildren(imagen);
        }));
        boton.disabled = false;
        mensaje.textContent = 'Etiquetas listas. Puedes imprimirlas o guardarlas como PDF.';
        boton.addEventListener('click', () => window.print());
    } catch (error) {
        mensaje.textContent = 'No se pudieron preparar todos los QR. Recarga la página para intentar de nuevo.';
        console.error(error);
    }
})();
