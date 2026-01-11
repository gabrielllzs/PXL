import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import { homedir } from 'os';
import { resolve } from 'path';


const host = 'pxl.test';
const certPath = resolve(homedir(), 'Library/Application Support/Herd/config/valet/Certificates');


export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        if (id.includes('maplibre-gl')) {
                            return 'maplibre';
                        }
                        if (id.includes('vue')) {
                            return 'vendor';
                        }
                        if (id.includes('@fingerprintjs') || id.includes('@solana') || id.includes('axios')) {
                            return 'libs';
                        }
                        return 'vendor';
                    }
                },
            },
        },
        chunkSizeWarningLimit: 1000,
        commonjsOptions: {
            include: [/node_modules/],
        },
    },
    server: {
        host: host,
        https: {
            key: fs.readFileSync(`${certPath}/${host}.key`),
            cert: fs.readFileSync(`${certPath}/${host}.crt`),
        },
        hmr: {
            host: host,
        },
    },
})
