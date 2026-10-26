# Infrastructure

How the platform goes to AWS ([ADR 0059](adr/0059-terraform-per-app-values-out-of-the-repository.md)). Locally nothing of this is used: MiniStack is set up by the commands of the apps (`make db`).

## Layout

```
apps/api/infra/       the API: DynamoDB tables, Cognito user pool, Lambda, HTTP API (on the domain of the edge)
apps/storage/infra/   the storage: files bucket, CloudFront and its function, private files bucket, Lambda and its signed function URL
apps/front/infra/     the SPA: bucket of the releases, CloudFront and its release function
infra/modules/        modules shared by apps of the same kind, created at the second use (none yet)
```

Each root has the same files: `versions.tf` (Terraform and the providers pinned, `.terraform.lock.hcl` committed), `backend.tf` (an empty S3 backend), `providers.tf` (the region, the allowed account, the `default_tags` `project`, `instance`, `app`, `managed-by`), `variables.tf`, `main.tf`, `outputs.tf`, `.tflint.hcl`, and the examples of the values it receives (`instance.tfvars.example`, `instance.tfbackend.example`, `instance.env.example`).

## Instances

An instance is a whole installation (`dev`, `prod`, `test`...), in the AWS account its values name. Everything carries its name:

| What | Convention |
|---|---|
| Resources | `prospecter-<instance>-<app>-<resource>` |
| State | key `<app>/<instance>.tfstate` in the state bucket of the instance |
| Values shared between apps | SSM Parameter Store `/prospecter/<instance>/<app>/<key>`, published by the owner app, read by path by the others, empty until published ([ADR 0069](adr/0069-a-new-instance-converges-in-two-applies.md)) |
| Application secrets | SSM `SecureString` parameters, standard tier; their value is set by hand, never in the code |

## Values of an instance

They stay out of this repository, in a configuration directory: `INFRA_CONFIG`, by default `../prospecterapp-infra` next to this clone. For each instance:

| File | What | Example |
|---|---|---|
| `envs/<instance>.tfvars` | the instance, its AWS account, its region, the permissions boundary of the roles of the apps if the account requires one | `apps/api/infra/instance.tfvars.example` |
| `envs/<instance>.tfbackend` | its state bucket (S3, versioned, locked by `use_lockfile`) | `apps/api/infra/instance.tfbackend.example` |
| `envs/<instance>.env` | the environment of the AWS SDK, such as `AWS_PROFILE` (`~/.aws` is mounted) | `apps/api/infra/instance.env.example` |

The account, the state bucket and the credentials are prepared beforehand by whoever deploys. Every plan reads the caller identity: Terraform refuses credentials of another account than the one of the instance.

## Commands

```bash
make infra-plan  APP=api INSTANCE=dev     # re-initializes the root on the state of the instance, then plans
make infra-apply APP=api INSTANCE=dev     # the same, then applies after confirmation
make infra-init  APP=storage INSTANCE=prod
make infra-lint                           # fmt, validate, tflint on every root (part of make lint)
make package-api                          # the api for its Lambda: build/api.zip (package-storage: build/storage.zip)
make package-front                        # the SPA as a release: build/front
make release-front INSTANCE=dev [CLEAN=1] # put the release in the bucket of the instance (CLEAN=1: remove the former ones)
make console INSTANCE=dev CMD="..."       # a command of the api on the tables and the user pool of the instance
```

The same from the directory of an app, which knows its app (ADR 0080): `cd apps/api && make infra-plan INSTANCE=dev`, `make infra-lint`, `make package`, and for the front `make release INSTANCE=dev [CLEAN=1]`.

`APP` and `INSTANCE` are given on the command line (a variable left in the shell is refused). Terraform and tflint run in Docker; each instance has its own working directory (`.terraform/<instance>`).

## Plan of a pull request, apply of `dev` and `main`

A pull request to `dev` or `main` touching the infrastructure is planned by `.github/workflows/terraform-plan.yml` ([ADR 0060](adr/0060-terraform-plans-every-pull-request.md)): each changed app on each instance of `vars.INFRA_PLAN_INSTANCES` (by default `["dev", "prod"]`), a comment per app and instance listing the changed resources, the whole plan in the log with the account ids and the state bucket masked; a plan that fails fails its job. Per instance, the repository needs:

| Secret | What |
|---|---|
| `INFRA_TFVARS_<INSTANCE>` | the content of `envs/<instance>.tfvars` |
| `INFRA_TFBACKEND_<INSTANCE>` | the content of `envs/<instance>.tfbackend` |
| `AWS_PLAN_ROLE_ARN_<INSTANCE>` | the plan role of the account of the instance |
| `AWS_TF_ROLE_ARN_<INSTANCE>` | the role that applies in the account of the instance: a repository secret for the instances applied from `dev`, a secret of the environment `production` for those applied from `main` |

The plan role trusts the GitHub OIDC provider of the account for `repo:<owner>/<repository>:pull_request`; it has `ReadOnlyAccess`, `kms:Decrypt` through SSM only (the `SecureString` parameters), and is denied the application data: DynamoDB items, S3 objects other than the state, Cognito users, CloudWatch logs, Secrets Manager values.

