# 0084. Playwright runs on the network of the stack, the local hosts resolved to its nginx

- Date: 2026-10-26
- Status: Accepted
- Supersedes: the network of Playwright in [0015](0015-local-stack-in-docker.md)
- Implemented by: `apps/front/Makefile` (`e2e`)

## Context

Playwright ran in its official image on the network of the host (ADR 0015), the three local hosts on 127.0.0.1, where nginx listens. After `make stop` then `make start`, the first journey failed one run out of three or four: still on `/` when the sign-in screen was expected. Its trace showed the modules of the SPA cancelled while loading, `net::ERR_NETWORK_CHANGED`. The global setup of the journeys calls the api and the storage; MiniStack then starts the container of each Lambda, which adds an interface to the host. On the network of the host, Chromium sees that interface appear and cancels what it is loading; when that lands in the first page of a journey, the SPA never starts. A Lambda MiniStack starts later, for requests in parallel, can do the same to any journey.

## Decision

`make e2e` runs Playwright on the Docker network of nginx, the three local hosts resolved to the address of nginx on that network, both read from the container `pro-nginx` when the journeys start. The network namespace of Playwright then has only its own interface: the interfaces of the Lambdas no longer reach Chromium.

## Consequences

- A failed journey no longer comes from a Lambda starting: the journeys pass on a stack just started, the first one included.
- `make e2e` needs the container `pro-nginx` running and says so otherwise; its name is the one of `docker-compose.yml`.
- The journeys reach the internet through the NAT of Docker, as the other containers.

## Alternatives considered

- Opening the SPA once in a browser in the global setup, the lead of the ticket (a dev server still preparing its modules after a restart): tried, the first journey still failed, the SPA reaching the sign-in screen in half a second in the setup.
- Sharing the network namespace of nginx (`--network container:pro-nginx`), where 127.0.0.1 is nginx: Docker refuses `--add-host` in that mode, and the global setup needs the three hosts resolved.

## Revisit when

The journeys run against an instance on AWS, or nginx publishes on another address than 127.0.0.1.
