# 0013. Front stack: React, TanStack, typed client, shadcn/ui

- Date: 2026-10-14
- Status: Accepted
- Implemented by: c8bcf16; `docs/front-architecture.md`, `docs/design-system.md`

## Context

A new SPA (0006), to be built with current practices: application state, API call state, routing, a modern design system without Bootstrap, French interface ready for translation.

## Decision

- React 19 with the React Compiler (no hand-written memoization), Vite, strict TypeScript, pnpm, Node 24.
- TanStack Router (file routes, typed links and search params, code split per route) and TanStack Query (server state cache, loaders, targeted invalidation).
- Each kind of state in its place: server data in the Query cache, search and pagination in the URL, UI preferences in Zustand, forms in react-hook-form + zod, session in oidc-client-ts.
- shadcn/ui (Radix) and Tailwind CSS 4 with semantic tokens, light and dark themes; react-i18next with typed French keys.
- Feature folders (`features/<feature>`, `shared`, thin `routes`); Biome, Vitest, Playwright.
- Navigation: "back" goes back in history, forms are left by going back.

## Consequences

- Fast, typed end to end (with 0008), little boilerplate for server state.
- Many young libraries to follow (TypeScript 7 native compiler, Vite 8): tools needing the TypeScript API run with TypeScript 5.
- shadcn components are project code: adapting them is expected, upgrading them is manual.

## Alternatives considered

- Redux for all state: duplicates the server cache.
- React Router: less typed search params.
- MUI or another component library: heavier and harder to restyle than owned components.

## Revisit when

A framework with server rendering becomes necessary, or a library of the stack stops being maintained.
