import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: 'public',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                'tinyfinder': resolve(__dirname, 'resources/js/tinyfinder.js'),
                'tinyfinder-styles': resolve(__dirname, 'resources/css/tinyfinder.css'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name].css';
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        },
        sourcemap: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
            format: {
                comments: false
            }
        }
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources'),
        }
    }
});
