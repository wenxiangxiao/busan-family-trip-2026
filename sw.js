/**
 * Busan Trip Service Worker
 * 三層快取策略：
 *  - app shell（HTML/CSS/JS/font/leaflet）→ Cache First
 *  - API（/api/*）→ Network First, fallback cache
 *  - uploads/ 照片 + tile.openstreetmap.org → Cache First
 *
 * 升版時改 CACHE_VERSION，舊 cache 自動清除。
 */
const CACHE_VERSION = 'busan-v4';
const SHELL_CACHE   = `${CACHE_VERSION}-shell`;
const API_CACHE     = `${CACHE_VERSION}-api`;
const ASSET_CACHE   = `${CACHE_VERSION}-assets`;

const SHELL_URLS = [
  '/',
  '/index.html',
  '/manifest.json',
  // Tailwind / FA / Leaflet 從 CDN，runtime 第一次抓到後快取
];

self.addEventListener('install', e => {
  self.skipWaiting();
  e.waitUntil(
    caches.open(SHELL_CACHE).then(c => c.addAll(SHELL_URLS).catch(() => {}))
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => !k.startsWith(CACHE_VERSION)).map(k => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // API：Network first
  if (url.pathname.startsWith('/api/')) {
    e.respondWith(networkFirst(req, API_CACHE));
    return;
  }

  // uploads / leaflet tiles / 字體 / FA / tailwind：Cache first
  const isAsset =
    url.pathname.startsWith('/uploads/') ||
    url.hostname.includes('tile.openstreetmap.org') ||
    url.hostname.includes('fonts.gstatic.com') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('cdnjs.cloudflare.com') ||
    url.hostname.includes('cdn.tailwindcss.com') ||
    url.hostname.includes('unpkg.com');
  if (isAsset) {
    e.respondWith(cacheFirst(req, ASSET_CACHE));
    return;
  }

  // App shell（同源頁面）：Network first，fallback cache
  if (url.origin === location.origin) {
    e.respondWith(networkFirst(req, SHELL_CACHE));
    return;
  }
});

async function cacheFirst(req, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(req);
  if (cached) return cached;
  try {
    const resp = await fetch(req);
    if (resp && resp.status === 200) cache.put(req, resp.clone());
    return resp;
  } catch (e) {
    return cached || Response.error();
  }
}

async function networkFirst(req, cacheName) {
  const cache = await caches.open(cacheName);
  try {
    const resp = await fetch(req);
    if (resp && resp.status === 200) cache.put(req, resp.clone());
    return resp;
  } catch (e) {
    const cached = await cache.match(req);
    if (cached) return cached;
    // 最後一招：對於 navigate 失敗，回傳離線頁
    if (req.mode === 'navigate') {
      const shell = await caches.open(SHELL_CACHE);
      const fallback = await shell.match('/index.html') || await shell.match('/');
      if (fallback) return fallback;
    }
    return Response.error();
  }
}

// 給前端訊息
self.addEventListener('message', e => {
  if (!e.data) return;
  if (e.data.type === 'CLEAR_CACHE') {
    caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k))));
  } else if (e.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
