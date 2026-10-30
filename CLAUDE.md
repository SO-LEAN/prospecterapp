# prospecterapp

Monorepo of a prospecting application built with clean architecture:

| Path | What | Instructions |
|---|---|---|
| `apps/front/` | React 19 SPA (TypeScript, Vite, TanStack Router/Query, shadcn/ui) | `apps/front/CLAUDE.md` |
| `apps/api/` | Symfony 8.1 JSON API documented with NelmioApiDocBundle, a resource server of the Cognito user pool the members sign in with (MiniStack locally, `docs/auth.md`) | `apps/api/CLAUDE.md` |
| `apps/storage/` | Symfony 8.1 app keeping the uploaded files in an S3 bucket and serving them in named sizes (`static.local.prospecter.io`) | `apps/storage/CLAUDE.md` |
| `packages/core/` | `prospecter/core`: the bounded contexts Accounts, PersonalData and Prospecting and their shared kernel (pure PHP), tested by Behat scenarios | `packages/core/CLAUDE.md` |

Docs: `docs/clean-code.md`, `docs/front-architecture.md`, `docs/design-system.md`, `docs/auth.md`, `docs/core-tests.md`, `docs/monorepo.md`, `docs/infra.md`, `docs/upgrade-2026.md`.

## Architecture decisions

The index below is loaded in every session; open a record before working on its subject. A decision that changes the structure, a protocol or a tool gets a new record: follow `docs/adr/TEMPLATE.md` (an accepted record is never edited, a new one supersedes it; alternatives only if really weighed).

@docs/adr/README.md

## Commands

Everything runs in Docker (MiniStack with the Lambdas, Node 24, nginx; the PHP 8.5 of the Lambdas for the command line, ADR 0056); nothing but Docker and make on the host. Each project has its `Makefile`, run from its directory (`cd apps/api && make test`), including only the fragments of `make/`; the root `Makefile` is the façade, running the targets of the projects and holding only what crosses them (ADR 0080): a command of one project goes in its Makefile, then in the façade under its former name. From the repository root (`make` alone lists the commands; a new target gets a `## description` on its line):

- `make install`: images, Composer and pnpm dependencies, and in MiniStack the DynamoDB tables and the Cognito user pool of the API and the S3 bucket of the storage, with the data and the members of the demonstration
- `make start` / `make stop`: the stack, the API and the storage deployed in their Lambdas on MiniStack (ADR 0055): https://local.prospecter.io (SPA, sign in `prospector@solean-it.io` / `password`, or `assistant@solean-it.io` / `password`, a prospector without the right to manage personal data), https://api.local.prospecter.io/api/doc (API doc)
- `make ci` (what the CI runs: build of the images, dependencies, lint, tests, contract, build of the front), `make test` (core scenarios and unit tests, API, storage, front), `make test-core` (the business rules only), `make test-<core|api|storage|front> FILTER=<name>` (only the matching scenarios and tests, or test files of the front), `make e2e` (Playwright on the running stack, once every part of it answers, its functions warmed), `make lint` (`FILTER=<paths from the root>`: only them, each by its project), `make fix`
- `make api-doc`: after changing an endpoint, regenerate `apps/api/openapi.json` and the front API types (`make test` fails while the committed contract differs from the code)
- `make infra-plan APP=<api|storage|front> INSTANCE=<dev|prod|...>` / `make infra-apply …`: the Terraform of an app on AWS (`apps/<app>/infra`, ADR 0059, `docs/infra.md`), the values of the instance in `envs/<instance>.{tfvars,tfbackend,env}` of `INFRA_CONFIG` (default `../prospecterapp-infra`), out of the repository; `make package-api` / `make package-storage` package the app for its Lambda (`build/<app>.zip`, ADR 0063, built before an apply), `make package-front` the SPA as a release (`build/front`), put by `make release-front INSTANCE=<instance>` before the apply (ADR 0066); `make infra-lint` (part of `make lint`)
- `make console CMD="..." [INSTANCE=<instance>]`: a command of the API, on MiniStack, or with `INSTANCE` on an instance on AWS with the credentials of its AWS profile (`app:account:open <corporate name> <login email>` opens an account and its founder, ADR 0064; `app:member:register <login email of an administrator> <login email>` registers a member in their account, a prospector unless `--personal-data` or `--admin`, ADR 0075)
- `make db`: recreate in MiniStack the dev tables and the user pool of the API and the bucket of the storage (destroys local data); `bin/console app:search:rebuild` writes again the search keys of the API (ADR 0071)

