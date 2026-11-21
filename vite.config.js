    import { defineConfig } from 'vite';
    import laravel from 'laravel-vite-plugin';

    export default defineConfig({
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    // Bundle aparte: solo se carga en Control de Asistencia.
                    'resources/js/scanner-asistencia.js',
                ],
                refresh: true,
            }),
        ],
        server: {
            https: false,
        }
    });
