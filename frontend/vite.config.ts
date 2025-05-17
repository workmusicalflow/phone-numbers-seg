import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { quasar, transformAssetUrls } from "@quasar/vite-plugin";
import { resolve } from "path";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue({
      template: { transformAssetUrls },
    }),
    quasar({
      sassVariables: "@/quasar-variables.sass", // Use alias for quasar sass variable path
    }),
  ],
  server: {
    proxy: {
      "/api.php": {
        target: "http://localhost:8000",
        changeOrigin: true,
        // secure: false, // uncomment if your backend is on http
      },
      "/graphql.php": {
        target: "http://localhost:8000",
        changeOrigin: true,
        // secure: false, // uncomment if your backend is on http
      },
    },
  },
  resolve: {
    alias: {
      // Correct the alias name from 'src' to '@'
      '@': resolve(__dirname, "src"), 
    },
  },
  css: {
    preprocessorOptions: {
      sass: {
        additionalData: `@import "@/quasar-variables.sass"\n`, // Use alias for sass variable path
      },
    },
  },
  build: {
    // Enable source maps for production build
    sourcemap: true,
    // Improve chunk size
    chunkSizeWarningLimit: 1000,
    // Optimize chunks
    rollupOptions: {
      output: {
        // Separate vendor chunks
        manualChunks: {
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          'quasar-vendor': ['quasar', '@quasar/extras'],
          'apollo-vendor': ['@apollo/client', '@vue/apollo-composable', 'graphql'],
        },
        // Customize chunk filenames
        chunkFileNames: 'assets/js/[name]-[hash].js',
        entryFileNames: 'assets/js/[name]-[hash].js',
        assetFileNames: 'assets/[ext]/[name]-[hash].[ext]',
      },
    },
    // Minify options
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
  // Optimize dev server
  optimizeDeps: {
    include: [
      'vue',
      'vue-router',
      'pinia',
      'quasar',
      '@quasar/extras',
      '@apollo/client',
      '@vue/apollo-composable',
      'graphql'
    ],
  },
});
