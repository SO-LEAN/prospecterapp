# 0058. `main` receives `dev` and the hotfixes by merge commits

- Date: 2026-10-16
- Status: Accepted
- Supersedes: the rebase merges into `main` of [0057](0057-continuous-integration-around-dev.md)
- Implemented by: the rulesets `dev` and `main` of the repository on GitHub, `allow_merge_commit`

## Context

0057 merges every pull request by rebase. On GitHub a rebase merge always writes new commits, even when a fast-forward would do: harmless for a branch of work, deleted once merged, but `main` would receive copies of the commits of `dev`, the two branches diverging at every release, and the next pull request from `dev` replaying commits already applied; a hotfix cherry-picked onto `dev` makes the same.

## Decision

- `main` receives `dev`, and a `hotfix/*`, by a merge commit only (the ruleset `main`: pull request, merge method `merge`, the check `ci`, no force-push, no deletion): `main` is a history of releases, each merge bringing the commits of `dev` as they are.
- `dev` stays linear (the ruleset `dev`: rebase only, linear history), the branches of work rebased onto it.
- A hotfix merged into `main` is still cherry-picked onto `dev`; the next merge of `dev` into `main` takes it as already there.

## Consequences

- The history of `main` is not linear: its first parents are the releases.
- `dev` and `main` never diverge on the same commits: a commit of `dev` keeps its SHA in `main`.

## Alternatives considered

- `main` moved by fast-forward to the tip of `dev`, without pull request: a linear `main`, but a hotfix would have to go through `dev`.
- Rebase merges into `main` (0057): the divergence above.
