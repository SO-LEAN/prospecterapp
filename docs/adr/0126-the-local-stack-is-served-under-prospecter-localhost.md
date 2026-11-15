# 0126. The local stack is served under prospecter.localhost, nothing in /etc/hosts

- Date: 2026-11-15
- Type: architecture
- Status: Accepted
- Supersedes: for the hosts `local.prospecter.io`, `api.local.prospecter.io` and `static.local.prospecter.io` and the name constraint of the local authority, [0123](0123-a-local-authority-signs-the-certificate-of-every-local-stack.md)
- Implemented by: `docker-compose.yml` (`NGINX_HOST`, `API_HOST`), `docker-compose.agents.yml`, the `.env` of the api, the storage and the front, `apps/front/vite.config.ts`, `docker/lambda/deploy.sh` (CORS), `docker/nginx/local-ca.sh`, `apps/front/Makefile` (`e2e`), `apps/front/playwright.config.ts`, `apps/front/e2e/stack.ts`, `apps/api/tests/Local/CorsTest.php`, `README.md`

## Context

The local stack was served under `local.prospecter.io` and its subdomains, which `/etc/hosts` pointed to 127.0.0.1. Their names are those of a public domain: an agent walking its QA journey in Chrome, allowed to type the credentials of the demonstration members only on a host local by its name, stopped at the sign-in and asked the user to sign in, or went on, depending on the run. The browsers resolve the names in `.localhost` to the loopback themselves, without `/etc/hosts`.

## Decision

- The stack is served under `prospecter.localhost` (the SPA), `api.prospecter.localhost` (the API, its doc, the Cognito of MiniStack under `/cognito/`) and `static.prospecter.localhost` (the files), on 443 for the main checkout and on its own port for a worktree (0116). The former names are not kept beside them: one configuration.
- The local authority (0123) may sign only for `prospecter.localhost` and its subdomains. `make ca` replaces an authority whose constraint does not permit that domain, which could not sign for it, and keeps it otherwise; the new one is imported in the browser in place of the former.
- The journeys, in the network of the stack (0084), resolve the names to its nginx: Node by the `/etc/hosts` of the container of Playwright, Chromium, which resolves a name in `.localhost` to its own loopback whatever that file says, by a rule of its resolver (`--host-resolver-rules`) given the address of nginx.

## Consequences

- Nothing to add to `/etc/hosts`; the hosts read as local by their name, so an agent signs in on them alone.
- Every machine imports a new authority once, after `make ca`: the former one cannot sign for the new names.
- A checkout still on the former names, its branch older than this change, serves a certificate for names it does not answer: the journeys accept it (`ignoreHTTPSErrors`), the browser warns.

## Alternatives considered

- An exception letting the agent type the credentials on `local.prospecter.io`: the user chose the names in `.localhost`, simpler, also sparing `/etc/hosts`.
- Keeping the former names beside the new ones: refused by the user, for a single configuration.

## Revisit when

A browser or a tool of the stack stops resolving the names in `.localhost` to the loopback.
