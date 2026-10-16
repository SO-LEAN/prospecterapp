# 0057. Continuous integration around `dev`: branches of work by pull request, `main` and `dev` protected

- Date: 2026-10-16
- Status: Accepted
- Supersedes: [0003](0003-single-linear-main-branch.md) (the history stays linear)
- Implemented by: `.github/workflows/ci.yml`, `make ci` / `make contract` / `make front-build`, the ruleset `protected` of the repository on GitHub

## Context

One linear `main` took every short-lived branch by fast-forward (0003), the tools run by hand before. The user wants continuous integration around a branch `dev`, `main` and `dev` protected; delivery (CD) comes later.

## Decision

- `dev` integrates the work: `feature/*` and `fix/*` start from `dev` and come back by pull request. `dev` goes to `main` by pull request.
- A `hotfix/*` starts from `main` and comes back by pull request; it is then cherry-picked onto `dev` (a `fix/*` branch and its pull request).
- A pull request is merged by rebase only: the history stays linear, without merge commits; the merged branch is deleted.
- GitHub Actions runs `make ci` (build of the images, dependencies, lint, tests of the core, the API and the storage on MiniStack, the front, the OpenAPI contract and the types of the front, the build of the front for production) on every push of `main`, `dev`, `feature/**`, `fix/**`, `hotfix/**` and on the pull requests to `dev` and `main`, in Docker as locally. The journeys of Playwright stay local.
- The ruleset `protected` covers `main` and `dev` without any bypass, administrators included: no direct push (pull request, no approval, the developer being alone), the check `ci` passing on an up-to-date branch, a linear history, no force-push, no deletion.

## Consequences

- Nothing reaches `dev` or `main` without the integration passing; `make ci` runs the same locally before a push.
- A hotfix is applied twice (on `main`, then cherry-picked onto `dev`).
- The e2e journeys are not part of the integration: a change of a journey is checked locally with `make e2e`.

## Alternatives considered

- Squash merging: one commit per feature, the detail of its commits lost.
- Merge commits: a history no longer linear.
- The e2e journeys in the integration: left out by the user.
