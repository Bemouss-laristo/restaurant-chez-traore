/*
 * Chez Traoré — service worker (ce qui rend le site installable comme une application).
 *
 * Règle d'or : les PAGES ne sont jamais mises en cache. Ventes, caisse, stock et
 * rapports viennent toujours du serveur, à jour, et rien de privé ne reste sur
 * l'appareil. Seuls les fichiers qui ne changent jamais sous le même nom
 * (styles et scripts compilés, icônes) sont gardés pour ouvrir l'appli plus vite.
 *
 * Sans connexion, on affiche une page « Pas de connexion » au lieu de l'erreur
 * du navigateur.
 */

const VERSION = 'v1';
const CACHE = `chez-traore-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/favicon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key.startsWith('chez-traore-') && key !== CACHE)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

/** Fichiers dont le nom change à chaque nouvelle version : on peut les garder. */
function isImmutableAsset(url) {
    return url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/icons/');
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // On ne touche qu'aux lectures sur notre propre site (jamais aux formulaires envoyés).
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Pages : toujours le réseau ; la page hors ligne seulement en cas d'échec.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );
        return;
    }

    // Styles, scripts et icônes : depuis le cache s'ils y sont, sinon réseau puis cache.
    if (isImmutableAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            })),
        );
    }

    // Tout le reste (images des plats, etc.) : comportement normal du navigateur.
});
