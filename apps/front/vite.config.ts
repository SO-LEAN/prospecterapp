/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url'
import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import { tanstackRouter } from '@tanstack/router-plugin/vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

const PUBLIC_HOST = 'local.prospecter.io'

const typedRoutesSplitByRoute = tanstackRouter({
  target: 'react',
  autoCodeSplitting: true,
  routesDirectory: './src/routes',
  generatedRouteTree: './src/app/routeTree.gen.ts',
})

const reactCompiler = babel({ presets: [reactCompilerPreset()] })

/** amazon-cognito-identity-js reads the Node `global`: the browser names it `globalThis`. */
const nodeGlobalOfTheCognitoLibrary = { global: 'globalThis' }

/** A form typed key by key takes seconds when `make ci` runs the other checks beside the tests (ADR 0109). */
const TIMEOUT_OF_A_TEST_SHARING_THE_MACHINE = 20_000

export default defineConfig({
  plugins: [typedRoutesSplitByRoute, react(), reactCompiler, tailwindcss()],
  define: nodeGlobalOfTheCognitoLibrary,
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    allowedHosts: [PUBLIC_HOST],
    hmr: { host: PUBLIC_HOST, protocol: 'wss', clientPort: 443 },
  },
  build: {
    target: 'es2024',
    sourcemap: true,
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}', 'lint/**/*.test.ts'],
    css: false,
    testTimeout: TIMEOUT_OF_A_TEST_SHARING_THE_MACHINE,
  },
})
