const CACHE_NAME = 'ejecuttrans-v3';
const urlsToCache = [
    './',
    './index.php',
    './manifest.json',
    './Assets/icons/icon-192x192.png',
    './Assets/icons/icon-512x512.png',
    './Assets/js/auth.js',
    'https://cdn.tailwindcss.com',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Instalación del Service Worker y Caché de recursos estáticos
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
            .then(() => self.skipWaiting())
    );
});

// Activa el nuevo SW de inmediato y limpia cachés antiguas
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

// Estrategia Network-First (prioriza internet para no mostrar datos PHP viejos)
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    event.respondWith(
        fetch(event.request)
            .then(response => {
                if (response.ok) {
                    const copy = response.clone();
                    const url = new URL(event.request.url);
                    if (url.origin === self.location.origin) {
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
                    }
                }
                return response;
            })
            .catch(() => {
                if (event.request.mode === 'navigate') {
                    return caches.match('./index.php');
                }
                return caches.match(event.request);
            })
    );
});