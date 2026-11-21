# front (React SPA)

## What is in force

Read before working on the front: `docs/architecture/front.md` (stack, folders, state, data flow, navigation, API access, and its rules), `docs/architecture/design-system.md` (design rules), `docs/architecture/auth.md` (sign-in on Cognito with SRP).

## Commands

Run them through Docker from the repository root (there is no Node on the host): `docker compose run --rm --no-deps node pnpm <script>`, or the Makefile (`make test-front`, `make lint`, `make fix`, `make e2e`, `make api-doc`). The dev server runs in the `node` service (`make start`), behind https://prospecter.localhost.

Scripts: `dev`, `build`, `preview`, `typecheck` (tsc 7), `lint` / `format` (Biome), `test` / `test:watch` (Vitest), `e2e` (Playwright; use `make e2e`, whose global setup `e2e/wait-for-the-stack.ts` waits for the stack to answer), `api:types`.

Before declaring work done: `pnpm typecheck`, `pnpm lint`, `pnpm test`, and `make e2e` for changes to a main journey. Vitest tests the screens; a journey keeps only what no other test covers (ADR 0122, `docs/architecture/tests.md`).

## Gotchas

- No test reads the real clock (ADR 0150): `src/test/setup.ts` sets every test's date to `THE_MOMENT_OF_THE_TESTS`, `vi.setSystemTime` moves it, and `theMomentOfTheTest()` reads it. Biome refuses `Date.now()` and `new Date()` in a test or a journey (`docs/architecture/tests.md`).
- The configuration (`shared/config/env.ts`) comes from `window.prospecter`, set by `/config.js` (empty locally, `public/config.js`; on AWS, the instance's CloudFront Function answers it, ADR 0066), or else from the build's `VITE_*` variables. A new setting goes in both.

- TypeScript 7 is the native compiler and has no JavaScript compiler API. Tools that need one (openapi-typescript) run with `pnpm dlx` and their own TypeScript 5 (see the `api:types` script). Stryker would rewrite `tsconfig.json` with it in its sandbox: `stryker.config.json` names a `tsconfigFile` that does not exist, so it rewrites none (`make mutation`).
- pnpm 12 ignores the `pnpm` field of `package.json` (settings would go in a `pnpm-workspace.yaml`; none is needed today). The store is set by `npm_config_store_dir` in the node image and lives in the `pnpm-store` Docker volume.
- The sign-in screen (`routes/auth/sign-in.tsx`, ADR 0037, 0052) sits outside the app shell. The `_app` guard sends an anonymous visitor there with `returnTo`, a path within the SPA only (`shared/auth/sign-in-page.ts`), which opens once the visitor signs in.
- The router plugin generates `routeTree.gen.ts` on `vite`/`vite build`: commit it, do not edit it.
- shadcn's generator may write `import { cn } from "cn"`: replace it with `@/shared/lib/utils`. Its sonner wrapper expects `next-themes`; ours uses `useResolvedTheme`. Do not keep the `cn` or `next-themes` packages it installs.
- Certificate: once imported in the browser, the local authority (`make ca`, ADR 0123, `docs/architecture/local-stack.md`, "Trusting the local authority") makes it trust every checkout's stack. Without it, accept the self-signed certificate once per host (SPA, API, static) and per port.
- The e2e journeys run in parallel (ADR 0117, `E2E_WORKERS`, two by default), each file in its own worker. They sign in the same demonstration members and name what they create after the moment they run. A journey that signs a member out (a Cognito global sign-out, which signs out the journeys running beside it), or that reads what the others change in the account (a figure, a count), runs alone after the others: the main journey in the `alone` project of `playwright.config.ts`, then the dashboard in the `alone-dashboard` project (`dashboard-figures.spec.ts`, in the account the console opens for the run, ADR 0122), one after the other (ADR 0136).
- `make e2e` runs Playwright inside the network of the checkout's stack (ADR 0116), with the local hosts resolved to the address of its `nginx`, never on the host network. There, each container a function starts changes the network interfaces, and Chromium drops its requests (`net::ERR_NETWORK_CHANGED`), so the first journey after a stack switch would fail (ADR 0084).
- MapLibre 6 loads its worker from `maplibre-gl-worker.mjs`, next to its own file, which Vite's bundling moves. `map-view.tsx` passes the real URL (`?url` import + `setWorkerUrl`). Without it the map stays grey (markers shown, no tiles, "Worker failed to load" in the console).
- MapLibre's stylesheet comes with the map's lazy chunk, after `app/styles.css`. A rule that restyles its elements (the popups) goes in `shared/ui/map/map-view.css`, imported right after it; otherwise MapLibre wins at equal specificity (a white popup in the dark theme).
