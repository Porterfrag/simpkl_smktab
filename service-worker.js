// service-worker.js
const CACHE_NAME = 'pkl-app-v1';
const urlsToCache = [
  // Kita hanya cache aset statis agar aplikasi terasa cepat
  // Jangan cache file .php agar data selalu fresh
  'assets/css/style.css',
  'assets/images/logo-smk.png',
  'assets/images/icon-192.png',
  'assets/images/icon-512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  // Strategi: Network First (Coba ambil data terbaru dulu, kalau offline baru cek cache)
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});