Other commands: `docker compose run --rm --no-deps php <cmd>` (working dir `apps/api`; add `-w /var/www/src/apps/storage` or `-w /var/www/src/packages/core` for the others) or `docker compose run --rm --no-deps node <cmd>` (working dir `apps/front`).

Before declaring work done: `make ci` passes (what the CI runs: lint, tests, contract, build of the front); `make e2e` for changes to a main journey.

## Code conventions

- Clean code as taught by Robert C. Martin: read `docs/clean-code.md` before writing or reviewing code, and leave the code cleaner than you found it. In short: native types everywhere; no comment that repeats a type or explains the code: give the intention a name (extract a function, a constant); short methods with one level of abstraction; command-query separation; docblocks only for what the language cannot express (generics, array shapes); dependencies are injected, never built (ADR 0044, a PHPStan rule of `packages/phpstan-rules`). PHP: readonly DTOs, constructor promotion. TypeScript: strict, no `any`. The guide's **Known debt** lists are the backlog of the improvement iterations.
- Do not trust existing docblocks blindly: some were wrong (a `@var stdClass` led Rector to produce a broken type).
- Business rules belong to `packages/core`, described by its Gherkin scenarios (`docs/core-tests.md`); the API only adapts (HTTP, DynamoDB, storage, authentication); the front only presents.
- One invokable controller per API endpoint, grouped by bounded context (`src/<Context>/Controller`); resource-oriented URLs.

## Git

- `dev` integrates the work (ADR 0057): a `feature/<name>` or `fix/<name>` branch starts from `dev` and comes back by pull request (`gh pr create --base dev`), merged by rebase once the CI passes (`gh pr merge --rebase`): `dev` stays linear. `dev` goes to `main` by a pull request merged by a merge commit (`gh pr merge --merge`, ADR 0058: a rebase merge of GitHub rewrites the commits, `main` and `dev` would diverge). A `hotfix/<name>` starts from `main`, comes back to `main` the same way, then is cherry-picked onto `dev` (a `fix/` branch).
- `dev` is the default branch of the repository (pull requests and clones start there). `main` and `dev` are protected (rulesets `dev` and `main`, no bypass): no direct push, the check `ci` must pass, no force-push; rebase only on `dev`, merge commits only on `main`.
- The CI (`.github/workflows/ci.yml`) runs `make ci`: run it before pushing. The e2e journeys stay local (`make e2e`). A pull request touching `apps/*/infra` is also planned by Terraform (`.github/workflows/terraform-plan.yml`, ADR 0060), a comment per app and instance; once merged into `dev`, it is applied to the instance `dev` (`.github/workflows/terraform-apply-dev.yml`, ADR 0061), and once in `main`, to `prod` after an approval in the environment `production` (`.github/workflows/terraform-apply-prod.yml`, ADR 0062); both can be run by hand on their branch (`workflow_dispatch`), every app applied: after the edge changed (ADR 0067), or for the second apply of a new instance (ADR 0069).
- A pull request merged into `dev` moves the tickets it refers to (`Refs <backlog>#<n>`) to the column QA of the project of the backlog (`.github/workflows/backlog-qa.yml`); the release to `main` and a pull request closed without merge leave the board alone. The repository needs the secret `PROJECT_TOKEN` (a fine-grained token: Projects of the organization read and write, Issues of the backlog read) and the variables `BACKLOG_OWNER`, `BACKLOG_PROJECT` (its number) and `BACKLOG_REPOSITORY` (`<owner>/<repository>`).
- Conventional commits (`feat:`, `fix:`, `refactor:`, `chore:`, `docs:`) explaining why.
- Commit or push only when asked.

