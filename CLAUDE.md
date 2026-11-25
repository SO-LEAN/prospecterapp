# prospecterapp

A monorepo for a prospecting application built with clean architecture:

| Path | What | Instructions |
|---|---|---|
| `apps/front/` | React 19 SPA (TypeScript, Vite, TanStack Router/Query, shadcn/ui) | `apps/front/CLAUDE.md` |
| `apps/api/` | Symfony 8.1 JSON API documented with NelmioApiDocBundle. It is a resource server for the Cognito user pool members sign in with (MiniStack locally, `docs/architecture/auth.md`) | `apps/api/CLAUDE.md` |
| `apps/storage/` | Symfony 8.1 app that keeps uploaded files in an S3 bucket and serves them in named sizes (`static.prospecter.localhost`) | `apps/storage/CLAUDE.md` |
| `packages/core/` | `prospecter/core`: the bounded contexts Accounts, PersonalData, Prospecting, Reporting, Connectors, Campaigns and Booking and their shared kernel (pure PHP), tested by Behat scenarios | `packages/core/CLAUDE.md` |

## What is in force

Prospecter follows Domain-Driven Design: read `docs/architecture/ddd.md` first, to read the code in its terms.

Before working on a subject, read its document. Read the decision records (`docs/adr`, an immutable journal typed architecture or domain) only to learn why a rule exists, or before changing it. A decision gets a new record (`docs/adr/TEMPLATE.md`) and updates its subjects' documents in the same change (ADR 0090).

@docs/README.md

## Commands

Everything runs in Docker. You only need Docker and make on the host. Run these from the repository root (`make` alone lists them). The full reference, with every flag, the infrastructure and packaging targets, and the console commands (`app:*`), is in [`docs/architecture/commands.md`](docs/architecture/commands.md).

- `make install`: builds the images, installs the dependencies, and creates MiniStack's tables, user pool and bucket with the demonstration data.
- `make start` / `make stop`: start or stop the checkout's stack. A worktree gets its own port, which `make start` prints. `make down` removes a worktree's stack. Sign in at https://prospecter.localhost as `prospector@solean-it.io` / `password` (or `assistant@…`, `newcomer@…`, see `commands.md`).
- `make ci`: runs what the CI runs, the checks that the changes since `origin/dev` need. `ALL=1` runs all of them. It needs no stack and runs no journeys.
- `make check-e2e`: runs the journeys in a worktree, on its running stack given the demonstration back (ADR 0205). `FRESH=1` starts from nothing, as do a stack that is not running and a change of what defines it.
- `make check-<secrets|infra|core|api|storage|front|contract|event-storming|e2e>`: runs one check.
- `make test`, `make test-core`, `make test-<core|api|storage|front> FILTER=<name>`: run the tests. `FAILED=1` reruns what failed. `FAIL_FAST=1` stops at the first failure. `CLOCK_SHIFT=+365d` runs them on a clock one year ahead.
- `make e2e`: runs Playwright on the checkout's running stack.
- `make lint` and `make fix`, both with `FILTER=<paths from the root>`.
- `make adr-index`: run it after adding a record or changing its status, and after rebasing a branch that touches the index. Commit the index when it changed.
- `make api-doc`: run it after changing an endpoint, then commit the contract and the front's types.
- `make event-storming`: run it after changing the core or the API, then commit every context's wall (`docs/architecture/event-storming.md`). `make ci` fails while a wall differs from the code.
- `make db`: recreates the local tables, user pool and bucket (destroys local data).
- `make console CMD="..." [INSTANCE=<instance>]`: runs an API console command, locally or on an AWS instance.

Before declaring work done, `make ci` must pass (lint, tests, contract, front build). A change to the core, the apps or the stack must also pass the journeys. Run `make check-e2e` in a worktree, or `make e2e` on the running stack in the main checkout. When something fails, fix it and rerun only that (`FILTER`, `FAILED=1`), never the whole `make ci`.

## Code conventions

- Clean code as taught by Robert C. Martin: read `docs/architecture/clean-code.md` before writing or reviewing code, and leave the code cleaner than you found it. In short: native types everywhere. No comment that repeats a type or explains the code: name the intention instead (extract a function, a constant). Short methods with one level of abstraction. Command-query separation. Docblocks only for what the language cannot express (generics, array shapes). Dependencies are injected, never built (ADR 0044, a PHPStan rule in `packages/phpstan-rules`). PHP: readonly DTOs, constructor promotion. TypeScript: strict, no `any`. The guide's **Known debt** lists are the backlog for the improvement iterations.
- Write plain, idiomatic technical English: short subject-verb-object sentences, one idea per sentence, no ellipsis or inversion, active voice, possessives and compound nouns rather than chains of `of` ("each account's figures", not "the figures of each account"). Avoid the false friends listed in `docs/architecture/clean-code.md` (section 14): `consult`, `civility`, `make conform`, `actual` for "current", `eventually` for "possibly". A branch that touches a doc, a docblock or a name moves it to this style; code is renamed only when touched, never in a global pass (ADR 0188). The tickets, the Gherkin scenarios and the interface's labels stay in French.
- Do not trust existing docblocks blindly: some were wrong (a `@var stdClass` led Rector to produce a broken type).
- Business rules belong in `packages/core`, described by its Gherkin scenarios (`docs/architecture/tests.md`). The API only adapts (HTTP, DynamoDB, storage, authentication); the front only presents.
- One invokable controller per API endpoint, named `<Intent>Controller` and grouped by bounded context (`src/<Context>/Controller`). URLs are resource-oriented.

