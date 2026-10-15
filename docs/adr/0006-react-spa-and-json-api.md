# 0006. A React SPA and a JSON API replace the server-rendered application

- Date: 2026-10-14
- Status: Accepted
- Implemented by: aa0ba05, 20f66d6, 308c797, c8bcf16

## Context

After the upgrade (0004), the application was still Twig pages with Bootstrap 4, KnpMenu, Symfony forms and AssetMapper. The goal was a modern interface, and the use cases had to be reachable by other clients than these pages.

## Decision

- `apps/api` (the former `webapp/`): a JSON API exposing the use cases, plus the OAuth 2 server (0010). The only server-rendered page left is the sign-in page.
- `apps/front`: a React single page application (0013).
- Everything specific to server rendering is removed: Twig pages, KnpMenu, AssetMapper, Sass, Bootstrap, page forms and their handlers.

## Consequences

- The API is the single entry point to the use cases, for the SPA and any future client.
- Two applications to build and deploy; the contract between them must be kept in sync (0008).
- Authentication becomes token-based (0010).

## Alternatives considered

- Keep Twig and modernize the pages (Symfony UX, Turbo, Stimulus): less infrastructure, but the use cases stay tied to one delivery.
- Server components frameworks (Next.js): a second backend next to Symfony.

## Revisit when

The SPA's complexity is not paying for itself (mostly static pages, SEO needs).
