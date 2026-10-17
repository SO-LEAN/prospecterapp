# 0063. The continuous deployment packages an app, its Terraform deploys the package

- Date: 2026-10-17
- Status: Accepted
- Implemented by: `tools/package/api`, `apps/api/src/Kernel.php`, `tools/ci/code-version`, `tools/ci/infra-changed-apps`, the `package-api` target and `TF_VAR_code_version` of the `Makefile`, `apps/api/infra/{lambda,http-api,parameters}.tf`, the workflows `terraform-*.yml`, `apps/api/config/services.yaml`

## Context

The api is to run on AWS in a Lambda with the PHP layer of Bref behind an HTTP API (0053, 0055), its tables being there already (0059). Its code must reach the function by the same road as its infrastructure: planned on the pull request, applied to `dev` after the merge, to `prod` after the approval (0060 to 0062). The plans and their logs are public.

## Decision

- `make package-<app>` packages an app in `build/<app>.zip`, in the php container: its code without what only develops or tests it, its dependencies without those of development, the core copied in (`COMPOSER_MIRROR_PATH_REPOS`), the cache of the prod environment warmed.
- The Terraform of the app deploys the package with its function. The version of its code, a digest of the tracked files of the app (and of the core when it requires it) without its infrastructure and its tests, decides a redeployment: the package is built only to apply, a plan needs none.
- The workflows of 0060 to 0062 run on a change of the code of an app or of the core too; an app with a package is packaged before its apply.
- The secrets of an app are SSM parameters read by the function at its start (`bref-ssm:`), never values of its variables, which a plan prints. Its roles carry the permissions boundary the values of the instance name, when there is one.
- The SDK of AWS takes the session token of the credentials of the function, and no endpoint when the MiniStack one is empty.
- The function runs its package read-only: the kernel of the api extends the `BrefKernel` of `bref/symfony-bridge`, which moves the cache and the logs to `/tmp` and copies there the cache warmed in the package, only when the project cannot be written (the local Lambdas and the command line, on the images of Bref too, keep the shared cache of 0053); the share dir is compiled to `/tmp`, and the router is compiled at the first request, its routes being bound to the host of the instance.

## Consequences

- A pull request changing `apps/api/src` shows in its plan that the function changes; once merged, `dev` runs it within minutes.
- An apply of the api builds its package first: a few minutes more than the infrastructure alone.
- Until Cognito, the storage and the domain are on AWS, the api answers on the address of its HTTP API, without signing in.

## Alternatives considered

- Terraform for the function only, the code sent apart (`aws lambda update-function-code`): two roads to production, and a plan that never shows a deployment of code coming.
- The digest of the package as its version: the package is not reproducible (dates, the cache), it would redeploy at every apply.

## Revisit when

The package outgrows the limits of Lambda (50 MB zipped sent directly, 250 MB unzipped with the layer), or another app gets a function.
