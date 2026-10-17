# 0059. Each app keeps its Terraform; the values of an installation stay out of the repository

- Date: 2026-10-16
- Status: Accepted
- Implemented by: `apps/{api,storage,front}/infra/`, `.terraform-version`, the `infra-*`, `hooks` and `secrets` targets of the `Makefile`, `.gitleaks.toml`, `tools/git-hooks/pre-commit`, `docs/infra.md`

## Context

The platform described by 0048 to 0055 (DynamoDB, Cognito, S3, Lambdas with the Bref layers behind HTTP APIs) goes to AWS. Locally MiniStack is set up by the commands of the apps, without Terraform; on AWS the infrastructure must be code. This repository is public (GPL-3.0): anyone may fork it and deploy it in their own AWS account. The code describes the platform; how an installation is organized and what opens it belongs to whoever runs it.

## Decision

- **One root per app.** Each app keeps its Terraform in `apps/<app>/infra`, and only what concerns it: Cognito is in `apps/api/infra` (the API is its resource server and administers it). There is no global root. An app that needs an output of another reads it in SSM Parameter Store, published by the owner, never by `terraform_remote_state`: the roots stay independent and deployable alone. Reusable modules go to `infra/modules/` at the root, created at the second use.
- **Instances.** An instance is a whole installation of the platform with a short name (`dev`, `prod`, `test`...). It names everything: resources `prospecter-<instance>-<app>-<resource>`, state key `<app>/<instance>.tfstate`, SSM paths `/prospecter/<instance>/<app>/<key>`, the tag `instance` of `default_tags`. The variable `instance` has no default.
- **The values of an instance stay out of the repository**, in a configuration directory (`INFRA_CONFIG`): `envs/<instance>.tfvars` (its AWS account and region), `envs/<instance>.tfbackend` (its state bucket), `envs/<instance>.env` (the environment of the AWS SDK, such as its profile). Here, only `*.example` files with the placeholder account `123456789012`. Each root checks at every plan that the credentials are those of the account of the instance. A gitleaks hook (`make hooks`, run by `make install`) refuses a commit holding a secret or an identifier of an AWS installation; `make ci` scans the commits of the branch again.
- **Application secrets** are SSM Parameter Store `SecureString` parameters: Terraform creates the parameters and their read permissions, their value is set by hand or ignored by Terraform (`ignore_changes` on `value`).
- **Tooling.** Terraform (pinned in `.terraform-version` and the `versions.tf`, the `.terraform.lock.hcl` committed) and tflint run in their images, as everything else (0015); the state is locked natively by S3 (`use_lockfile`). `make lint` checks the format, the validity and tflint of each root.

## Consequences

- A pull request that adds a table to the API changes `apps/api/src` and `apps/api/infra` together; a plan or a broken state touches one app of one instance.
- The order of the first deployment matters (an app publishes in SSM what another reads), and the versions of Terraform and of the providers are repeated in each root.
- An installation is three small files per instance; the accounts, their state buckets and the credentials that reach them are prepared beforehand by whoever deploys.

## Alternatives considered

- One `infra/` root for the whole platform: simpler at first, but one state for everything, plans touching everything, and the infrastructure away from the code it serves.
- `terraform_remote_state` between the roots: it couples the states and the deployments; SSM keeps them apart and is also readable by the applications.
- AWS Secrets Manager for the application secrets: its only advantage, the automatic rotation, is not needed, at about 0.40 USD a secret a month.
- Terraform installed on the host (tenv): it would break the rule of nothing but Docker and make on the host (0015).

## Revisit when

A resource shared by several apps appears (a DNS zone, an alarm on the whole instance), or a continuous deployment runs the roots.
