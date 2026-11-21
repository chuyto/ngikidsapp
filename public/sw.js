/* NGIKids Service Worker
   Estrategia deliberadamente conservadora:
   - NUNCA se cachean páginas autenticadas, HTML, JSON, PDFs ni respuestas
     con query string. Esos datos son privados (firmas, niños, reportes).
   - Navegación: red únicamente; si no hay conexión se muestra offline.html.
   - Solo assets públicos inmutables (build, imágenes, iconos, manifest):
     stale-while-revalidate.
   - Al cerrar sesión la app envía CLEAR_CACHES para borrar lo cacheado. */

const CACHE = 'ngikids-static-v2';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/favicon.ico',
    '/favicon-16x16.png',
    '/favicon-32x32.png',
    '/img/icons/icon-192.png',
    '/img/icons/icon-512.png',
    '/img/icons/maskable-192.png',
    '/img/icons/maskable-512.png',
    '/img/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => Promise.allSettled(PRECACHE.map((url) => cache.add(url))))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'CLEAR_CACHES') {
        event.waitUntil(
            caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
        );
    }
});

function esAssetPublico(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/img/')
        || url.pathname.startsWith('/fonts/')
        || url.pathname === '/manifest.webmanifest'
        || url.pathname.startsWith('/favicon')
        || /\.(css|js|woff2?|ttf|png|jpe?g|gif|webp|svg|ico)$/i.test(url.pathname);
}

function esRutaPrivada(url) {
    return url.pathname.startsWith('/reportes')
        || url.pathname.startsWith('/asistencias')
        || url.pathname.startsWith('/padres')
        || url.pathname.startsWith('/hijos')
        || url.pathname.startsWith('/usuarios')
        || url.pathname.startsWith('/dashboard')
        || url.pathname.startsWith('/profile')
        || url.pathname.startsWith('/login')
        || url.pathname.toLowerCase().endsWith('.pdf')
        || url.pathname.startsWith('/api/');
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== location.origin) return;
    if (request.headers.get('range')) return;

    // Páginas y datos privados: siempre red. Si no hay conexión, offline.html.
    if (request.mode === 'navigate' || esRutaPrivada(url)) {
        event.respondWith(
            fetch(request).catch(() => {
                if (request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }

                return new Response(
                    JSON.stringify({ error: 'Sin conexión. Vuelve a intentarlo.' }),
                    { status: 503, headers: { 'Content-Type': 'application/json' } }
                );
            })
        );
        return;
    }

    // Assets públicos, sin query string (las URLs versionadas de Vite son inmutables).
    if (!url.search && esAssetPublico(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const red = fetch(request)
                    .then((response) => {
                        if (response.ok && response.type === 'basic') {
                            const copy = response.clone();
                            caches.open(CACHE).then((cache) => cache.put(request, copy));
                        }

                        return response;
                    })
                    .catch(() => cached);

                return cached || red;
            })
        );
    }
});