## Gotchas

- The repository is public and describes the platform, never an installation of it (ADR 0059): no AWS account id, ARN, state bucket name, account email or secret in a commit, not even an intermediate one, nor how the AWS accounts are organized; read `git diff --cached` before each commit; the pre-commit hook (`make hooks`) runs gitleaks; never `git add -f` an ignored file.

- MiniStack (`ministack` service, ADR 0048) emulates the AWS services locally: DynamoDB and Cognito for the API, S3 for the storage (ADR 0051, 0052), their state in the volume `ministack-data`, reachable from the host (IDE, AWS CLI) at `http://localhost:4566`, region `eu-west-3`, keys `test`/`test`. After `docker compose up -d` recreated `node`, restart `nginx` (it keeps its former address on the SPA host: 502; the API and static hosts resolve MiniStack on each request).
- Cognito (ADR 0052): MiniStack makes the ids of the user pool and of its client at random, the API finds them by name and caches them; after the volume `ministack-data` was removed, run `make db`. The SPA reaches the Cognito of MiniStack through `https://api.local.prospecter.io/cognito/` (nginx passes its calls only).

- The API and the storage run only in their Bref Lambdas on MiniStack (ADR 0055), the repository mounted in them: `make start` (or `make deploy`) deploys them behind their HTTP APIs; the `php` container is the command line only (tests, Composer, console), on the PHP of the Lambdas (the dev image of Bref, ADR 0056; a new version of Bref goes in `docker/php` and `docker/lambda` both), and is not part of the running stack. MiniStack gets the Docker socket (root on the host, accepted locally). MiniStack starts a provided runtime with the handler `bootstrap` and answers the CORS preflights of an HTTP API itself; `docker/lambda/bootstrap` (local only, never in a package for AWS) and the CORS of the HTTP API in `docker/lambda/deploy.sh` make up for it (the same CORS as the `nelmio_cors` of dev: change both). PHP-FPM of the layer serves as `nobody`: the bootstrap makes it serve as the owner of the mounted repository (ADR 0082), the user of `php`, so that the functions write there the cache and the logs of dev, shared with the command line (with `nobody`, `Kernel::isLambda()` would move them to the `/tmp` of each function). OPcache revalidates the files every 2 s.
- The profiler of the API keeps the history of the requests in `apps/api/var/profiler`, out of the cache, kept by a `cache:clear` (dev only): https://api.local.prospecter.io/_profiler lists the calls, the header `X-Debug-Token-Link` of each response (exposed to the front) opens its profile; `make profiler-clear` empties it.
- Hosts: `/etc/hosts` needs `local.prospecter.io`, `api.local.prospecter.io` and `static.local.prospecter.io` on 127.0.0.1. The self-signed certificate (generated by nginx in `docker/data/ssl`) must be accepted once per host in the browser; Chrome reports requests to a non-accepted host as failed (503 in the network panel).
- The core library is installed by a Composer `path` repository: `apps/api/vendor/prospecter/core` is a symlink to `packages/core`; production builds copy it with `COMPOSER_MIRROR_PATH_REPOS=1` (`docs/monorepo.md`). Never set `"symlink": true` on that repository.
- Flex recipes and shadcn's generator edit files you did not ask for (a PostgreSQL service in `docker-compose.yml`, broken imports): review `git status` after installing anything.
- Files created in containers belong to the host user (UID/GID exported by the Makefile). Root-owned leftovers: `docker run --rm -v $PWD:/app alpine rm -rf /app/<path>`.