## Git

- A `feature/<name>`, `fix/<name>` or `refactor/<name>` branch starts from `dev` and comes back to `dev` through a pull request. The pull request is merged by rebase once the CI passes. `main` receives `dev` through a merge commit. `main` and `dev` are protected: never push to them (ADR 0057, 0058). `docs/architecture/workflow.md` describes the whole workflow, its CI and its Terraform.
- Write conventional commits (`feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, and `test:` for a red test committed alone before the code that makes it pass). The subject is imperative (`add`, `fix`, `remove`) and 72 characters at most. The body gives the why.
- Commit or push only when asked.

## Gotchas

- The repository is public. It describes the platform, never an installation of it (ADR 0059). No commit holds an AWS account id, an ARN, a state bucket name, an account email or a secret, not even an intermediate commit. Nothing in it says how the AWS accounts are organized. Read `git diff --cached` before each commit. The pre-commit hook (`make hooks`) runs gitleaks. Never `git add -f` an ignored file.

- MiniStack (the `ministack` service, ADR 0048) emulates the AWS services locally: DynamoDB and Cognito for the API, S3 for the storage (ADR 0051, 0052). Its state lives in the `ministack-data` volume. The host (IDE, AWS CLI) reaches the main checkout's MiniStack at `http://localhost:4566`, in region `eu-west-3`, with the keys `test`/`test`. After `docker compose up -d` recreates `node`, restart `nginx`. Otherwise nginx keeps the old address for the SPA host and answers 502. The API and static hosts resolve MiniStack on each request.
- Cognito (ADR 0052): MiniStack generates random ids for the user pool and its client. The API finds them by name and caches them. After the `ministack-data` volume is removed, run `make db`. The SPA reaches MiniStack's Cognito through `https://api.prospecter.localhost/cognito/` (nginx only passes its calls through).

- The API and the storage run only in their Bref Lambdas on MiniStack (ADR 0055), with the repository mounted in them. `make start` (or `make deploy`) deploys them behind their HTTP APIs. The `php` container serves the command line only (tests, Composer, console). It runs the Lambdas' PHP (Bref's dev image, ADR 0056). A new Bref version goes in both `docker/php` and `docker/lambda`. The `php` container is not part of the running stack. MiniStack gets the Docker socket, which gives it root on the host. That is accepted locally.
  - MiniStack starts a provided runtime with the handler `bootstrap` and answers an HTTP API's CORS preflights itself. `docker/lambda/bootstrap` (local only, never in a package for AWS) and the HTTP API's CORS in `docker/lambda/deploy.sh` make up for this. These CORS settings match the dev `nelmio_cors`: change both together.
  - The layer's PHP-FPM serves as `nobody`. The bootstrap makes it serve as the owner of the mounted repository (ADR 0082), the same user as `php`. So the functions write the dev cache and logs in the repository, and share them with the command line. As `nobody`, `Kernel::isLambda()` would move them to each function's `/tmp`.
  - OPcache revalidates the files every 2 s.
- In dev only, the API's profiler keeps the request history in `apps/api/var/profiler`. That folder is outside the cache, so a `cache:clear` keeps it. https://api.prospecter.localhost/_profiler lists the calls; each response's `X-Debug-Token-Link` header (exposed to the front) opens its profile. `make profiler-clear` empties it.
- Hosts: the stack is served under `.localhost` names, `prospecter.localhost`, `api.prospecter.localhost` and `static.prospecter.localhost` (ADR 0126). The browser resolves them to the loopback itself: nothing goes in `/etc/hosts`. Inside the stack's network, `make e2e` resolves them to its nginx. For Node, the Playwright container's `/etc/hosts` does it. Its Chromium ignores that file for these names, so a resolver rule does it.
  - For every checkout's stack, whatever its port, nginx serves the certificate that the machine's local authority signed (`make ca`, ADR 0123, in the main checkout's `docker/data/ssl`). Import the authority `ca.pem` once in the browser (`docs/architecture/local-stack.md`, "Trusting the local authority"). Without it, nginx generates a self-signed certificate that you must accept once per host and per port. Chrome reports requests to a host it has not accepted as failed (503 in the network panel). Never commit or share the authority's key.
- The core library is installed through a Composer `path` repository: `apps/api/vendor/prospecter/core` is a symlink to `packages/core`. Production builds copy it with `COMPOSER_MIRROR_PATH_REPOS=1` (`docs/architecture/monorepo.md`). Never set `"symlink": true` on that repository.
- Flex recipes and shadcn's generator edit files you did not ask for (a PostgreSQL service in `docker-compose.yml`, broken imports, the framework-bundle recipe's generic `AGENTS.md`). That `AGENTS.md` was removed: the `CLAUDE.md` files hold the rules. Review `git status` after installing anything.
- Files created in containers belong to the host user (UID/GID exported by the Makefile). To remove root-owned leftovers: `docker run --rm -v $PWD:/app alpine rm -rf /app/<path>`.
