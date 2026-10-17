# Infrastructure

How the platform goes to AWS ([ADR 0059](adr/0059-terraform-per-app-values-out-of-the-repository.md)). Locally nothing of this is used: MiniStack is set up by the commands of the apps (`make db`).

## Layout

```
apps/api/infra/       the API: DynamoDB tables, Cognito user pool, Lambda, HTTP API
apps/storage/infra/   the storage: files bucket, Lambda, HTTP API
apps/front/infra/     the SPA: static files
infra/modules/        modules shared by apps of the same kind, created at the second use (none yet)
```

Each root has the same files: `versions.tf` (Terraform and the providers pinned, `.terraform.lock.hcl` committed), `backend.tf` (an empty S3 backend), `providers.tf` (the region, the allowed account, the `default_tags` `project`, `instance`, `app`, `managed-by`), `variables.tf`, `main.tf`, `outputs.tf`, `.tflint.hcl`, and the examples of the values it receives (`instance.tfvars.example`, `instance.tfbackend.example`, `instance.env.example`).

## Instances

An instance is a whole installation (`dev`, `prod`, `test`...), in the AWS account its values name. Everything carries its name:

| What | Convention |
|---|---|
| Resources | `prospecter-<instance>-<app>-<resource>` |
| State | key `<app>/<instance>.tfstate` in the state bucket of the instance |
| Values shared between apps | SSM Parameter Store `/prospecter/<instance>/<app>/<key>`, published by the owner app |
| Application secrets | SSM `SecureString` parameters, standard tier; their value is set by hand, never in the code |

## Values of an instance

They stay out of this repository, in a configuration directory: `INFRA_CONFIG`, by default `../prospecterapp-infra` next to this clone. For each instance:

| File | What | Example |
|---|---|---|
| `envs/<instance>.tfvars` | the instance, its AWS account, its region | `apps/api/infra/instance.tfvars.example` |
| `envs/<instance>.tfbackend` | its state bucket (S3, versioned, locked by `use_lockfile`) | `apps/api/infra/instance.tfbackend.example` |
| `envs/<instance>.env` | the environment of the AWS SDK, such as `AWS_PROFILE` (`~/.aws` is mounted) | `apps/api/infra/instance.env.example` |

The account, the state bucket and the credentials are prepared beforehand by whoever deploys. Every plan reads the caller identity: Terraform refuses credentials of another account than the one of the instance.

## Commands

```bash
make infra-plan  APP=api INSTANCE=dev     # re-initializes the root on the state of the instance, then plans
make infra-apply APP=api INSTANCE=dev     # the same, then applies after confirmation
make infra-init  APP=storage INSTANCE=prod
make infra-lint                           # fmt, validate, tflint on every root (part of make lint)
```

`APP` and `INSTANCE` are given on the command line (a variable left in the shell is refused). Terraform and tflint run in Docker; each instance has its own working directory (`.terraform/<instance>`).

## What never enters this repository

The values of an installation: account ids, ARNs, state bucket names, account emails, keys and secrets. `.gitignore` refuses the values, the states and the saved plans; the pre-commit hook (`make hooks`, run by `make install`) runs gitleaks with `.gitleaks.toml` on what is committed, `git commit -a` and commits by paths included, and refuses a secret, an ARN, an account id in Terraform, the workflows or the docs, an id of an AWS organization or of an Identity Center, a state bucket name or a plus-addressed email; `make secrets-branch`, part of `make ci`, scans the commits of the branch since `dev` again. An example uses the account `123456789012`.
