# 0001. Record architecture decisions

- Date: 2026-10-15
- Status: Accepted
- Implemented by: `docs/adr/`

## Context

In three days the project went from a 2021 server-rendered Symfony 4.4 application to a monorepo with a React SPA, a JSON API, an OAuth 2 server and a tested hexagon. The reasons are scattered across commit messages, `CLAUDE.md` files and `docs/`. The refactoring goes on, and these decisions will be reviewed one by one.

## Decision

Every structuring decision is recorded in `docs/adr`, in Michael Nygard's format plus two sections: the alternatives considered, and when to revisit it. A decision that changes is not edited: a new record supersedes it.

## Consequences

- Reviewing the architecture means reading short records, each with its reasons and its exit signal.
- Writing a record is part of making such a decision, for people and for Claude Code (rule in the root `CLAUDE.md`).
- Records 0002 to 0022 were written afterwards, from the history: their dates are the dates of the decisions, not of the writing.

## Alternatives considered

- Keep the reasons in commit messages and `docs/`: they stay, but they say what was done, not which options were weighed, and they are hard to review as a whole.

## Revisit when

Records are no longer read or kept up to date.
