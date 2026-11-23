# Commands

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

Everything runs in Docker: MiniStack with the Lambdas, Node 24, nginx, and the Lambdas' PHP 8.5 for the command line (ADR 0056). You only need Docker and make on the host. Each project has its own `Makefile`. You run it from the project's directory (`cd apps/api && make test`). It includes only the fragments of `make/`. The root `Makefile` is the façade: it runs the projects' targets and holds only what spans several of them (ADR 0080). A command that belongs to one project goes in that project's Makefile, and then in the façade under its former name.

Run these from the repository root. `make` alone lists the commands. A new target gets a `## description` on its line.

## Setting up and running the stack

- `make install`: builds the images, installs the Composer and pnpm dependencies, and creates the API's DynamoDB tables and Cognito user pool and the storage's S3 bucket in MiniStack, with the demonstration data and members.
- `make start` / `make stop`: start or stop the checkout's stack. The API and the storage are deployed in their Lambdas on MiniStack (ADR 0055).
  - A worktree runs its own stack, on its own port, which `make start` prints (for instance https://prospecter.localhost:8444). Its containers run within the budget that `make agents-slice` sets.
  - `make down` removes a worktree's stack once you are done with the worktree, keeping the caches the checkouts share (ADR 0116).
  - `make images-prune` removes the images no checkout expects any more.
  - https://prospecter.localhost serves the SPA. Sign in as:
    - `prospector@solean-it.io` / `password`;
    - `assistant@solean-it.io` / `password`, a prospector without the right to manage personal data;
    - `newcomer@solean-it.io` / `password`, a prospector with an empty profile, completed at sign-in.
  - https://api.prospecter.localhost/api/doc serves the API doc.
- `make db`: recreates the dev tables, the API's user pool and the storage's bucket in MiniStack. **This destroys local data.** It then:
  - empties the relay's and the worker's failure queues, whose messages refer to the destroyed data;
  - deploys the worker, the subscription rules and the outbox relay again, on the new tables' streams (ADR 0098, 0101);
  - rebuilds the contacts' tags (`app:tags:rebuild`, ADR 0141);
  - counts the demonstration's figures (`app:reporting:rebuild`, five minutes, ADR 0104, 0106).

## Checks

- `make ci`: runs what the CI runs. It runs the checks that the changes since `origin/dev` need, and `ALL=1` runs every one (ADR 0110). It builds the images, installs the dependencies and checks the contract. Then it runs each project's lint and tests together and builds the front (ADR 0109). It needs no stack: the API and storage tests run on the checkout's own MiniStack. It stops at the first failure. It does not run the journeys (ADR 0121).
- `make check-e2e`: runs the journeys in a worktree once development is done. On the worktree's running stack, it first deploys the API and the storage again, recycles the relay and the worker, and gives the tables and the user pool the demonstration back (`make -C apps/api restore`). It starts from nothing when `FRESH=1` is set, when no stack runs, or when what defines the stack changed since it was made. The stack is then started again, deployed and its data recreated. Each step prints how long it took (`Time/<step>: <n> s`). Only one checkout at a time runs them, and the first failure stops them (ADR 0117, 0121, 0205).
- `make check-<secrets|infra|core|api|storage|front|contract|e2e>`: runs one check. `make -j` runs its steps together.
- `make test`: runs the core's scenarios and unit tests, then the API, storage and front tests.
- `make test-core`: runs the business rules only.
- `make test-<core|api|storage|front> FILTER=<name>`: runs only the matching scenarios and tests, or the front's matching test files.
  - `make test`, `make test-api` and `make check-api` leave out the API tests that re-test a core rule (the `core-rule` group, ADR 0204). `ALL=1` runs them too, and so does `make ci ALL=1`. A test named by `FILTER` runs whatever its group.
  - `FAILED=1` reruns what failed last. `FAIL_FAST=1` stops at the first failure. Both also work with `make e2e`.
  - `CLOCK_SHIFT=+365d` runs the core, the storage and the front on a clock one year ahead. The API stays on the real clock (ADR 0150).
- `make e2e`: runs Playwright on the checkout's running stack, once every part of it answers and its functions are warm. It uses doubles of the services outside the stack (ADR 0092).
- `make mutation`: mutates the lines a branch adds since `origin/dev` in the core and the front, and lists the mutants its tests let survive (ADR 0091). The definition of done runs it, with a threshold of 80 % per project (ADR 0202). It prints the number of mutants the core skipped, and fails before the front runs when there is one (ADR 0196). `make mutation-api` does the same for the API, on demand only: it takes minutes per mutant.
- `make lint`: `FILTER=<paths from the root>` lints only those paths, each with its project's tools. The PHP lint caches live in each project's `var/cache/` and take a few seconds once warm (`docs/architecture/monorepo.md`).
- `make fix`: fixes what the linters can fix.

## Decision records and the API contract

- `make adr-index`: after you add a decision record or change its status, it writes the record's line in the index `docs/adr/README.md` from the record's header. `make ci` fails while they differ. Two branches that each add a record merge their lines without a conflict (ADR 0128). After you rebase a branch that touches the index, run it again before pushing: the union replays the lines the branch changed (`docs/architecture/workflow.md`). Commit the index when it changed.
- `make api-doc`: after you change an endpoint, it regenerates `apps/api/openapi.json` and the front's API types. `make test` fails while the committed contract differs from the code.

## Infrastructure on AWS

- `make infra-plan APP=<api|storage|front> INSTANCE=<dev|prod|...>` / `make infra-apply …`: plan or apply an app's Terraform on AWS (`apps/<app>/infra`, ADR 0059, `docs/architecture/infra.md`). The instance's values live in `envs/<instance>.{tfvars,tfbackend,env}` of `INFRA_CONFIG`, outside the repository. `INFRA_CONFIG` defaults to `../prospecterapp-infra` next to the main clone, even from a worktree.
- `make package-api` / `make package-storage`: package the app for its Lambda (`build/<app>.zip`, ADR 0063). The package is built before an apply.
- `make package-front`: packages the SPA as a release (`build/front`). `make release-front INSTANCE=<instance>` uploads it before the apply (ADR 0066).
- `make infra-lint`: lints the Terraform (part of `make lint`).

## Console commands

`make console CMD="..." [INSTANCE=<instance>]` runs an API command on MiniStack. With `INSTANCE`, it runs it on an AWS instance, with the credentials of that instance's AWS profile. It refuses to run while the local code lacks changes that the instance has deployed (`origin/dev`, or `origin/main` for prod); `FORCE=1` runs it anyway.

Each of the following commands runs one use case. A member completes their profile at their first sign-in (ADR 0100).

- `app:account:open <corporate name> <login email>`: opens an account and its founder (ADR 0064).
- `app:member:register <login email of an administrator> <login email>`: registers a member in the administrator's account, as a prospector unless `--personal-data` or `--admin` is given (ADR 0075).
- `app:campaign:erase <id>`: erases a campaign whatever its status, to clean up after a test or to answer a person's request.
- `app:demo:restore`: gives the local tables and user pool the demonstration back without recreating them. It empties the tables, loads the demonstration again and gives its members back. It deletes the users and the clients added since. `make check-e2e` runs it through `make -C apps/api restore`. **This destroys local data.**
- `app:integration:redrive`: hands the events in the failure queue back to the worker (ADR 0101).
- `app:organizations:locate-again`: a one-time command. It geocodes again the organizations that were located before their municipality was stored. Then run `app:tags:rebuild` to give them their geographic tags (ADR 0142).
- `app:reporting:rebuild`: recounts every account's figures and says how many accounts it counted, after five minutes of waiting (ADR 0106, 0182). Run it on an instance where Reporting is new, after `app:keys:rebuild` (ADR 0104).
- `app:tags:rebuild [--account=<id>]`: records again the facts that the contacts' tags derive from, without waiting (ADR 0141). Run it on an instance once Campaigns derives them.
- `app:scheduled-tasks:run`: runs every scheduled task, one after the other. This is what an instance's daily schedule does; run it by hand locally (ADR 0161). Each task can also run alone:
  - `app:contacts:erase-unattached`: erases the contacts to attach that nobody attached within 30 days of their last meeting (ADR 0146, 0147);
  - `app:gmail:renew-watches`: renews the mailbox watch of each active Gmail import (ADR 0154).
- `app:consistency:check [--account=<id>]`: a read-only check. It checks the figures, the tags' facts, the reactions between contexts, and the keys that list the accounts and the exchanges against the tables. It also checks contacts that record the same link, and the failure queues. It lists each discrepancy with the command that corrects it, and fails when there is one (ADR 0105).

### Rebuilding the keys

`bin/console app:keys:rebuild` writes again the keys that the tables derive from the aggregates:

- the search keys of organizations and persons (ADR 0071), including a person's first name (ADR 0149; run once on each instance for the contacts stored before it);
- the keys that list accounts, and exchanges by account (ADR 0099);
- the links of exchanges and to-dos in their holders' calendars (ADR 0115, 0134);
- the reservations of the contacts' links (ADR 0159, ADR 0165; run once on each instance for the LinkedIn profiles and pages stored before links existed, which are taken as links);
- the reservations of the organizations' links and website domains (ADR 0160);
- the links of the organizations' resemblance keys (ADR 0162; run once on each instance for the organizations stored before it);
- the links of the contacts' resemblance keys (ADR 0170; run once on each instance for the contacts stored before it);
- the registry of the exchanges' fingerprints (ADR 0166; run once on each instance where e-mails were imported before it).

## Other commands

- `docker compose run --rm --no-deps php <cmd>`: a PHP command, in `apps/api`. Add `-w /var/www/src/apps/storage` or `-w /var/www/src/packages/core` for the other projects.
- `docker compose run --rm --no-deps node <cmd>`: a Node command, in `apps/front`.
