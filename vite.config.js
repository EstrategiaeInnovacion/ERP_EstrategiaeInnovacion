import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/Sistemas_IT/inventario-index.css', // Sistemas_IT page styles
                'resources/js/Sistemas_IT/tickets-my.js', // Tickets "mis tickets" page script
                'resources/js/Sistemas_IT/tickets-create.js', // Tickets "create" page script
                // Component scripts ya se importan dentro de app.js (evita entradas redundantes en build)
                // Area: Recursos Humanos
                'resources/css/Recursos_Humanos/index.css',
                'resources/js/Recursos_Humanos/index.js',
                // Area: Logistica
                'resources/css/Logistica/index.css',
                'resources/js/Logistica/index.js'
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('alpinejs') || id.includes('@alpinejs')) {
                            return 'vendor-alpine';
                        }
                        if (id.includes('flatpickr')) {
                            return 'vendor-flatpickr';
                        }
                        if (id.includes('jspdf') || id.includes('html2canvas')) {
                            return 'vendor-pdf';
                        }
                        return 'vendor';
                    }
                }
            }
        }
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
