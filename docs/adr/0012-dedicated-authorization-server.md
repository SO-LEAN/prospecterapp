# 0012. Delegate authentication to a dedicated authorization server

- Date: 2026-10-15
- Status: Proposed
- Implemented by: not yet; prepared by 0011

## Context

The API is both the resource server and the authorization server (0010), and keeps the credentials (0011). The intent is to authenticate users elsewhere.

## Decision

Authentication will move to a dedicated authorization server, outside the API; the API will only validate its tokens. The `Auth` module is kept isolated and depends on the core only through the user id and user name, so that it can be extracted.

## Consequences

- To decide when implementing: which server, how users are provisioned and linked to the core `User` (id, user name, roles), who owns the user name, migration of existing credentials, the SPA's OAuth settings.
- The sign-in page, `src/Auth` and the authorization part of the OAuth bundle leave the API.

## Alternatives considered

None were weighed yet: which server to use is still to be decided.

## Revisit when

Before implementing it: turn this record into an accepted one with the chosen server.
