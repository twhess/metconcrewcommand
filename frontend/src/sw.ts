/// <reference lib="webworker" />

import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { registerRoute, NavigationRoute } from 'workbox-routing'
import { NetworkFirst, StaleWhileRevalidate, CacheFirst, NetworkOnly } from 'workbox-strategies'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'
import { ExpirationPlugin } from 'workbox-expiration'
import { BackgroundSyncPlugin } from 'workbox-background-sync'

declare let self: ServiceWorkerGlobalScope

// Clean up old caches from previous versions
cleanupOutdatedCaches()

// Precache app shell (JS, CSS, HTML) — injected by vite-plugin-pwa at build time
precacheAndRoute(self.__WB_MANIFEST)

// SPA navigation fallback — serve index.html for all non-API navigation requests
const navigationHandler = new NetworkFirst({
  cacheName: 'navigations',
  networkTimeoutSeconds: 3
})
const navigationRoute = new NavigationRoute(navigationHandler, {
  denylist: [/^\/api/]
})
registerRoute(navigationRoute)

// ── Auth: NEVER cache ──
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/auth'),
  new NetworkOnly()
)

// ── QR/Transport scans: NEVER cache (real-time data) ──
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/transport/scan'),
  new NetworkOnly()
)

// ── Inventory scans: NEVER cache ──
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/inventory/scan'),
  new NetworkOnly()
)

// ── User profile: NetworkFirst with fast fallback ──
registerRoute(
  ({ url }) => url.pathname === '/api/me',
  new NetworkFirst({
    cacheName: 'api-user-profile',
    networkTimeoutSeconds: 3,
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
      new ExpirationPlugin({ maxEntries: 1, maxAgeSeconds: 86400 })
    ]
  })
)

// ── Static reference data: CacheFirst (yards, roles, permissions) ──
registerRoute(
  ({ url, request }) => {
    if (request.method !== 'GET') return false
    return ['/api/yards', '/api/roles', '/api/permissions'].includes(url.pathname)
  },
  new CacheFirst({
    cacheName: 'api-reference-data',
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
      new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 604800 }) // 7 days
    ]
  })
)

// ── Schedules & reports: NetworkFirst (prefer fresh, allow offline) ──
registerRoute(
  ({ url, request }) => {
    if (request.method !== 'GET') return false
    return url.pathname.startsWith('/api/schedules') ||
           url.pathname.startsWith('/api/reports')
  },
  new NetworkFirst({
    cacheName: 'api-schedules-reports',
    networkTimeoutSeconds: 5,
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
      new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 43200 }) // 12 hours
    ]
  })
)

// ── All other GET API calls: StaleWhileRevalidate ──
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api') && request.method === 'GET',
  new StaleWhileRevalidate({
    cacheName: 'api-general',
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
      new ExpirationPlugin({ maxEntries: 500, maxAgeSeconds: 86400 }) // 24 hours
    ]
  })
)

// ── Background sync for write operations ──
const bgSyncPlugin = new BackgroundSyncPlugin('metcon-write-queue', {
  maxRetentionTime: 24 * 60 // 24 hours in minutes
})

registerRoute(
  ({ url, request }) =>
    url.pathname.startsWith('/api') &&
    ['POST', 'PUT', 'DELETE', 'PATCH'].includes(request.method),
  new NetworkOnly({
    plugins: [bgSyncPlugin]
  })
)

// Listen for skip waiting message from the app
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
})
