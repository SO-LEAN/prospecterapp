# Infrastructure

This document states the rules in force. The decision records (`docs/adr`) explain why. Read them only for that, or before changing a rule (ADR 0090).

This document describes how the platform is deployed to AWS ([ADR 0059](../adr/0059-terraform-per-app-values-out-of-the-repository.md)). None of this is used locally: the apps' commands set up MiniStack (`make db`).

## Layout

```
apps/api/infra/       the API: DynamoDB tables, Cognito user pool, Lambda, HTTP API (on the edge's domain), the bus, the outbox relay and the worker, the daily schedule of the scheduled tasks, the alerts topic and the alarms on the failure queues, the functions and the 5xx
apps/storage/infra/   the storage: files bucket, CloudFront and its function, private files bucket, Lambda and its signed function URL, its alarms
apps/front/infra/     the SPA: releases bucket, CloudFront and its release function
infra/modules/        modules shared by apps of the same kind, created on second use (none yet)
```

Each root has the same files:

- `versions.tf` (Terraform and the providers pinned, `.terraform.lock.hcl` committed);
- `backend.tf` (an empty S3 backend);
- `providers.tf` (the region, the allowed account, the `default_tags` `project`, `instance`, `app`, `managed-by`);
- `variables.tf`, `main.tf`, `outputs.tf`, `.tflint.hcl`;
- examples of the values it receives (`instance.tfvars.example`, `instance.tfbackend.example`, `instance.env.example`).

## Instances

An instance is a whole installation (`dev`, `prod`, `test`...), in the AWS account its values name. Everything carries its name:

| What | Convention |
|---|---|
| Resources | `prospecter-<instance>-<app>-<resource>` |
| State | key `<app>/<instance>.tfstate` in the instance's state bucket |
| Values shared between apps | SSM Parameter Store `/prospecter/<instance>/<app>/<key>`, published by the owning app, read by path by the others, empty until published ([ADR 0069](../adr/0069-a-new-instance-converges-in-two-applies.md)) |
| Application secrets | SSM `SecureString` parameters, standard tier; their value is set by hand, never in the code |

## Instance values

They stay out of this repository, in a configuration directory, `INFRA_CONFIG`. By default, it is `../prospecterapp-infra`, next to the main clone. It is also found from a worktree (the parent of the common git directory). For each instance:

| File | What | Example |
|---|---|---|
| `envs/<instance>.tfvars` | the instance, its AWS account, its region, the permissions boundary for the apps' roles if the account requires one, the addresses the alarms e-mail (`alert_emails`, sensitive) | `apps/api/infra/instance.tfvars.example` |
| `envs/<instance>.tfbackend` | its state bucket (S3, versioned, locked by `use_lockfile`) | `apps/api/infra/instance.tfbackend.example` |
| `envs/<instance>.env` | the AWS SDK environment, such as `AWS_PROFILE` (`~/.aws` is mounted) | `apps/api/infra/instance.env.example` |

Whoever deploys prepares the account, the state bucket and the credentials beforehand. Every plan reads the caller identity. Terraform refuses credentials for any account other than the instance's.

## Commands

```bash
make infra-plan  APP=api INSTANCE=dev     # re-initializes the root on the instance's state, then plans
make infra-apply APP=api INSTANCE=dev     # the same, then applies after confirmation
make infra-init  APP=storage INSTANCE=prod
make infra-lint                           # fmt, validate, tflint on every root (part of make lint)
make package-api                          # the api for its Lambda: build/api.zip (package-storage: build/storage.zip)
make package-front                        # the SPA as a release: build/front
make release-front INSTANCE=dev [CLEAN=1] # puts the release in the instance's bucket (CLEAN=1: removes the old ones)
make console INSTANCE=dev CMD="..."       # an api command on the instance's tables and user pool
```

