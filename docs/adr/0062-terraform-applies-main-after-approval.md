# 0062. Terraform applies each push to `main` to the instances of the prod account, after an approval

- Date: 2026-10-17
- Status: Accepted
- Implemented by: `.github/workflows/terraform-apply-prod.yml`, `docs/infra.md`

## Context

`dev` reaches the instances of the dev account by itself (0061). `main` receives `dev` and the hotfixes by merge commits (0058) and is what runs in production; an apply there touches the data of the members, and a mistake costs more than a delay.

## Decision

- A workflow applies each push to `main` touching the infrastructure: each changed app on each instance of `vars.INFRA_MAIN_INSTANCES` (by default `prod`), as the apply of `dev` does, with the same action, the same concurrency groups and the same masking.
- Its jobs run in the GitHub environment `production`: a reviewer approves them, and only the branch `main` deploys to it. The role that applies in the prod account trusts that environment only, not the branch: no job escapes the approval.
- The role ARN is a secret of the environment, out of reach of the jobs that did not pass the approval.

## Consequences

- A merge into `main` waits for the approval before changing production; the plan of each app was shown on the pull request to `dev` and on the one to `main`.
- A hotfix merged into `main` is applied the same way.

## Alternatives considered

None: the environment and its approval were prepared with the accounts, for this use.

## Revisit when

Another reviewer joins, or production needs a deployment window.
