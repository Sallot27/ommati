/* Service worker: offline pages, cached assets, and push reminders. */
const VERSION = 'v1';
const PAGES = `pages-${VERSION}`;
const ASSETS = `assets-${VERSION}`;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PAGES).then((c) => c.addAll(['/offline', '/', '/adhkar', '/adhkar/morning', '/adhkar/evening', '/tasbih'])).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => ![PAGES, ASSETS].includes(k)).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    // Audio and book files are large; let the browser handle them.
    if (url.hostname.includes('islamic.network') || url.pathname.includes('/file')) return;

    // Built assets and fonts: cache first.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.endsWith('.woff2')) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                if (res.ok || res.type === 'opaque') {
                    const copy = res.clone();
                    caches.open(ASSETS).then((c) => c.put(req, copy));
                }
                return res;
            }))
        );
        return;
    }

    if (url.origin !== location.origin || url.pathname.startsWith('/admin')) return;

    // Quran text API: serve from cache, refresh in background.
    if (url.pathname.startsWith('/api/quran/') || url.pathname.startsWith('/api/tafsir/')) {
        event.respondWith(
            caches.open(ASSETS).then(async (c) => {
                const hit = await c.match(req);
                const network = fetch(req).then((res) => { if (res.ok) c.put(req, res.clone()); return res; }).catch(() => hit);
                return hit || network;
            })
        );
        return;
    }

    // Pages: network first, fall back to the last copy, then the offline page.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).then((res) => {
                if (res.ok) {
                    const copy = res.clone();
                    caches.open(PAGES).then((c) => c.put(req, copy));
                }
                return res;
            }).catch(async () => (await caches.match(req)) || caches.match('/offline'))
        );
    }
});

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch {
        data = { title: 'تذكير', body: event.data?.text() };
    }
    const title = data.title || 'تذكير';
    event.waitUntil(self.registration.showNotification(title, {
        body: data.body,
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/badge-72.png',
        tag: data.tag,
        lang: 'ar',
        dir: 'rtl',
        data: data.data || {},
        renotify: true,
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if ('focus' in client) {
                    client.navigate(target);
                    return client.focus();
                }
            }
            return self.clients.openWindow(target);
        })
    );
});
