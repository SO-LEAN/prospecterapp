/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url'
import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import { tanstackRouter } from '@tanstack/router-plugin/vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import { defineConfig, type Rolldown } from 'vite'

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

/** The chunk of the map, MapLibre itself, loaded with the map only (`geo-map.tsx`): beyond it, a chunk is warned. */
const SIZE_OF_THE_MAP_LOADED_ON_DEMAND_KB = 1_200

/** Tailwind generates the stylesheet without a sourcemap, by design: the warning of any other plugin is kept. */
const keepAllButTheMissingSourcemapOfTailwind: NonNullable<Rolldown.InputOptions['onLog']> = (
  level,
  log,
  handler,
) => {
  const missingSourcemapOfTailwind =
    log.code === 'SOURCEMAP_BROKEN' && log.plugin?.startsWith('@tailwindcss/vite')
  if (!missingSourcemapOfTailwind) handler(level, log)
}

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
    chunkSizeWarningLimit: SIZE_OF_THE_MAP_LOADED_ON_DEMAND_KB,
    rolldownOptions: {
      checks: { bundlerTimings: false },
      onLog: keepAllButTheMissingSourcemapOfTailwind,
    },
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}', 'lint/**/*.test.ts'],
    css: false,
    testTimeout: TIMEOUT_OF_A_TEST_SHARING_THE_MACHINE,
  },
})
