# 0036. The client gives the identity and the moment of a command; aggregates keep their creation and last change

- Date: 2026-10-15
- Status: Accepted
- Supersedes: [0024](0024-uuid-v7-identities-given-by-the-repository.md)
- Implemented by: `apps/api/src/Shared/Identities.php`, the `executedAt` of every command, `createdAt()` / `modifiedAt()` of `Organization`, `Account` and `Person`

## Context

A creation asked the repository for `nextIdentity()`, an interface of the domain: the client had to see the domain to create something (0035). Nothing recorded when an aggregate was created or changed.

## Decision

- The client generates the identity of what it creates, a UUID v7 (`App\Shared\Identities` in the API), and puts it in the command; the repositories no longer give identities.
- Every command carries `executedAt`, the moment the client executes it (the clock of Symfony in the API). The intention methods take it (`at:`): a creation sets `createdAt` and `modifiedAt`, every change `modifiedAt`. Queries carry no moment.
- The responses carry both dates; the organization resource of the API shows them.

## Consequences

- Identities and moments are given from outside: the core is deterministic, the scenarios set the time (`nous sommes le ...`).
- The core trusts the client for both: a wrong clock or a reused identity is the client's fault (a reused identity overwrites, the API always generates a new one).
- Other information on where and how an intention was executed can join `executedAt` later.

## Alternatives considered

- An application query giving the next identity from the repository, keeping 0024: the user preferred the client generating it.

## Revisit when

- Another client than the API sends commands, or a workflow needs more than the creation and the last change.
