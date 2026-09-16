(() => {
    const cacheImagenes = new Map();
    const cacheLimites = new WeakMap();

    const cargarImagen = (src) => {
        if (!cacheImagenes.has(src)) {
            cacheImagenes.set(src, new Promise((resolve, reject) => {
                const imagen = new Image();
                imagen.decoding = 'async';
                imagen.addEventListener('load', () => resolve(imagen), { once: true });
                imagen.addEventListener('error', () => reject(new Error('No se pudo cargar el logo para el QR.')), { once: true });
                imagen.src = src;
            }));
        }

        return cacheImagenes.get(src);
    };

    const obtenerLimitesVisibles = (imagen) => {
        if (cacheLimites.has(imagen)) return cacheLimites.get(imagen);

        const lienzo = document.createElement('canvas');
        lienzo.width = imagen.naturalWidth;
        lienzo.height = imagen.naturalHeight;
        const contexto = lienzo.getContext('2d', { willReadFrequently: true });
        contexto.drawImage(imagen, 0, 0);

        const pixeles = contexto.getImageData(0, 0, lienzo.width, lienzo.height).data;
        let izquierda = lienzo.width;
        let arriba = lienzo.height;
        let derecha = -1;
        let abajo = -1;

        for (let y = 0; y < lienzo.height; y += 1) {
            for (let x = 0; x < lienzo.width; x += 1) {
                const indice = (y * lienzo.width + x) * 4;
                const visible = pixeles[indice + 3] > 12
                    && (pixeles[indice] < 248 || pixeles[indice + 1] < 248 || pixeles[indice + 2] < 248);

                if (visible) {
                    izquierda = Math.min(izquierda, x);
                    arriba = Math.min(arriba, y);
                    derecha = Math.max(derecha, x);
                    abajo = Math.max(abajo, y);
                }
            }
        }

        const limites = derecha < izquierda || abajo < arriba
            ? { x: 0, y: 0, ancho: lienzo.width, alto: lienzo.height }
            : {
                x: izquierda,
                y: arriba,
                ancho: derecha - izquierda + 1,
                alto: abajo - arriba + 1
            };

        cacheLimites.set(imagen, limites);
        return limites;
    };

    const rectanguloRedondeado = (contexto, x, y, ancho, alto, radio) => {
        const r = Math.min(radio, ancho / 2, alto / 2);
        contexto.beginPath();
        contexto.moveTo(x + r, y);
        contexto.lineTo(x + ancho - r, y);
        contexto.quadraticCurveTo(x + ancho, y, x + ancho, y + r);
        contexto.lineTo(x + ancho, y + alto - r);
        contexto.quadraticCurveTo(x + ancho, y + alto, x + ancho - r, y + alto);
        contexto.lineTo(x + r, y + alto);
        contexto.quadraticCurveTo(x, y + alto, x, y + alto - r);
        contexto.lineTo(x, y + r);
        contexto.quadraticCurveTo(x, y, x + r, y);
        contexto.closePath();
    };

    const aplicar = async (canvas, src) => {
        if (!(canvas instanceof HTMLCanvasElement)) {
            throw new Error('No se encontro el lienzo del codigo QR.');
        }

        const imagen = await cargarImagen(src);
        const recorte = obtenerLimitesVisibles(imagen);
        const maximoAncho = canvas.width * 0.32;
        const maximoAlto = canvas.height * 0.16;
        const escala = Math.min(maximoAncho / recorte.ancho, maximoAlto / recorte.alto);
        const ancho = Math.max(1, Math.round(recorte.ancho * escala));
        const alto = Math.max(1, Math.round(recorte.alto * escala));
        const x = Math.round((canvas.width - ancho) / 2);
        const y = Math.round((canvas.height - alto) / 2);
        const margen = Math.max(3, Math.round(canvas.width * 0.018));
        const contexto = canvas.getContext('2d');

        contexto.save();
        contexto.fillStyle = '#ffffff';
        rectanguloRedondeado(
            contexto,
            x - margen,
            y - margen,
            ancho + margen * 2,
            alto + margen * 2,
            margen
        );
        contexto.fill();
        contexto.imageSmoothingEnabled = true;
        contexto.imageSmoothingQuality = 'high';
        contexto.drawImage(
            imagen,
            recorte.x,
            recorte.y,
            recorte.ancho,
            recorte.alto,
            x,
            y,
            ancho,
            alto
        );
        contexto.restore();

        return canvas;
    };

    window.QRConLogo = { aplicar };
})();
