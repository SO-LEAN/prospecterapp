# 0003. A single linear `main`, short-lived branches, fast-forward only

- Date: 2026-10-13
- Status: Accepted
- Implemented by: history rewritten on 2026-10-13; root `CLAUDE.md`

## Context

The history had `master`, `develop`, `travis-ci` and merge commits, with `develop` containing everything. A single maintainer works on the project, often with Claude Code.

## Decision

- One branch, `main` (renamed from `master`), with a linear history and no merge commits.
- Work happens on a short-lived branch, then `main` is fast-forwarded to it and the branch deleted.
- Conventional commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`) whose message explains why.
- Committing and pushing happen only when asked.

## Consequences

- `git log` reads as a story; each commit can be reviewed alone and reverted.
- A long-running branch would need rebasing before the fast-forward.

## Alternatives considered

- Committing straight to `master` during the migration (`master` could be broken meanwhile) instead of a temporary branch fast-forwarded at the end: the branch was chosen.
- For the CI cleanup: delete the old branches and `.travis.yml`, or the branches only: both were removed.

## Revisit when

Several people contribute in parallel, or releases need maintenance branches.
