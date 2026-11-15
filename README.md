# prospecterapp

Prospecting application built with clean architecture: a React single page application, a Symfony API that delivers the business use cases, a storage for the files, and the use cases library itself.

| Path | Content |
|---|---|
| `apps/front/` | React 19 SPA: TypeScript, Vite, TanStack Router and Query, shadcn/ui + Tailwind CSS, MapLibre |
| `apps/api/` | Symfony 8.1 JSON API documented with NelmioApiDocBundle, DynamoDB (one table per bounded context), the members signed in by Cognito; MiniStack emulates both locally |
| `apps/storage/` | Symfony 8.1 app keeping the uploaded files in an S3 bucket (MiniStack locally) and serving the images in named sizes |
| `packages/core/` | `prospecter/core`: the bounded contexts Accounts, PersonalData and Prospecting, pure PHP, described by Gherkin scenarios |
| `docker/` | PHP 8.5, Node 24 and nginx images |

Everything runs in Docker: only Docker (with the compose plugin) and make are needed on the host.

Documentation: [what is in force, by subject](docs/README.md), [decision records](docs/adr/README.md), [clean code](docs/architecture/clean-code.md), [core tests](docs/architecture/tests.md), [front architecture](docs/architecture/front.md), [design system](docs/architecture/design-system.md), [authentication](docs/architecture/auth.md), [monorepo](docs/architecture/monorepo.md), [infrastructure](docs/architecture/infra.md), [2026 upgrade notes](docs/upgrade-2026.md). Instructions for AI coding assistants are in the `CLAUDE.md` files, which point to the documents by subject.

## Setup

    make install
    make ca
    make start

`make ca` creates, once per machine, the local authority signing the certificate of the local hosts; import it once in Chrome ([Certificat local](#certificat-local)) and no stack shows a certificate warning. The hosts are names in `.localhost`, which Chrome, Firefox and curl resolve to the loopback themselves: nothing to add to `/etc/hosts`. A stack whose data date from the former hosts (`local.prospecter.io`) keeps them in the addresses of its files: `make db` gives it those of the demonstration again, and the lines of those hosts can leave `/etc/hosts`.

- Application: https://prospecter.localhost, sign in with `prospector@solean-it.io` / `password` (or `assistant@solean-it.io`, who sees the contacts masked)
- API documentation: https://api.prospecter.localhost/api/doc

Without `make ca`, nginx falls back to a self-signed certificate: the browser then warns once for each host (`prospecter.localhost`, `api.` and `static.prospecter.localhost`), and again for the port of each worktree.

## Certificat local

A local authority, created once per machine, signs the certificate of the local hosts; imported once in Chrome, it makes it trust the stack of every checkout, the main one on 443 and each worktree on its own port, without a warning.

1. `make ca`, from the main checkout or a worktree: it creates the authority in `docker/data/ssl` of the main checkout (`ca.pem`, valid twenty years, its key `ca-key.pem` readable by you alone), then the certificate of `prospecter.localhost`, `api.prospecter.localhost` and `static.prospecter.localhost` it signs (`docker/data/ssl/signed`, valid ten years). Run again, it keeps both, and replaces an authority that may not sign for `prospecter.localhost` (the former one, made for other names): import the new one in place of it.
2. Import the authority in Chrome, which keeps it in its own store, per profile, not in the one of the system: `chrome://certificate-manager` › Local certificates › Installed by you › Trusted certificates › Import, choose `docker/data/ssl/ca.pem`. Then quit Chrome entirely (Ctrl+Q), not only its window: until then it keeps the exceptions accepted for the self-signed certificate and its connections, and still says "Not secure".
3. Restart each stack (`make stop`, `make start`): nginx serves the signed certificate when it exists, the self-signed one otherwise. A port changes nothing to it.

Firefox keeps its own authorities: Settings › Privacy & Security › Certificates › View Certificates › Authorities › Import.

The authority may sign only for `prospecter.localhost` and its subdomains: a critical name constraint, which a browser enforces, so that it vouches for no other site. Its key never leaves the machine: it is never shared, nor committed (`docker/data` is ignored by git; the repository is public), since whoever holds it signs certificates your browser accepts for these hosts. Lost or leaked, remove `docker/data/ssl/ca.pem`, `ca-key.pem` and `signed/`, run `make ca` again, and import the new authority in place of the former one.

## Develop

`make` (or `make help`) lists every command.

| Command | Purpose |
|---|---|
| `make start` / `make stop` / `make logs` | Run the stack (the front reloads on save) |
| `make deploy` | Redeploy the Lambdas of the API and the storage (done by `make start`) |
| `make test` | core business rules (Behat) and unit tests, API, storage and front tests |
| `make test-core` / `make test-api` / `make test-storage` / `make test-front` | One of them |
| `make e2e` | Playwright journeys against the running stack |
| `make ci` | What the continuous integration runs: build, lint, tests, contract, build of the front |
| `make lint` / `make fix` | PHPStan, php-cs-fixer, Rector, Biome, TypeScript (and Terraform for `make lint`) |
| `make api-doc` | Regenerate the OpenAPI contract of the API and the front API types after an endpoint change |
| `make db` | Reset the tables and the user pool of the API and the bucket of the storage, with the data and the members of the demonstration |
| `make infra-plan APP=api INSTANCE=dev` / `make infra-apply …` / `make infra-lint` | Terraform of an app on AWS ([infrastructure](docs/architecture/infra.md); its values are internal configuration) |
| `make package-<api\|storage\|front>` / `make release-front INSTANCE=dev` / `make console INSTANCE=dev CMD=…` | package an app for AWS, put the release of the SPA, a command of the api on an instance ([infrastructure](docs/architecture/infra.md)) |
| `make hooks` | Install the pre-commit hook that refuses secrets and AWS identifiers (done by `make install`) |
| `make commands` | List the commands of the core and their handlers |
| `make exec-api` / `make exec-front` | Shell in the PHP or Node container |

Local overrides go in `apps/<app>/.env.local`. Addresses are geocoded by the French State service (IGN Géoplateforme): free, no key, addresses in France only.

## Contributing

Work starts from `dev` on a `feature/<name>` or `fix/<name>` branch and comes back by pull request, merged by rebase once the continuous integration (GitHub Actions, `make ci`) passes. `dev` goes to `main` by a pull request merged by a merge commit, a release. A `hotfix/<name>` starts from `main`, comes back the same way, then is cherry-picked onto `dev`. `main` and `dev` accept no direct push ([ADR 0057](docs/adr/0057-continuous-integration-around-dev.md), [0058](docs/adr/0058-main-receives-dev-and-hotfixes-by-merge-commits.md)).

