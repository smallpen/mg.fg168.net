import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin-layout.css',
                'resources/css/enhanced-admin-layout.css',
                'resources/css/responsive-design.css',
                'resources/css/accessibility.css',
                'resources/css/theme-system.css',
                'resources/css/performance-optimization.css',
                'resources/js/app.js',
                'resources/js/admin-layout.js',
                'resources/js/keyboard-shortcuts.js',
                'resources/js/touch-gestures.js',
                'resources/js/rtl-support.js',
                'resources/js/lazy-loading.js',
                'resources/js/service-worker-manager.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // 核心框架
                    'vendor': ['alpinejs', 'axios']
                }
            }
        },
        // 啟用壓縮
        minify: 'esbuild',
        // CSS 壓縮
        cssMinify: true,
        // 資源內聯閾值
        assetsInlineLimit: 4096,
        // 啟用 gzip 壓縮提示
        reportCompressedSize: true,
    },
    // CSS 預處理器選項
    css: {
        postcss: './postcss.config.js',
    },
});