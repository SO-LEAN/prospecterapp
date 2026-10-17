# 0061. Terraform applies each merge into `dev` to the instances of the dev account

- Date: 2026-10-17
- Status: Accepted
- Implemented by: `.github/workflows/terraform-apply-dev.yml`, `.github/actions/infra-instance`, `tools/ci/infra-changed-apps`, `INFRA_APPLY_FLAGS` of `make infra-apply`, `docs/infra.md`

## Context

Pull requests are planned (0060); what is merged into `dev` must reach the instances of the dev account without a manual step, so that `dev` is always what runs there. A merge into `dev` has passed the CI and its plan, and only a pull request can change `dev` (0057).

## Decision

- A workflow applies each push to `dev` touching the infrastructure: each changed app on each instance of `vars.INFRA_DEV_INSTANCES` (by default `dev`), through `make infra-apply` with `-auto-approve`, as locally.
- It assumes by OIDC the role that applies in the account of the instance, which trusts the branch `dev` only; the plan role is never used to apply, nor this role to plan a pull request.
- Two applies of the same app and instance wait for each other in a concurrency group, never cancelled; the S3 lock remains the safety net. Each apply makes its own plan on the state of the moment, never the one of the pull request.
- The values and the masking are those of the plan, shared by an action of the repository.

## Consequences

- A merge into `dev` changes the instance `dev` within minutes; a failed apply leaves the instance as far as it got, and is fixed by a new pull request.
- The apps are applied in parallel: an app that reads in SSM what another publishes needs its first deployment ordered by hand.
- The instance `prod` is not applied by this workflow.

## Alternatives considered

- Applying the plan saved by the pull request: it would be stale as soon as another pull request is applied.

## Revisit when

The instance `prod` is deployed from `main`, or the apps need an order of deployment.
