/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url'
import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import { tanstackRouter } from '@tanstack/router-plugin/vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import { defineConfig, type Rolldown } from 'vite'

const PUBLIC_HOST = 'prospecter.localhost'
/** The port of the host the stack is published on: 443 for the main checkout, its own for a worktree (ADR 0116). */
const PUBLIC_PORT = Number(process.env.PUBLIC_PORT ?? 443)

const typedRoutesSplitByRoute = tanstackRouter({
  target: 'react',
  autoCodeSplitting: true,
  routesDirectory: './src/routes',
  generatedRouteTree: './src/app/routeTree.gen.ts',
})

const reactCompiler = babel({ presets: [reactCompilerPreset()] })

/** A form typed key by key takes seconds when `make ci` runs the other checks beside the tests (ADR 0109). */
const TIMEOUT_OF_A_TEST_SHARING_THE_MACHINE = 20_000

/**
 * One jsdom per worker instead of one per file, which took 30 % of the run. Each file still gets its own window and its
 * own modules, in a VM context of its own (ADR 0207).
 */
const ONE_DOM_PER_WORKER = 'vmThreads'

/**
 * A worker in a VM context frees its memory less reliably: it is started again once it holds half of the container's
 * memory. A worktree's worker reached 1.25 GB of its 2 GB, beside a test that runs TypeScript on a copy of the front.
 */
const MEMORY_A_WORKER_HOLDS_BEFORE_ITS_RESTART = '50%'

/** The chunk of the map, MapLibre itself, loaded with the map only (`geo-map.tsx`): beyond it, a chunk is warned. */
const SIZE_OF_THE_MAP_LOADED_ON_DEMAND_KB = 1_200

/**
 * The modules of the SPA transformed as the dev server starts, the components the routes load on demand among them:
 * the first page a stack serves, after a deployment, renders in a second instead of waiting for each of them.
 */
const MODULES_OF_THE_SPA = [
  './src/main.tsx',
  './src/{app,features,routes,shared}/**/*.tsx',
  '!./src/**/*.test.tsx',
]

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
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    allowedHosts: [PUBLIC_HOST],
    hmr: { host: PUBLIC_HOST, protocol: 'wss', clientPort: PUBLIC_PORT },
    warmup: { clientFiles: MODULES_OF_THE_SPA },
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
    pool: ONE_DOM_PER_WORKER,
    vmMemoryLimit: MEMORY_A_WORKER_HOLDS_BEFORE_ITS_RESTART,
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}', 'lint/**/*.test.ts'],
    css: false,
    testTimeout: TIMEOUT_OF_A_TEST_SHARING_THE_MACHINE,
  },
})
