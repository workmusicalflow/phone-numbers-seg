#!/bin/bash

# Stop any running frontend server
echo "Stopping any running frontend server..."
pkill -f "vite"

# Navigate to the frontend directory
cd frontend

# Add CORS headers to vite.config.ts
echo "Updating vite.config.ts with CORS configuration..."
cat > vite.config.ts << 'EOL'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue({
      template: { transformAssetUrls }
    }),
    quasar({
      sassVariables: 'src/quasar-variables.sass'
    })
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 3000,
    cors: {
      origin: '*',
      methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
      allowedHeaders: ['Content-Type', 'Authorization'],
      credentials: true
    },
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      },
      '/graphql.php': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      }
    }
  }
})
EOL

# Install dependencies if needed
echo "Installing dependencies..."
npm install

# Start the frontend server
echo "Starting frontend server..."
npm run dev
