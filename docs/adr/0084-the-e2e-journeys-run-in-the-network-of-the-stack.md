# 0084. The e2e journeys run in the network of the stack

- Date: 2026-10-26
- Status: Accepted
- Implemented by: `apps/front/Makefile` (target `e2e`)
- Supersedes: [0015](0015-local-stack-in-docker.md), Playwright on the network of the host

## Context

Playwright ran in its official image on the network of the host (ADR 0015), the three local hosts resolved to 127.0.0.1, where nginx listens. Its Chromium then sees the interfaces of the host: when MiniStack starts the container of a function (ADR 0055), its interface gets an IPv6 address about 2 s later, and Chromium drops the requests in flight with `net::ERR_NETWORK_CHANGED`. Right after a switch of the stack, the global setup of the journeys starts the containers of the functions itself, and the first journey failed, 3 times out of 3 on a cold stack, measured by a spike.

## Decision

`make e2e` runs Playwright in the network of the stack (`${COMPOSE_PROJECT_NAME:-prospecterapp}_default`, the one MiniStack starts the functions in), the three local hosts resolved to the address of `pro-nginx` in that network, read by `docker inspect`; it stops with a message when nginx is not there.

## Consequences

- The containers of the functions starting on the host no longer change the network Chromium sees: the first journey after a switch of the stack passes like the others.
- The journeys reach the stack only once it is up: without nginx, `make e2e` fails at once instead of waiting in the global setup.

## Alternatives considered

None were weighed: retries or pauses in Playwright were ruled out by the ticket, they would hide the cause.

## Revisit when

Playwright runs elsewhere than next to the stack (in the CI, against an instance on AWS).
