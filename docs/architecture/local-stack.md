# The local stack

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

Everything runs in Docker, driven by make (ADR 0015, 0080). MiniStack emulates the AWS services (ADR 0048). The API and the storage run only in their Bref Lambdas on MiniStack (ADR 0055). The command line runs on the Lambdas' PHP (ADR 0056). nginx serves the hosts. The commands are listed in `commands.md`, and the traps in the Gotchas of the root `CLAUDE.md`. `local-vs-aws.md` describes how the stack differs from an instance on AWS.

## A stack per checkout

- The stack is served under `.localhost` names (ADR 0126):
  - `prospecter.localhost` serves the SPA;
  - `api.prospecter.localhost` serves the API, its doc, and MiniStack's Cognito under `/cognito/`;
  - `static.prospecter.localhost` serves the files.

  Chrome, Firefox and curl resolve these names to the loopback themselves, so nothing goes in `/etc/hosts`. nginx knows them through `NGINX_HOST` (`docker-compose.yml`). The journeys run in the stack's network and resolve these names to its nginx (`apps/front/Makefile`). Node does so through its container's `/etc/hosts` (`--add-host`). Chromium resolves a `.localhost` name to its own loopback whatever that file says. So it uses a resolver rule instead (`--host-resolver-rules`, with nginx's address in `STACK_ADDRESS`, `playwright.config.ts`). MiniStack is told that it is reached as `ministack` (`MINISTACK_HOST`). With its default, `localhost`, it took the first label of a `.localhost` host for a virtual-hosted bucket, because the static host passes it the private files under their own host.

- Each checkout runs its own stack (ADR 0116). Its Compose project is named after its directory: `prospecterapp` for the main checkout, a number for a worktree. Each stack has its own network, its own `ministack-data` volume and its own Lambdas, which mount its code. No container has a fixed name.
  - In the main checkout, make adds `docker-compose.host.yml`. This file publishes the host's ports 80, 443 and 4566: https://prospecter.localhost, and MiniStack for the IDE.
  - In a worktree, make adds `docker-compose.agents.yml`. This file publishes the worktree's hosts on the worktree's own port. It publishes no port for MiniStack.
  - A worktree's port is the first one from 8443 that no other worktree holds. The worktree keeps it in its `.stack-port` file (`make/stack-port.sh`). make chooses it under a lock in the common git directory, so two worktrees that start together get two ports. `make start` prints the addresses, for instance https://prospecter.localhost:8444.
  - The worktree's nginx listens on that port (`PUBLIC_PORT`). So the journeys, inside the stack's network (ADR 0084), and Chrome on the host reach the same addresses.
  - The apps give the browser their addresses with the port (`PUBLIC_PORT_SUFFIX`). The variable is empty in their `.env`, and `docker/lambda/deploy.sh` passes it to the functions. The SPA gets the port through `VITE_API_URL` and Vite's live reload.
  - The API's CORS settings accept the SPA on any port. nginx passes a private file's host to MiniStack with its port. The file's signature covers that port.
  - `make stop` stops the checkout's stack. It removes only the functions of the checkout's MiniStack (label `ministack.instance`).
  - `make down` removes a worktree's stack and its data once the worktree is no longer needed. It keeps the caches that the checkouts share.
- The checkouts share the images: `prospecter-<image>:<digest of docker/<image>/ and of UID:GID>` (`make/common.mk`).
  - A checkout that changes an image builds its own tag. The others keep theirs. Run outside make, Compose uses the `local` tag.
  - The checkouts also share the Composer and pnpm downloads, in the `prospecter-composer-cache` and `prospecter-pnpm-store` volumes. These volumes belong to no Compose project (`external` in `docker-compose.yml`). make creates them when they are missing, before its first `docker compose` (`SHARED_CACHES`, `make/common.mk`). So `make down` keeps them, and no stack warns that they were created for another project. Running Compose by hand needs them, so run make once first.
  - Each stack keeps its own MiniStack data.
  - An old tag stays until `make images-prune`, which runs on demand only. It removes the `prospecter-php`, `-node`, `-nginx` and `-lambda-deploy` tags that no checkout of `git worktree list` expects from its own `docker/`: those of an old build context, or of a removed worktree. It keeps the `local` tag. It also keeps a tag that a container still uses, and says so.
- A worktree's containers join the systemd slice `prospecter-agents.slice` (`cgroup_parent`, and Playwright's `--cgroup-parent`). `make agents-slice` writes the slice's budget once, with sudo, for all worktrees together: `AGENTS_CPUS` (10 CPUs), `AGENTS_MEMORY_GB` (40), and a `CPUWeight` of 50, behind the rest of the machine. Without the slice, only each container's bounds apply (`cpus`, `mem_limit` in `docker-compose.agents.yml`, Playwright's `E2E_BOUNDS` in `apps/front/Makefile`). Each bound is about twice the peak measured during a whole `make ci` with the journeys (2026-11-14):
  - php 2 GB (1.1 GB: PHPStan and Rector in 2 workers. As many workers as processors, 12 from the host, doubled its memory and saved no time);
  - node 2 GB (1.1 GB);
  - nginx 64 MB (18 MB);
  - MiniStack 12 GB. It used 5.4 GB alone and over 8 GB beside another worktree, during the reactions to stopping prospecting. It then ran some 85 functions at once and kept them until it stopped. That was before the worker was capped at 10 invocations at once. Since then it uses 3.8 GB (`make check-e2e FRESH=1`, 2026-11-16, ADR 0131). `make check-e2e FRESH=1` restarts it (ADR 0121);
  - the tests' MiniStack 512 MB (280 MB);
  - the deployment 1 GB and 2 processors (460 MB, 7 processors);
  - Playwright 3 GB and 4 processors (1.8 GB, 5 processors).

  At its peak, during the journeys that run alone, a worktree takes some 9.5 GB. 3.6 GB of it are in its functions, outside the slice. The worktrees compete for processors first: the PHP checks of one `make ci` take all 10 of the slice. MiniStack 1.5.27 passes the functions it starts only their memory (`--memory=1g` in `LAMBDA_DOCKER_FLAGS`, AWS's 1024 MB). It passes neither their CPU nor their cgroup. So the functions stay outside the slice. They are kept warm for 10 minutes (`LAMBDA_WARM_TTL_SECONDS`) instead of the main checkout's hour. A memory limit would derive a cap on the functions running at once. Beyond that cap, MiniStack refuses an invocation without waiting, and an invocation made by a rule would be lost. So `LAMBDA_ACCOUNT_CONCURRENCY` is 0, unbounded.

## The certificates

- One local authority per machine signs the local hosts' certificate (ADR 0123).
  - `make ca` (`docker/nginx/local-ca.sh`, openssl in the nginx image) creates the authority once in the main checkout's `docker/data/ssl`, whichever checkout runs it (`LOCAL_CA_DIR`, `make/common.mk`).
  - The authority is `ca.pem`. It is valid twenty years. It has the `basicConstraints` CA with no intermediates, and a critical name constraint that permits only `prospecter.localhost` and its subdomains (ADR 0126, revising ADR 0123). Its key, `ca-key.pem`, has mode 0600 and belongs to the host user.
  - The certificate it signs goes in `signed/` (`project.crt`, `project.key`). It is valid ten years, for `prospecter.localhost`, `api.prospecter.localhost` and `static.prospecter.localhost`.
  - Run again, `make ca` keeps both. It replaces an authority whose constraint does not permit `prospecter.localhost`: one made for the old names, to import in its place. It signs again only when the certificate is missing or no longer comes from the authority.
  - Chrome limits to 398 days only the certificates of public authorities, not those of an authority imported by hand.
- Every checkout's nginx mounts that `signed/` read-only, never the authority's key. It serves the signed certificate when it is there (`docker/nginx/05-generate-certificate.sh`, linked in `/etc/ssl/served`).
  - Otherwise it serves a self-signed certificate of its checkout. It generates this certificate in its `docker/data/ssl/certs` on the first start. Meanwhile Compose creates an empty `signed/` owned by root, and `make ca` gives it back to the host user.
  - A port is not part of a certificate's names. So once the authority is imported in the browser ([Trusting the local authority](#trusting-the-local-authority)), every worktree's stack is trusted too.
  - Nothing in `docker/data` is committed (`.gitignore`). The repository is public, and whoever held the key could sign certificates that the browser accepts for these hosts.

### Trusting the local authority

Create the authority once per machine and import it once in Chrome. Chrome then trusts every checkout's stack without a warning: the main checkout on 443 and each worktree on its own port.

1. Run `make ca`, from the main checkout or a worktree. It creates the authority in the main checkout's `docker/data/ssl` (`ca.pem`, valid twenty years, with its key `ca-key.pem` readable by you alone). It then creates the certificate it signs for `prospecter.localhost`, `api.prospecter.localhost` and `static.prospecter.localhost` (`docker/data/ssl/signed`, valid ten years). Run again, it keeps both. It replaces an authority that may not sign for `prospecter.localhost` (the old one, made for other names): import the new one in its place.
2. Import the authority in Chrome. Chrome keeps it in its own store, per profile, not in the system's store: `chrome://certificate-manager` › Local certificates › Installed by you › Trusted certificates › Import, then choose `docker/data/ssl/ca.pem`. Then quit Chrome entirely (Ctrl+Q), not only its window. Until then, it keeps the exceptions accepted for the self-signed certificate and its connections, and still says "Not secure".
3. Restart each stack (`make stop`, `make start`). nginx serves the signed certificate when it exists, the self-signed one otherwise. The port makes no difference.

Firefox keeps its own authorities: Settings › Privacy & Security › Certificates › View Certificates › Authorities › Import.

Without `make ca`, nginx falls back to a self-signed certificate. The browser then warns once for each host (`prospecter.localhost`, `api.prospecter.localhost` and `static.prospecter.localhost`), and again for each worktree's port.

The authority may sign only for `prospecter.localhost` and its subdomains. This critical name constraint, which browsers enforce, keeps it from vouching for any other site. Its key never leaves the machine: never share it or commit it (`docker/data` is ignored by git, and the repository is public). Whoever holds the key can sign certificates that your browser accepts for these hosts. If the key is lost or leaked, remove `docker/data/ssl/ca.pem`, `ca-key.pem` and `signed/`, run `make ca` again, and import the new authority in place of the old one.

## The API

- The API runs in the dev environment, in a Lambda with the Bref layer (FPM runtime, `bref/bref`), behind a MiniStack HTTP API. The repository is mounted in the Lambda (ADR 0053, 0055; `make start`).
  - `php/conf.d/bref.ini` enables the extensions that the layer ships disabled (`intl`). A new extension that the API needs goes there too.
  - `TRUSTED_PROXIES` lets Symfony trust the gateway's `X-Forwarded-Proto`. It is `REMOTE_ADDR` in the function, and empty for the tests.
  - The functions write the dev cache and logs in the repository, with PHP-FPM serving as the repository's owner (`docker/lambda/bootstrap`).
  - The dev profiler keeps its profiles in `var/profiler`, outside the cache, so a `cache:clear` (`make db`) keeps the history. Read it on https://api.prospecter.localhost/_profiler, or through a response's `X-Debug-Token-Link` in the network panel. `make profiler-clear` empties it.
  - The tests run on the command line of the `php` container.
- The outbox relay (ADR 0098) is the `prospecter-dev-relay` function (`BREF_RUNTIME=function`, the `App\Shared\Messaging\OutboxRelay` service).
  - A mapping on each table's stream calls the relay, and the relay publishes on MiniStack's `prospecter-dev-integration` bus.
  - `docker/lambda/deploy.sh` deploys it with the API. `make db` deploys it again (`deploy.sh relay`), because recreating the tables recreates their streams.
  - MiniStack neither splits a failing batch nor stops retrying it. So a record is kept for a minute at most (`--maximum-record-age-in-seconds 60`). Then its batch goes to the `prospecter-dev-relay-failures` queue.
  - The delivery is `outbox` (`apps/api/.env`, ADR 0101). A command writes its `OUTBOX#` item. The relay logs each published event with its `eventId` and its correlation in the dev logs (`apps/api/var/log/dev.log`). The reactions run in the worker about a second later.
- The worker (ADR 0101) is the `prospecter-dev-worker` function (`BREF_RUNTIME=function`, the `App\Shared\Messaging\QueuedIntegrationEventConsumer` service). This service hands what it receives to `IntegrationEventConsumer`, the AWS handler.
  - The worker runs at most 10 invocations at once (`deploy.sh`'s `WORKER_CONCURRENCY`, its reserved concurrency, ADR 0131).
  - Each subscription is in `apps/api/config/subscriptions.json`, which is mounted in the `lambda-deploy` image and read with `jq`. For each subscription, a bus rule puts its invocation in the `prospecter-dev-worker-invocations` queue, through AWS's input transformer. That queue's mapping gives the invocations to the worker one at a time, as many at once as its concurrency allows.
  - The queue is needed because MiniStack 1.5.27 refuses an invocation beyond the reserved concurrency without waiting. It also invokes a rule's target without Lambda's retries or destination. So a rule's invocation refused by the cap would be lost.
  - When the worker throws, the message is received again after the queue's visibility timeout (30 s). At its third receive, it is kept in the `prospecter-dev-integration-failures` queue (`RetriesExhausted`). That queue also keeps what the worker refuses and what Lambda gives up on.
  - The worker gets Lambda's retries and destination when it is invoked asynchronously and directly (`aws lambda invoke --invocation-type Event`, `app:integration:redrive`).
  - Before recreating the tables, `make db` empties the failure queues and the invocations queue (`deploy.sh purge`), because their messages are about the data being destroyed. Otherwise `app:consistency:check` would count failures on data that no longer exists.
  - `deploy.sh relay` deploys the worker, its queue and its rules first, before the relay publishes.
  - A container that does not start within the time MiniStack waits for the Docker daemon fails its invocation. A gesture with many reactions starts several containers at once, and the daemon then answers beyond its default 10 s. Hence `docker-compose.yml` raises `MINISTACK_DOCKER_TIMEOUT`.
  - A function's first call takes some 16 seconds. So `make e2e` first warms the relay and as many workers as their concurrency allows (`deploy.sh warm`): the journeys count on a reaction within about a second.
  - The local bootstrap starts PHP from the code's root, as AWS does. For a function of the `function` runtime, it starts PHP as the repository's owner.

- The MCP server (`mcp.md`) is `/mcp` on the API's host, in the same Lambda. It is reached like the API.
  - The AIs sign in on the sign-in page of MiniStack's Cognito (ADR 0157). nginx passes the browser's requests to `https://api.prospecter.localhost/cognito/oauth2/authorize` (`GET`) and `/login` (`POST`). The sign-in form posts to `/login` on the host that serves it. nginx also passes the calls that carry `X-Amz-Target` under `/cognito/`.
  - The API calls MiniStack's token endpoint directly (`COGNITO_OAUTH_URL`).
  - The facade names the API without a worktree's port. So a client following it needs the main checkout's stack, on 443.

## The SPA

- nginx passes `https://prospecter.localhost` to Vite's dev server (`templates/front.conf.template`). Vite answers one request per module, about 150 for a page load. A connection serves 100 000 requests (`keepalive_requests`). With nginx's default of 1000, a journey that reloaded its pages crossed the limit. nginx closed the connection, and the modules requested meanwhile failed in the browser (`net::ERR_FAILED`, never reaching nginx). The page was left blank.

## The storage

- Both entry points reach the app in its `prospecter-dev-storage` Lambda, through `docker/nginx/storage-lambda.conf` (ADR 0054, 0055). The Lambda mounts the repository and uses Bref's PHP and imagick layers, with `BREF_BINARY_RESPONSES` for the images.

## The journeys' doubles

- nginx serves the Google double (ADR 0120, `conf.d/google-double.conf`) on the Docker network (`http://nginx:8082`):
  - its token endpoint and its revocation;
  - its calendar (ADR 0127). It accepts every push and every removal, and keeps nothing. It answers one Meet link, `https://meet.google.com/double-of-prospecter`, to every event that asks for a conference (ADR 0137);
  - the Gmail watch. It grants `gmail.readonly` with the calendar, watches a mailbox until 2100 from history 1, and stops it. Its history holds one message from Ada Lovelace, a demonstration contact. `app:gmail:renew-watches` reads this message and imports it once as an exchange (ADR 0154). No notification is ever pushed to the local stack.

  The double grants every code and every refresh the tokens of the `agenda@solean-it.io` account. Its authorization page is on the API's host (`/google/authorize`, `templates/api.conf.template`). It sends the browser straight back to the SPA with a fixed code and the `state` it brought. `make google-down` makes the double answer 503 to every call, as if Google were out of reach, until `make google-up`. The local stack and the journeys talk to the double, because `apps/api/.env` names it. The double's client serves the demonstration's SOLEAN IT account (`DemoData`, ADR 0125). A developer who tries a real Google Cloud client names Google's addresses in `apps/api/.env.local` and configures the client as an account administrator. The accounts' secrets are in MiniStack's SSM (`SSM_ENDPOINT`, `ACCOUNT_SECRETS_PATH`).
- nginx serves the IGN geocoder double on the Docker network only (`http://nginx:8081/search`, `conf.d/geocoder-double.conf`). `make e2e` points the API at it while the journeys run (`docker/lambda/deploy.sh geocoder <url>`). Then it points the API back at the service named in `apps/api/.env` (ADR 0092, `docs/architecture/tests.md`).
