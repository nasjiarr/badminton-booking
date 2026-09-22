import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            manifest: {
                name: 'Smash Arena — Badminton Booking',
                short_name: 'Smash Arena',
                description: 'Aplikasi booking lapangan badminton modern, cepat & real-time.',
                theme_color: '#0A0F1D',
                background_color: '#0A0F1D',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                navigateFallback: null, // Crucial: do not intercept Laravel/Inertia server-driven dynamic routing
                runtimeCaching: [
                    {
                        // Cache Google / Bunny Web Fonts
                        urlPattern: /^https:\/\/fonts\.(?:bunny\.net|googleapis\.com)\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'web-fonts-cache',
                            expiration: {
                                maxEntries: 15,
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        // Cache court images and static media
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'static-media-cache',
                            expiration: {
                                maxEntries: 60,
                                maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
                            },
                        },
                    },
                    {
                        // CRITICAL: Slot availability & API endpoints must ALWAYS be real-time
                        urlPattern: /\/(?:courts\/.*\/availability|api\/.*)/i,
                        handler: 'NetworkOnly',
                    },
                ],
            },
        }),
    ],
});
