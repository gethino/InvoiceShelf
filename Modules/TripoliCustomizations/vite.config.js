import { resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

const moduleDirectory = fileURLToPath(new URL('.', import.meta.url))

export default defineConfig({
  publicDir: false,
  resolve: {
    alias: {
      '@': resolve(moduleDirectory, '../../resources'),
    },
  },
  plugins: [vue()],
  build: {
    emptyOutDir: true,
    outDir: resolve(moduleDirectory, 'dist'),
    lib: {
      entry: resolve(moduleDirectory, 'Resources/scripts/module.js'),
      name: 'TripoliCustomizationsModule',
      formats: ['iife'],
      fileName: () => 'tripoli-customizations.iife.js',
    },
    rollupOptions: {
      external: ['vue'],
      output: {
        globals: {
          vue: 'Vue',
        },
      },
    },
  },
})