Each push to `dev` touching the infrastructure is applied by `.github/workflows/terraform-apply-dev.yml` ([ADR 0061](adr/0061-terraform-applies-each-merge-into-dev.md)): each changed app on each instance of `vars.INFRA_DEV_INSTANCES` (by default `["dev"]`), with `-auto-approve`, two applies of the same app and instance waiting for each other. The role that applies trusts the GitHub OIDC provider of the account for `repo:<owner>/<repository>:ref:refs/heads/dev` only.

Each push to `main` touching the infrastructure is applied by `.github/workflows/terraform-apply-prod.yml` ([ADR 0062](adr/0062-terraform-applies-main-after-approval.md)) to each instance of `vars.INFRA_MAIN_INSTANCES` (by default `["prod"]`), in the GitHub environment `production`: a required reviewer approves the jobs, and only `main` deploys to it. The role that applies in that account trusts `repo:<owner>/<repository>:environment:production` only.

## Code of an app

An app running in a Lambda (the api, the storage) is packaged by `make package-<app>` in `build/<app>.zip` and deployed by its Terraform with its function ([ADR 0063](adr/0063-the-cd-packages-an-app-and-its-terraform-deploys-it.md)): `make infra-apply` passes the version of its code (`code-version` of `make/aws.mk`, a digest of its tracked files without `infra/`, the tests and its `Makefile`), a new one redeploying the package; build it first. The workflows plan and apply an app when its code, its infrastructure or the core it requires change. Its secrets are SSM `SecureString` parameters read at the start of the function (`bref-ssm:/prospecter/<instance>/<app>/<key>`), never values of its variables: a plan prints them. The files of the storage are served by CloudFront from its bucket, a missing size failing over to its function ([ADR 0065](adr/0065-the-files-on-aws-behind-cloudfront.md)); the api reads its token and its addresses in SSM. The private files (the attachments of the exchanges) are in a bucket of their own that nothing serves, sent and read by the browsers with requests the storage signs, its CORS allowing the address of the front ([ADR 0077](adr/0077-the-attachments-are-private-files-reached-by-signed-urls.md)). A new instance is applied twice ([ADR 0069](adr/0069-a-new-instance-converges-in-two-applies.md)): the first run creates every app with what the others published so far, the second (the apply workflows run by hand) gives each the addresses of the others. The internal entry of the storage is a function URL answering only the requests signed by the role of the api or by CloudFront ([ADR 0068](adr/0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md)); the permissions boundary of the roles must allow `lambda:InvokeFunctionUrl`, and `cloudfront:CreateInvalidation` for the storage to invalidate a removed file ([ADR 0070](adr/0070-a-removed-file-is-forgotten-by-the-caches.md)). The SPA is a release under `releases/<version>/` of its bucket, built once by `make package-front`, put by `make release-front INSTANCE=<instance>` before the apply that serves it, former releases removed 30 days after they are replaced by `make release-front INSTANCE=<instance> CLEAN=1` after it; its configuration (the address of the api) is the `config.js` its CloudFront Function answers ([ADR 0066](adr/0066-the-spa-on-aws-in-releases.md)).

## Domains of an instance

An installation puts its apps on its domains behind its edge, a proxy such as Cloudflare ([ADR 0067](adr/0067-the-apps-behind-an-edge.md)), from outside this repository: it writes in SSM, under `/prospecter/<instance>/edge/`, the domain of each app (`api-domain`, `front-domain`, `storage-domain`), its certificate in ACM (`<app>-certificate`: in `us-east-1` for the front and the storage, CloudFront reading only there, in the region of the instance for the api) and the secret its edge adds to each request in the header `X-Origin-Verify` (`origin-secret`, `SecureString`). Then the apply workflows, run by hand (`workflow_dispatch`) on `dev` or `main`, apply every app: their AWS addresses refuse what does not carry the secret, the one of the api is closed, and each app publishes in `/prospecter/<instance>/<app>/edge-target` where the edge sends the requests of its domain.

## Commands of an instance

`make console CMD="..."` runs a command of the api on MiniStack; with `INSTANCE=<instance>`, it runs it from this machine on the tables and the user pool of an instance, with the temporary credentials of its AWS profile, exported by the AWS CLI in its image and passed through the environment ([ADR 0064](adr/0064-an-account-is-opened-by-its-founder.md)). The first account of an installation: `make console INSTANCE=dev CMD="app:account:open 'My company' me@example.com --form=SAS"`, the password asked without echo. Its colleagues, with only the roles they need ([ADR 0075](adr/0075-a-member-is-registered-by-an-administrator-of-the-account.md)): `make console INSTANCE=dev CMD="app:member:register me@example.com colleague@example.com --first-name=Ada --personal-data"`.

## What never enters this repository

The values of an installation: account ids, ARNs, state bucket names, account emails, keys and secrets. `.gitignore` refuses the values, the states and the saved plans; the pre-commit hook (`make hooks`, run by `make install`) runs gitleaks with `.gitleaks.toml` on what is committed, `git commit -a` and commits by paths included, and refuses a secret, an ARN, an account id in Terraform, the workflows or the docs, an id of an AWS organization or of an Identity Center, a state bucket name or a plus-addressed email; `make secrets-branch`, part of `make ci`, scans the commits of the branch since `dev` again. An example uses the account `123456789012`.
