/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    VitePWA({
      registerType: 'prompt',
      manifest: {
        name: 'Écolio',
        short_name: 'Écolio',
        lang: 'fr',
        display: 'standalone',
        start_url: '/',
        theme_color: '#0f766e',
        background_color: '#ffffff',
        icons: [],
      },
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['src/**/*.{test,spec}.ts'],
    coverage: { provider: 'v8', include: ['src/**/*.{ts,vue}'] },
  },
})
