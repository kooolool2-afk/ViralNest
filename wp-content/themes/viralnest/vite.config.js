// Vite builds src/ into assets/dist/ with stable file names so PHP can enqueue them
// without a manifest. Output is committed: production hosting never runs Node.js.
import { defineConfig } from 'vite';

export default defineConfig({
  base: './',
  publicDir: false,
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    target: 'es2019',
    cssCodeSplit: true,
    modulePreload: false,
    sourcemap: false,
    rollupOptions: {
      input: {
        main: 'src/js/main.js',
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
});