The same commands work from an app's directory, which knows its app (ADR 0080): `cd apps/api && make infra-plan INSTANCE=dev`, `make infra-lint`, `make package`, and for the front `make release INSTANCE=dev [CLEAN=1]`.

`APP` and `INSTANCE` are given on the command line (a variable left in the shell is refused). Terraform and tflint run in Docker. Each instance has its own working directory (`.terraform/<instance>`).

## Pull request plans, `dev` and `main` applies

`.github/workflows/terraform-plan.yml` plans a pull request to `dev` or `main` that touches the infrastructure ([ADR 0060](../adr/0060-terraform-plans-every-pull-request.md)). It plans each changed app on each instance of `vars.INFRA_PLAN_INSTANCES` (by default `["dev", "prod"]`). It posts one comment per app and instance, listing the changed resources. The whole plan goes to the log, with the account ids and the state bucket masked. A failing plan fails its job. For each instance, the repository needs:

| Secret | What |
|---|---|
| `INFRA_TFVARS_<INSTANCE>` | the content of `envs/<instance>.tfvars` |
| `INFRA_TFBACKEND_<INSTANCE>` | the content of `envs/<instance>.tfbackend` |
| `AWS_PLAN_ROLE_ARN_<INSTANCE>` | the plan role in the instance's account |
| `AWS_TF_ROLE_ARN_<INSTANCE>` | the role that applies in the instance's account: a repository secret for the instances applied from `dev`, a secret of the `production` environment for those applied from `main` |

The plan role trusts the account's GitHub OIDC provider for `repo:<owner>/<repository>:pull_request`. It has `ReadOnlyAccess` and `kms:Decrypt` through SSM only (for the `SecureString` parameters). It is denied the application data: DynamoDB items, S3 objects other than the state, Cognito users, CloudWatch logs, Secrets Manager values.

`.github/workflows/terraform-apply-dev.yml` applies each push to `dev` that touches the infrastructure ([ADR 0061](../adr/0061-terraform-applies-each-merge-into-dev.md)). It applies each changed app on each instance of `vars.INFRA_DEV_INSTANCES` (by default `["dev"]`), with `-auto-approve`. Two applies of the same app and instance wait for each other. The role that applies trusts the account's GitHub OIDC provider for `repo:<owner>/<repository>:ref:refs/heads/dev` only.

`.github/workflows/terraform-apply-prod.yml` applies each push to `main` that touches the infrastructure ([ADR 0062](../adr/0062-terraform-applies-main-after-approval.md)). It applies to each instance of `vars.INFRA_MAIN_INSTANCES` (by default `["prod"]`), in the GitHub environment `production`. A required reviewer approves the jobs, and only `main` deploys to that environment. The role that applies in that account trusts `repo:<owner>/<repository>:environment:production` only.

## An app's code

- An app that runs in a Lambda (the api, the storage) is packaged by `make package-<app>` into `build/<app>.zip`. Its Terraform deploys it with its function ([ADR 0063](../adr/0063-the-cd-packages-an-app-and-its-terraform-deploys-it.md), [0083](../adr/0083-the-scripts-of-tools-become-recipes-of-their-project.md)). `make infra-apply` passes the app's code version (`code-version` in `make/aws.mk`). The version is a digest of the app's tracked files, excluding `infra/`, `script/`, the tests and its `Makefile`. A new version redeploys the package, so build the package first. The workflows plan and apply an app when its code, its infrastructure or the core it requires changes.
- An app's secrets are SSM `SecureString` parameters read when the function starts (`bref-ssm:/prospecter/<instance>/<app>/<key>`). They are never Terraform variable values, because a plan prints those.
- CloudFront serves the storage's files from its bucket and fails over to its function for a missing size ([ADR 0065](../adr/0065-the-files-on-aws-behind-cloudfront.md)). The api reads its token and its addresses in SSM. The private files (the exchanges' attachments) live in a bucket of their own that nothing serves. Browsers upload and read them with requests that the storage signs. The bucket's CORS allow the front's address ([ADR 0077](../adr/0077-the-attachments-are-private-files-reached-by-signed-urls.md)).
- A new instance is applied twice ([ADR 0069](../adr/0069-a-new-instance-converges-in-two-applies.md)). The first run creates every app with what the others have published so far. The second run (the apply workflows run by hand) gives each app the others' addresses.
- The storage's internal entry is a function URL. It answers only requests signed by the api's role or by CloudFront ([ADR 0068](../adr/0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md)). The roles' permissions boundary must allow:
  - `lambda:InvokeFunctionUrl`;
  - `cloudfront:CreateInvalidation`, so that the storage invalidates a removed file ([ADR 0070](../adr/0070-a-removed-file-is-forgotten-by-the-caches.md));
  - `ssm:PutParameter`, `ssm:GetParameter` and `ssm:DeleteParameter` under `/prospecter/<instance>/api/accounts/`, with `kms:Encrypt`, `kms:Decrypt` and `kms:GenerateDataKey` through SSM, so that the api keeps the accounts' secrets ([ADR 0125](../adr/0125-each-account-configures-its-own-google-client.md)).

  Without them, the api answers its client 503 `secretsUnavailable`.
