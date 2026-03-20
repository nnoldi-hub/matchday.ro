/**
 * MatchDay.ro Service Worker
 * Provides offline support and caching for PWA
 */

const CACHE_NAME = 'matchday-v1';
const OFFLINE_URL = '/offline.html';

// Files to cache immediately on install
const PRECACHE_ASSETS = [
    '/',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/css/bootstrap.min.css',
    '/assets/js/main.js',
    '/assets/js/bootstrap.bundle.min.js',
    '/manifest.json'
];

// Install event - precache essential assets
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Install');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Pre-caching offline assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                // Force the waiting service worker to become active
                return self.skipWaiting();
            })
    );
});

// Activate event - cleanup old caches
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => {
                        console.log('[ServiceWorker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        }).then(() => {
            // Take control of all pages immediately
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip admin and API requests (always fetch fresh)
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/admin/') || 
        url.pathname.includes('_api') || 
        url.pathname.includes('api.php')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                if (cachedResponse) {
                    // Return cached version and update cache in background
                    event.waitUntil(updateCache(event.request));
                    return cachedResponse;
                }

                // Not in cache, fetch from network
                return fetchAndCache(event.request);
            })
            .catch(() => {
                // Network failed, try to serve offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }
            })
    );
});

// Fetch and add to cache
async function fetchAndCache(request) {
    try {
        const response = await fetch(request);
        
        // Only cache successful responses
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            // Clone the response because it can only be consumed once
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.log('[ServiceWorker] Fetch failed:', error);
        throw error;
    }
}

// Update cache in background (stale-while-revalidate pattern)
async function updateCache(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response);
        }
    } catch (error) {
        // Silently fail - user still has cached version
    }
}

// Listen for messages from the app
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.delete(CACHE_NAME).then(() => {
            console.log('[ServiceWorker] Cache cleared');
        });
    }
});

// Background sync for offline form submissions (future use)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-comments') {
        console.log('[ServiceWorker] Syncing comments...');
        // Future: sync offline comments
    }
});

// Push notifications (future use)
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body || 'Știri noi pe MatchDay.ro',
            icon: '/assets/images/icons/icon-192x192.png',
            badge: '/assets/images/icons/icon-72x72.png',
            vibrate: [100, 50, 100],
            data: {
                url: data.url || '/'
            },
            actions: [
                { action: 'open', title: 'Citește' },
                { action: 'close', title: 'Închide' }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(data.title || 'MatchDay.ro', options)
        );
    }
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'open' || !event.action) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url || '/')
        );
    }
});
