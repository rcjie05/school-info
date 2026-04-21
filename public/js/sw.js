// SCC School Management - Service Worker
// Handles caching, offline support, and push notifications

const CACHE_NAME = 'scc-school-mgmt-v1';
const STATIC_ASSETS = [
  '/login.html',
  '/css/style.css',
  '/css/themes.css',
  '/js/theme-switcher.js',
  '/images/logo.png',
  '/images/logo2.jpg',
  '/manifest.json',
  // HR Pages
  '/hr/dashboard.php',
  '/hr/employees.php',
  '/hr/leaves.php',
  '/hr/attendance.php',
  '/hr/id_cards.php',
  '/hr/announcements.php',
  '/hr/floorplan.php',
  '/hr/profile.php',
  // Admin Pages
  '/admin/dashboard.php',
  '/admin/users.php',
  '/admin/departments.php',
  '/admin/courses.php',
  '/admin/faculty.php',
  '/admin/grades.php',
  '/admin/subjects.php',
  '/admin/sections.php',
  '/admin/announcements.php',
  '/admin/audit_logs.php',
  '/admin/buildings.php',
  '/admin/settings.php',
  '/admin/account_settings.php',
  '/admin/feedback.php',
  '/admin/recycle_bin.php',
];

// ─── Install: cache static assets ──────────────────────────────────────────
self.addEventListener('install', event => {
  console.log('[SW] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[SW] Caching static assets');
      // Cache individually so one failure doesn't block the rest
      return Promise.allSettled(
        STATIC_ASSETS.map(url => cache.add(url).catch(err => console.warn('[SW] Failed to cache:', url, err)))
      );
    }).then(() => self.skipWaiting())
  );
});

// ─── Activate: clean up old caches ─────────────────────────────────────────
self.addEventListener('activate', event => {
  console.log('[SW] Activating...');
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => {
          console.log('[SW] Deleting old cache:', key);
          return caches.delete(key);
        })
      )
    ).then(() => self.clients.claim())
  );
});

// ─── Fetch: Network-first for API, Cache-first for static ──────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET and cross-origin requests
  if (event.request.method !== 'GET') return;
  if (url.origin !== location.origin) return;

  // API calls: network-first, no caching
  if (url.pathname.startsWith('/php/api/')) {
    event.respondWith(
      fetch(event.request).catch(() =>
        new Response(JSON.stringify({ success: false, error: 'You are offline. Please check your connection.' }),
          { headers: { 'Content-Type': 'application/json' } })
      )
    );
    return;
  }

  // PHP pages: network-first, fallback to cache
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => caches.match(event.request).then(cached => cached || offlinePage()))
    );
    return;
  }

  // Static assets: cache-first
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(response => {
        if (response.ok) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      });
    })
  );
});

// ─── Push Notifications ─────────────────────────────────────────────────────
self.addEventListener('push', event => {
  let data = { title: 'SCC School Portal', body: 'You have a new notification.', icon: '/images/logo.png', badge: '/images/logo.png' };

  if (event.data) {
    try { Object.assign(data, event.data.json()); } catch (e) { data.body = event.data.text(); }
  }

  const options = {
    body: data.body,
    icon: data.icon || '/images/logo.png',
    badge: data.badge || '/images/logo.png',
    vibrate: [200, 100, 200],
    data: { url: data.url || '/' },
    actions: data.actions || [],
    requireInteraction: data.requireInteraction || false,
    tag: data.tag || 'scc-notification',
  };

  event.waitUntil(self.registration.showNotification(data.title, options));
});

// ─── Notification Click ─────────────────────────────────────────────────────
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if (client.url === targetUrl && 'focus' in client) return client.focus();
      }
      if (clients.openWindow) return clients.openWindow(targetUrl);
    })
  );
});

// ─── Background Sync (for future use) ──────────────────────────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'sync-leave-requests') {
    console.log('[SW] Background sync: leave requests');
  }
});

// ─── Helpers ────────────────────────────────────────────────────────────────
function offlinePage() {
  return new Response(`
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Offline — SCC Portal</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #EEF2F7; display: flex; align-items: center; justify-content: center;
               min-height: 100vh; padding: 2rem; text-align: center; color: #1C2C42; }
        .card { background: white; border-radius: 16px; padding: 3rem 2rem; max-width: 380px;
                box-shadow: 0 8px 32px rgba(30,51,82,0.13); }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { color: #5A6B80; margin-bottom: 1.5rem; line-height: 1.6; }
        button { background: #1E3352; color: white; border: none; padding: 0.875rem 2rem;
                 border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer;
                 width: 100%; transition: background 0.2s; }
        button:hover { background: #2A4468; }
        .logo { font-size: 0.85rem; color: #8FA3B8; margin-top: 2rem; font-weight: 600; }
      </style>
    </head>
    <body>
      <div class="card">
        <div class="icon">📡</div>
        <h1>You're Offline</h1>
        <p>It looks like you lost your internet connection. Please check your network and try again.</p>
        <button onclick="location.reload()">🔄 Try Again</button>
        <div class="logo">Saint Cecilia College Portal</div>
      </div>
    </body>
    </html>
  `, { headers: { 'Content-Type': 'text/html' } });
}