- The SPA is a release under `releases/<version>/` in its bucket ([ADR 0066](../adr/0066-the-spa-on-aws-in-releases.md)). `make package-front` builds it once, and `make release-front INSTANCE=<instance>` puts it in the bucket before the apply that serves it. After that apply, `make release-front INSTANCE=<instance> CLEAN=1` removes the old releases 30 days after they were replaced. Its configuration (the api's address) is the `config.js` its CloudFront Function returns.

## An instance's domains

An installation puts its apps on its own domains behind its edge, a proxy such as Cloudflare ([ADR 0067](../adr/0067-the-apps-behind-an-edge.md)). It does so from outside this repository. It writes in SSM, under `/prospecter/<instance>/edge/`:

- each app's domain (`api-domain`, `front-domain`, `storage-domain`);
- its certificate in ACM (`<app>-certificate`). The certificate is in `us-east-1` for the front and the storage, since CloudFront reads only there, and in the instance's region for the api;
- the secret its edge adds to each request in the `X-Origin-Verify` header (`origin-secret`, `SecureString`).

Then the apply workflows, run by hand (`workflow_dispatch`) on `dev` or `main`, apply every app. The apps' AWS addresses refuse what does not carry the secret, and the api's address is closed. Each app publishes in `/prospecter/<instance>/<app>/edge-target` where the edge sends its domain's requests.

## Commands on an instance

`make console CMD="..."` runs an api command on MiniStack. With `INSTANCE=<instance>`, it runs the command from this machine on an instance's tables and user pool. It uses the temporary credentials of the instance's AWS profile, which the AWS CLI in its image exports and passes through the environment ([ADR 0064](../adr/0064-an-account-is-opened-by-its-founder.md)). An installation's first account is opened by `make console INSTANCE=dev CMD="app:account:open 'My company' me@example.com --form=SAS"`, which asks for the password without echo. Colleagues are registered with only the roles they need ([ADR 0075](../adr/0075-a-member-is-registered-by-an-administrator-of-the-account.md)): `make console INSTANCE=dev CMD="app:member:register me@example.com colleague@example.com --personal-data"`.

The command runs the local code against the instance's data, not the code deployed on the instance. So it is refused while the local code lacks changes from the branch the instance deploys (`origin/main` for `prod`, `origin/dev` for the others, fetched first). The message says how to update. It compares changes, not commits, because `main` receives `dev` through merge commits, and its hotfixes come back to `dev` as cherry-picks. `FORCE=1` runs it anyway, with code older than the instance ([ADR 0107](../adr/0107-a-command-on-an-instance-is-refused-while-the-local-code-lags-behind-it.md)).

## What never enters this repository

An installation's values: account ids, ARNs, state bucket names, account emails, keys and secrets. `.gitignore` refuses the values, the states and the saved plans. The pre-commit hook (`make hooks`, run by `make install`) runs gitleaks with `.gitleaks.toml` on what is committed, including `git commit -a` and commits by path. It refuses a secret, an ARN, an account id in Terraform, the workflows or the docs, an AWS organization or Identity Center id, a state bucket name, or a plus-addressed email. `make secrets-branch`, part of `make ci`, scans the branch's commits since `dev` again. Examples use the account `123456789012`.

## The apps on AWS

- The api on AWS (`infra/`, ADR 0063, 0067, 0068): the function runs `build/api.zip` (`make package-api`, `make/lambda.mk`), read-only (`Kernel` extends `BrefKernel`). It sits behind its HTTP API on the edge's domain, with its `execute-api` address closed. `Shared\OnlyThroughTheEdge` refuses a request without the edge's secret (`ORIGIN_SECRET`, `X-Origin-Verify` header). Every secret is read from SSM at startup (`bref-ssm:`).
- Each account's Google client is configured by its administrator in the SPA (ADR 0125). It is a web client in the organization's Google Cloud, and its redirect URI is `<front address>/settings/connectors/google`. The function puts, reads and deletes the client's secret itself. The secret is a `SecureString` under `/prospecter/<instance>/api/accounts/` (`ACCOUNT_SECRETS_PATH`, the `KeepTheSecretsOfTheAccounts` statement, the `aws/ssm` key through SSM). Nothing is set by hand or applied for it.
- The public booking pages (`infra/http-api.tf`, ADR 0153) have a route of their own, `GET /api/booking-pages/{code}`, on the function's integration. The stage caps its rate whatever the link and the address (`public_booking_rate_limit`, 10 per second, `public_booking_burst_limit`, 20 at once, instance variables). The api sets the per-link limit. The installation's edge sets the per-address limit. Booking a slot through a public link (ADR 0164) has a route of its own, `POST /api/booking-pages/{code}/appointments`, with a lower cap (`public_booking_book_rate_limit`, 2 per second, `public_booking_book_burst_limit`, 5 at once).
- The api's messaging (`infra/messaging.tf`, ADR 0098):
  - The tables stream their new items and forget them after their `expiresAt` (`tables.tf`).
  - The relay `prospecter-<instance>-api-relay` is the same package with `BREF_RUNTIME=function` and the handler `App\Shared\Messaging\OutboxRelay`. It has a role of its own (the tables' streams, `events:PutEvents` on the bus, its failure queue, its logs).
  - A mapping on each table's stream calls it, filtered on `OUTBOX#` insertions, 10 records from `TRIM_HORIZON`. A failing batch is split and retried 5 times, and a record is kept a day at most. Then its metadata goes to the queue `prospecter-<instance>-api-relay-failures`, kept 14 days.
  - The relay publishes on the bus `prospecter-<instance>-api-integration`, with no archive.
- The worker (`infra/messaging.tf`, ADR 0101) is the function `prospecter-<instance>-api-worker`. It has the api's package, layers and environment (`local.environment` in `lambda.tf`), with `BREF_RUNTIME=function`, the handler `App\Shared\Messaging\IntegrationEventConsumer` and the `outbox` delivery.
  - Its timeout is 120 seconds (ADR 0182: an imported e-mail's files are read from Gmail and sent to the private storage for each correspondent).
  - Its reserved concurrency is `worker_concurrency` when the instance sets it (ADR 0131). An invocation beyond it is throttled, then kept and retried by Lambda, and the rest of the account's executions are left to the api. The worker has no reserved concurrency when the instance sets none (ADR 0132). AWS keeps a minimum of the account's executions unreserved, 100 according to its documentation. So a reservation of 10 needs an account that accepts about 110 at once, and an account limited to 10 refuses it. An instance sets it once its account's quota allows it (#101).
  - Its role has the statements on the api's data (`local.data_statements`), its logs, and `sqs:SendMessage` on the queue `prospecter-<instance>-api-integration-failures`. The queue keeps messages 14 days and has no consumer. Its policy lets the rules send there too.
  - It is invoked asynchronously: 2 retries, an event kept 6 hours at most (`aws_lambda_function_event_invoke_config`), then that queue.
  - There is one bus rule per subscription in `apps/api/config/subscriptions.json`, named after it (`event_pattern` on its sources and events). Its target is the worker with an input transformer (`{"subscription": "<name>", "source": <source>, "detailType": <detailType>, "detail": <detail>}`). The target is retried for a day (185 attempts), then goes to the same queue. The rule has its permission to invoke the worker. A new subscription becomes a new rule at the next apply.
- The alerts (`infra/alerting.tf`, ADR 0178):
  - The SNS topic `prospecter-<instance>-api-alerts` is unencrypted (the key SNS manages refuses CloudWatch, and the messages hold no data). Its ARN is published in SSM `/prospecter/<instance>/api/alerts-topic` for the other apps' alarms. Its name is the `alerts_topic` output.
  - There is one e-mail subscription per address in `alert_emails`. There are none by default, and the list is sensitive, so the plan shows only how many there are. Each subscription waits for the confirmation sent to its address; Terraform cannot confirm it. Add an address at the end of the list. Since the addresses are sensitive, a subscription is known by its position, and removing one replaces those after it. Confirm an address before removing it. Terraform forgets an unconfirmed subscription, which stays in AWS until it expires.
  - There is one alarm per failure queue, `prospecter-<instance>-api-relay-failures-waiting` and `…-api-integration-failures-waiting`. It fires on `ApproximateNumberOfMessagesVisible` above 0 (its maximum over 5 minutes, missing data not breaching). The topic is notified on alarm and on return to OK. The description gives the recovery command (`app:outbox:republish --since=<moment>`, `app:integration:redrive`, then `app:consistency:check`). The role that applies needs SNS on the topic and the CloudWatch alarms.
  - Each function (`api`, `scheduled`, `relay`, `worker`, and the storage's) has an alarm `<function>-errors` (`Errors`) and `<function>-throttled` (`Throttles`). They fire on their sum over 5 minutes above `function_errors_threshold` and `function_throttles_threshold` (instance values, 0 by default). `scheduled` alarms at its first error whatever the value.
  - A failing scheduled task does not fail its invocation. A metric filter on `scheduled`'s log group counts it (`{ $.message = "A scheduled task failed" }`, metric `ScheduledTaskFailures` in `Prospecter/<instance>/api`). `…-api-scheduled-task-failed` alarms at its first failure.
  - 5xx answers over 5 minutes above `http_5xx_threshold` (5 by default) raise an alarm: `5xx` of the HTTP API, `Url5xxCount` of the storage's function URL. There is no alarm on duration, since a timeout is an error (ADR 0179).
  - Each description names the log group to read. For the relay, the worker and `scheduled`, it also names the recovery command.
  - The storage reads the topic from SSM by path (`/prospecter/<instance>/api`). Its alarms notify nobody until the api has published it (ADR 0069). The role that applies needs `logs:PutMetricFilter` on `scheduled`'s log group.
- The scheduled tasks (`infra/schedule.tf`, ADR 0146, 0154, 0161):
  - The function `prospecter-<instance>-api-scheduled` has the api's package, layers, data statements and environment. Its handler `App\Shared\ScheduledTasksFunction` runs every task one after the other (`App\Shared\ScheduledTasks`, each an `App\Shared\ScheduledTask` from the container).
  - A failing task is logged with its name and listed under `failed` in the answer. The following tasks still run, and the invocation does not fail.
  - One EventBridge Scheduler schedule, `prospecter-<instance>-api-scheduled` (`rate(1 day)`), calls it once a day, with no input and no retry. The next run makes up for a missed one.
  - The tasks: `erase-unattached` erases the contacts left unattached, and `renew-gmail-watches` renews the watch of each active Gmail import. A new task is a class in the api, with nothing to apply.
  - `make console INSTANCE=<instance> CMD="app:scheduled-tasks:run"` does the same by hand. `CMD="app:contacts:erase-unattached"` and `CMD="app:gmail:renew-watches"` run one task each.
- The tables (`infra/tables.tf`): there is one per context, `prospecter-<instance>-api-<context>` (`accounts`, `booking`, `campaigns`, `connectors`, `personal-data`, `prospecting`, `reporting`). They all have the same shape, are protected from deletion, and are recoverable to the second. The api's and the worker's statements (`local.data_statements`) cover them all. The `reporting` table keeps figures derived from the others (ADR 0104). Once an apply has created it, run two commands from this machine, outside a function's time limit:
  1. `make console INSTANCE=<instance> CMD="app:keys:rebuild"`. It writes the keys that list the accounts and an account's exchanges into the items written before those keys existed (ADR 0099).
  2. `make console INSTANCE=<instance> CMD="app:reporting:rebuild"`, which counts the figures.

  Until then the figures read zero. A rebuild before the keys counts none of the accounts written before them.
- The MCP server and the AIs' sign-in (ADR 0156, 0157, `mcp.md`):
  - `/mcp`, `/oauth/*` and `/.well-known/oauth-*` have their own routes on the HTTP API (`local.oauth_routes` in `http-api.tf`). The stage's `route_settings` throttle each one (`oauth_rate_limit`, `oauth_burst_limit`), since throttling `$default` would cap the whole API.
  - `cognito.tf` holds the domain of the pool's sign-in page (`cognito_domain_prefix`, by default the api's name). It uses the classic hosted UI, since the Lite tier has no managed login editor. It also holds the confidential client `prospecter-mcp` (its callback `<api>/oauth/callback`). The api reads the client's secret through `DescribeUserPoolClient`; the secret is never written anywhere.
  - The key that seals the requests is the SSM parameter `oauth-seal-key` (`random_bytes`, `bref-ssm:`). `COGNITO_SIGN_IN_URL` and `COGNITO_OAUTH_URL` are the pool's domain.
  - The edge lets Anthropic's egress addresses reach the API, since Claude on the web calls from Anthropic's servers. The installation's edge also rate-limits them by address.
- An instance's delivery is `outbox` (ADR 0108). It is the default of `integration_events_delivery`, of the api's `INTEGRATION_EVENTS_DELIVERY`, and of `make console INSTANCE=`, which reads it from the same values as the region. The worker always delivers `outbox`.
  - An instance whose reactions are not in service sets `integration_events_delivery = "sync"` in its values. The worker and its rules are deployed, but nothing is published on the bus.
  - An instance switches to the `outbox` delivery with the apply of the code that brings it (`dev` at the merge, `prod` at the release, ADR 0058). The same apply creates the `reporting` table and the messaging. An instance that holds data then runs `app:keys:rebuild` and `app:reporting:rebuild`. An empty one has correct figures at zero.
  - `app:consistency:check` then says whether the figures and the reactions agree with the tables.
  - `make console INSTANCE=<instance> CMD="app:integration:redrive"` gives the worker again what the failure queue keeps.
- The storage on AWS (`infra/`, ADR 0065): CloudFront acts as the static host. It serves the bucket and fails over to the function URL for a missing size (origin path `/storage`, signed by its origin access control of type `lambda`). The internal server is a function URL that answers only signed requests. The api signs with its role, and CloudFront with its origin access control (ADR 0068). The token and the addresses are in SSM `/prospecter/<instance>/storage/*`. The function runs `build/storage.zip` (`make package-storage`, ADR 0063), read-only: `Kernel` extends `BrefKernel`. A removed file is invalidated in CloudFront (`PublicCache`, ADR 0070), and the edge in front is told to keep no copy.
