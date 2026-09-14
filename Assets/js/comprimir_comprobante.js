// Assets/js/comprimir_comprobante.js
// Comprime las imágenes del comprobante antes de subirlas para acelerar la carga en móvil.
window.comprimirComprobante = async function comprimirComprobante(archivo, maxLado = 1600, calidad = 0.85) {
    if (!archivo || archivo.type === 'application/pdf' || !String(archivo.type || '').startsWith('image/')) {
        return archivo;
    }
    let origen = null;
    try {
        origen = await cargarImagenComprobante(archivo);
        const ladoMayor = Math.max(origen.width, origen.height);
        const factor = Math.min(1, maxLado / ladoMayor);
        if (factor >= 1) {
            if (origen.close) origen.close();
            return archivo;
        }
        const ancho = Math.max(1, Math.round(origen.width * factor));
        const alto = Math.max(1, Math.round(origen.height * factor));
        const lienzo = document.createElement('canvas');
        lienzo.width = ancho;
        lienzo.height = alto;
        const contexto = lienzo.getContext('2d', { alpha: false });
        contexto.fillStyle = '#ffffff';
        contexto.fillRect(0, 0, ancho, alto);
        contexto.drawImage(origen, 0, 0, ancho, alto);
        const blob = await new Promise((resolver, rechazar) => {
            lienzo.toBlob(b => b ? resolver(b) : rechazar(new Error('sin_blob')), 'image/jpeg', calidad);
        });
        if (origen.close) origen.close();
        if (blob.size >= archivo.size) return archivo;
        const base = archivo.name.replace(/\.[^.]+$/, '') || 'comprobante';
        return new File([blob], base + '.jpg', { type: 'image/jpeg' });
    } catch (_) {
        if (origen && origen.close) origen.close();
        return archivo;
    }
};

async function cargarImagenComprobante(archivo) {
    const url = URL.createObjectURL(archivo);
    try {
        if (typeof createImageBitmap === 'function') {
            try {
                return await createImageBitmap(archivo, { imageOrientation: 'from-image', colorSpaceConversion: 'none' });
            } catch (_) { /* Continuar con <img>. */ }
        }
        const imagen = new Image();
        imagen.decoding = 'async';
        await new Promise((resolver, rechazar) => {
            imagen.onload = () => resolver();
            imagen.onerror = () => rechazar(new Error('carga_fallida'));
            imagen.src = url;
        });
        return imagen;
    } finally {
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }
}