import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from "@tailwindcss/vite";
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        // host: 'localhost', // Gunakan 0.0.0.0 agar dapat diakses di seluruh jaringan
        // proxy: {
        //     '/api': 'http://localhost:8002', // Ganti dengan URL API backend kamu
        // },
        // cors : true,
        cors: {
            origin: ['http://localhost:8004', 'http://127.0.0.1:5173'], // Daftarkan origins yang benar
            methods: ['GET', 'POST', 'PUT', 'DELETE'],
            allowedHeaders: ['Content-Type', 'Authorization'],
        },
        fs: {
            allow: [
                '.',
                'resources',
                'node_modules',

                // Tambahan biar Vite bisa baca luar folder
                'app/Projects',
                'app/Groups',
                'app/Tenants',
            ],
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        })
    ],
    resolve: {
        alias: {
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
            '@tenants': path.resolve(__dirname, 'app/Tenants'),
            '@groups': path.resolve(__dirname, 'app/Groups'),
            '@projects': path.resolve(__dirname, 'app/Projects'),
            '@klinik': path.resolve(__dirname, 'app/Projects/klinik/src/Resources/js'),
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    optimizeDeps: {
        include: ['jose'], // ini penting untuk mastiin jose tidak gagal dioptimalkan
    }
});
