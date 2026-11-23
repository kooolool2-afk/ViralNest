// Vite builds frontend/src into public/assets/dist with stable file names, so PHP can link them
// without a manifest. The output is committed: the server never runs Node.js.
import { defineConfig } from 'vite';

export default defineConfig({
  base: '/assets/dist/',
  publicDir: false,
  build: {
    outDir: '../public/assets/dist',
    emptyOutDir: true,
    target: 'es2019',
    cssCodeSplit: true,
    modulePreload: false,
    sourcemap: false,
    rollupOptions: {
      input: {
        main: 'src/js/main.js',
        admin: 'src/admin/admin.js',
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
});
