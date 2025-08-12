/**
 * Service Worker 快取策略
 * 
 * 為管理後台提供離線支援和資源快取
 */

const CACHE_NAME = 'admin-layout-v1.0.0';
const STATIC_CACHE = 'admin-static-v1.0.0';
const DYNAMIC_CACHE = 'admin-dynamic-v1.0.0';
const API_CACHE = 'admin-api-v1.0.0';

// 需要快取的靜態資源
const STATIC_ASSETS = [
    '/',
    '/admin/dashboard',
    '/build/assets/app.css',
    '/build/assets/app.js',
    '/build/assets/admin-core.css',
    '/build/assets/admin-core.js',
    '/build/assets/vendor.js',
    '/images/logo.svg',
    '/images/avatar-placeholder.png',
    '/fonts/inter-var.woff2',
];

// API 路由快取配置
const API_CACHE_ROUTES = [
    '/admin/api/navigation',
    '/admin/api/user/profile',
    '/admin/api/notifications/unread-count',
    '/admin/api/dashboard/stats',
];

// 快取策略配置
const CACHE_STRATEGIES = {
    // 靜態資源：快取優先
    static: {
        cacheName: STATIC_CACHE,
        strategy: 'cacheFirst',
        maxAge: 30 * 24 * 60 * 60 * 1000, // 30 天
    },
    // 動態內容：網路優先
    dynamic: {
        cacheName: DYNAMIC_CACHE,
        strategy: 'networkFirst',
        maxAge: 24 * 60 * 60 * 1000, // 1 天
    },
    // API 資料：網路優先，快取備用
    api: {
        cacheName: API_CACHE,
        strategy: 'networkFirst',
        maxAge: 5 * 60 * 1000, // 5 分鐘
    },
};

/**
 * Service Worker 安裝事件
 */
self.addEventListener('install', (event) => {
    console.log('Service Worker 安裝中...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('快取靜態資源...');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('靜態資源快取完成');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('靜態資源快取失敗:', error);
            })
    );
});

/**
 * Service Worker 啟動事件
 */
self.addEventListener('activate', (event) => {
    console.log('Service Worker 啟動中...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        // 清除舊版本快取
                        if (cacheName !== STATIC_CACHE && 
                            cacheName !== DYNAMIC_CACHE && 
                            cacheName !== API_CACHE) {
                            console.log('清除舊快取:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker 啟動完成');
                return self.clients.claim();
            })
    );
});

/**
 * 網路請求攔截
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // 只處理同源請求
    if (url.origin !== location.origin) {
        return;
    }
    
    // 根據請求類型選擇快取策略
    if (isStaticAsset(request)) {
        event.respondWith(handleStaticAsset(request));
    } else if (isApiRequest(request)) {
        event.respondWith(handleApiRequest(request));
    } else if (isNavigationRequest(request)) {
        event.respondWith(handleNavigationRequest(request));
    } else {
        event.respondWith(handleDynamicRequest(request));
    }
});

/**
 * 判斷是否為靜態資源
 */
function isStaticAsset(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/build/') ||
           url.pathname.startsWith('/images/') ||
           url.pathname.startsWith('/fonts/') ||
           url.pathname.endsWith('.css') ||
           url.pathname.endsWith('.js') ||
           url.pathname.endsWith('.woff2') ||
           url.pathname.endsWith('.svg') ||
           url.pathname.endsWith('.png') ||
           url.pathname.endsWith('.jpg');
}

/**
 * 判斷是否為 API 請求
 */
function isApiRequest(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/admin/api/') ||
           url.pathname.startsWith('/livewire/');
}

/**
 * 判斷是否為導航請求
 */
function isNavigationRequest(request) {
    return request.mode === 'navigate';
}

/**
 * 處理靜態資源請求（快取優先策略）
 */
async function handleStaticAsset(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('靜態資源載入失敗:', error);
        return new Response('資源載入失敗', { status: 503 });
    }
}

/**
 * 處理 API 請求（網路優先策略）
 */
async function handleApiRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok && request.method === 'GET') {
            const cache = await caches.open(API_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('API 網路請求失敗，嘗試使用快取:', error);
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return new Response(JSON.stringify({
            error: 'Network unavailable',
            message: '網路連線不可用，請稍後再試'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

/**
 * 處理導航請求
 */
async function handleNavigationRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('導航請求失敗，嘗試使用快取:', error);
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // 返回離線頁面
        return caches.match('/admin/offline') || 
               new Response('頁面暫時無法使用', { status: 503 });
    }
}

/**
 * 處理動態請求
 */
async function handleDynamicRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return new Response('內容暫時無法載入', { status: 503 });
    }
}

/**
 * 清理過期快取
 */
async function cleanupExpiredCache() {
    const cacheNames = await caches.keys();
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        
        for (const request of requests) {
            const response = await cache.match(request);
            const dateHeader = response.headers.get('date');
            
            if (dateHeader) {
                const cacheDate = new Date(dateHeader);
                const now = new Date();
                const maxAge = getMaxAgeForCache(cacheName);
                
                if (now - cacheDate > maxAge) {
                    await cache.delete(request);
                    console.log('清除過期快取:', request.url);
                }
            }
        }
    }
}

/**
 * 取得快取的最大存活時間
 */
function getMaxAgeForCache(cacheName) {
    if (cacheName === STATIC_CACHE) {
        return CACHE_STRATEGIES.static.maxAge;
    } else if (cacheName === API_CACHE) {
        return CACHE_STRATEGIES.api.maxAge;
    } else {
        return CACHE_STRATEGIES.dynamic.maxAge;
    }
}

/**
 * 定期清理快取
 */
setInterval(cleanupExpiredCache, 60 * 60 * 1000); // 每小時清理一次

/**
 * 處理訊息事件
 */
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data && event.data.type === 'CACHE_STATS') {
        getCacheStats().then(stats => {
            event.ports[0].postMessage(stats);
        });
    } else if (event.data && event.data.type === 'CLEAR_CACHE') {
        clearAllCaches().then(() => {
            event.ports[0].postMessage({ success: true });
        });
    }
});

/**
 * 取得快取統計資訊
 */
async function getCacheStats() {
    const cacheNames = await caches.keys();
    const stats = {};
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();
        stats[cacheName] = keys.length;
    }
    
    return stats;
}

/**
 * 清除所有快取
 */
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(name => caches.delete(name)));
    console.log('所有快取已清除');
}