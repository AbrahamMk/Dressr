/* Dressr service worker — static assets only. Never caches HTML/JSON. */
const CACHE = 'dressr-static-v1';
const PRECACHE = [
  '/manifest.webmanifest',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))),
    ).then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (url.origin !== self.location.origin) {
    return;
  }

  // Never cache authenticated app shells, Inertia HTML, or JSON/API responses.
  const accept = request.headers.get('accept') || '';
  if (
    request.mode === 'navigate' ||
    accept.includes('text/html') ||
    accept.includes('application/json') ||
    url.pathname.startsWith('/outfits/generate') ||
    url.pathname.startsWith('/wear-log')
  ) {
    return;
  }

  const isStatic =
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname === '/manifest.webmanifest' ||
    /\.(?:js|css|png|jpg|jpeg|webp|svg|woff2?)$/i.test(url.pathname);

  if (!isStatic) {
    return;
  }

  event.respondWith(
    caches.open(CACHE).then(async (cache) => {
      const cached = await cache.match(request);
      if (cached) {
        return cached;
      }

      const response = await fetch(request);
      if (response.ok) {
        cache.put(request, response.clone());
      }
      return response;
    }),
  );
});
