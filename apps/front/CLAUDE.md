# front (React SPA)

## What is in force

Read before working on the front: `docs/architecture/front.md` (stack, folders, state, data flow, navigation, API access, and its rules), `docs/architecture/design-system.md` (design rules), `docs/architecture/auth.md` (sign-in on Cognito with SRP).

## Commands

Run them through Docker from the repository root (there is no Node on the host): `docker compose run --rm --no-deps node pnpm <script>`, or the Makefile (`make test-front`, `make lint`, `make fix`, `make e2e`, `make api-doc`). The dev server runs in the `node` service (`make start`), behind https://prospecter.localhost.

Scripts: `dev`, `build`, `preview`, `typecheck` (tsc 7), `lint` / `format` (Biome), `test` / `test:watch` (Vitest), `e2e` (Playwright; use `make e2e`, whose global setup `e2e/wait-for-the-stack.ts` waits for the stack to answer), `api:types`.

Before declaring work done: `pnpm typecheck`, `pnpm lint`, `pnpm test`, and `make e2e` for changes to a main journey. Vitest tests the screens; a journey keeps only what no other test covers (ADR 0122, `docs/architecture/tests.md`).

## Gotchas

- No test reads the real clock (ADR 0150). `src/test/setup.ts` sets every test's date to `THE_MOMENT_OF_THE_TESTS`. `vi.setSystemTime` moves it, and `theMomentOfTheTest()` reads it. Biome refuses `Date.now()` and `new Date()` in a test or a journey (`docs/architecture/tests.md`).
- The configuration (`shared/config/env.ts`) comes from `window.prospecter`, or else from the build's `VITE_*` variables. `/config.js` sets `window.prospecter`. Locally, that file is empty (`public/config.js`). On AWS, the instance's CloudFront Function answers it (ADR 0066). A new setting goes in both.

- TypeScript 7 is the native compiler and has no JavaScript compiler API. Tools that need one (openapi-typescript) run with `pnpm dlx` and their own TypeScript 5 (see the `api:types` script). In its sandbox, Stryker would rewrite `tsconfig.json` with it (`make mutation`). So `stryker.config.json` names a `tsconfigFile` that does not exist, and Stryker rewrites none.
- pnpm 12 ignores the `pnpm` field of `package.json`. Settings would go in a `pnpm-workspace.yaml`, and none is needed today. The store is set by `npm_config_store_dir` in the node image and lives in the `pnpm-store` Docker volume.
- The sign-in screen (`routes/auth/sign-in.tsx`, ADR 0037, 0052) sits outside the app shell. The `_app` guard sends an anonymous visitor there with `returnTo`. `returnTo` is only ever a path within the SPA (`shared/auth/sign-in-page.ts`). That path opens once the visitor signs in.
- The router plugin generates `routeTree.gen.ts` on `vite`/`vite build`: commit it, do not edit it.
- shadcn's generator may write `import { cn } from "cn"`: replace it with `@/shared/lib/utils`. Its sonner wrapper expects `next-themes`; ours uses `useResolvedTheme`. Do not keep the `cn` or `next-themes` packages it installs.
- Certificate: once the local authority (`make ca`, ADR 0123, `docs/architecture/local-stack.md`, "Trusting the local authority") is imported in the browser, the browser trusts every checkout's stack. Without it, accept the self-signed certificate once per host (SPA, API, static) and per port.
- The e2e journeys run in parallel (ADR 0117), on two workers by default (`E2E_WORKERS`). Each file runs in its own worker. The journeys sign in the same demonstration members. They name what they create after the moment they run. Two kinds of journey run alone, after the others (ADR 0136):
  - a journey that signs a member out, because a Cognito global sign-out also signs out the journeys running beside it;
  - a journey that reads what the others change in the account (a figure, a count).

  The main journey runs first, in the `alone` project of `playwright.config.ts`. Then the dashboard runs in the `alone-dashboard` project (`dashboard-figures.spec.ts`), in the account that the console opens for the run (ADR 0122).
- `make e2e` runs Playwright inside the network of the checkout's stack (ADR 0116), never on the host network. Inside, the local hosts resolve to the address of the stack's `nginx`. On the host network, each container that a function starts changes the network interfaces. Chromium then drops its requests (`net::ERR_NETWORK_CHANGED`), so the first journey after a stack switch would fail (ADR 0084).
- MapLibre 6 loads its worker from `maplibre-gl-worker.mjs`, next to its own file. Vite's bundling moves that file. `map-view.tsx` passes the real URL (`?url` import + `setWorkerUrl`). Without it the map stays grey (markers shown, no tiles, "Worker failed to load" in the console).
- MapLibre's stylesheet comes with the map's lazy chunk, after `app/styles.css`. A rule that restyles its elements (the popups) goes in `shared/ui/map/map-view.css`, which is imported right after it. Otherwise MapLibre wins at equal specificity (a white popup in the dark theme).
