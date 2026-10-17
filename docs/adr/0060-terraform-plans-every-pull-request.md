# 0060. Terraform plans every pull request touching the infrastructure, with a read-only role

- Date: 2026-10-17
- Status: Accepted
- Implemented by: `.github/workflows/terraform-plan.yml`, `INFRA_PLAN_FLAGS` of `make infra-plan`, `docs/infra.md`

## Context

The roots of 0059 are about to receive their first resources. A change to `apps/<app>/infra` must show what it does to each instance before it is merged, not after. The repository is public: its pull requests, comments and workflow logs are readable by anyone, and a pull request may change the workflow it runs.

## Decision

- A workflow plans each pull request to `dev` or `main` that touches the infrastructure: each changed app (all of them when `.terraform-version`, the `Makefile` or the workflow change) on each instance (`vars.INFRA_PLAN_INSTANCES`, by default `dev` and `prod`), through `make infra-plan`, as locally.
- It assumes, by GitHub OIDC, a plan role of the account of the instance: read-only, denied the application data (items, objects, users, logs), never the role that applies. The plan runs without the lock (`-lock=false`): plans in parallel are harmless.
- The values of each instance are repository secrets (`INFRA_TFVARS_<INSTANCE>`, `INFRA_TFBACKEND_<INSTANCE>`, `AWS_PLAN_ROLE_ARN_<INSTANCE>`); the account ids and the state bucket are masked in the log. A comment per app and instance lists the changed resources and the summary of the plan, never their values.
- A pull request from a fork gets no plan (GitHub gives it neither secrets nor an OIDC token).

## Consequences

- The plan is informative: it is not a required check, and it says nothing of the order of a first deployment.
- Whoever can push a branch can read the state through the plan role: the secrets of the state are as readable as the configuration of the resources.
- The apply, and its serialization by app and instance, come with the continuous deployment.

## Alternatives considered

- Checking out the private configuration in the workflow: one more credential to keep, and the workflow would name a repository of an installation.
- The full plan in the comment: it would publish the values of the resources (ARNs, account ids).

## Revisit when

The continuous deployment applies the roots, or a pull request needs the plan to block its merge.
