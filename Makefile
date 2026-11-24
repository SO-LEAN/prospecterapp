# The façade of the projects (ADR 0080): what one project does is in its own Makefile, run here by its directory;
# only what crosses the projects is written here
include make/common.mk make/aws.mk

# the commits checked for secrets and identifiers of an AWS installation (ADR 0059): the repository is mounted at
# its own path and the index git prepares for the commit is passed on, a commit -a or by paths using a temporary one;
# the common git directory is mounted at its own path too
GITLEAKS = docker run --rm -u $(UID):$(GID) -e GIT_INDEX_FILE -v $(CURDIR):$(CURDIR) -v $(GIT_COMMON_DIR):$(GIT_COMMON_DIR) \
	-w $(CURDIR) ghcr.io/gitleaks/gitleaks:v8.30.1
INFRA_APPS = api storage front

.PHONY: install hooks build vendor db aws ca front-install check-secrets check-infra check-contract check-core check-api check-storage check-front check-e2e check-e2e-locked check-e2e-run e2e-locked front-lint front-tested-build changed-checks infra-target infra-init infra-plan infra-apply infra-lint changed-apps package-api package-storage package-front release-front console start deploy stop down images-prune agents-slice logs ps google-down google-up ci test test-api test-storage test-front e2e test-core mutation mutation-api lint adr-references adr-index adr-index-check fix front-build api-doc contract event-storming commands profiler-clear exec-api exec-front secrets secrets-branch

##@ Setup
install: hooks build vendor db front-install ## Build the images, install the dependencies, create the tables, the bucket and the user pool, install the Git hooks
build: ## Build the Docker images, those of the command line and of the deployment included
	@echo "Docker/Building images..."
	@$(DC) --profile cli --profile deploy build

vendor: ## Install the Composer dependencies of the API, the core and the storage
	@$(MAKE) -C apps/api vendor
	@$(MAKE) -C packages/core vendor
	@$(MAKE) -C apps/storage vendor

db: ## Recreate in MiniStack the tables and the user pool of the API and the bucket of the storage, with the demo data and its figures counted (destroys local data), the queues of the failures emptied, the relay of the outbox on their new streams
	@$(MAKE) -C apps/api db
	@$(MAKE) -C apps/storage db

front-install: ## Install the pnpm dependencies of the front
	@$(MAKE) -C apps/front install

hooks: ## Install the Git hooks of the repository: gitleaks before each commit
	@git config core.hooksPath .githooks

##@ Stack
aws: ministack ## Start the AWS services emulated locally (MiniStack)

start: deploy ## Start the stack, the API and the storage in their Lambdas: https://prospecter.localhost
	@echo "SPA: https://prospecter.localhost$(PUBLIC_PORT_SUFFIX)  API doc: https://api.prospecter.localhost$(PUBLIC_PORT_SUFFIX)/api/doc"

deploy: ## Start the stack and (re)deploy the Lambdas of the API and the storage on MiniStack
	@$(call timed,Stack/images built and containers started,$(DC) up -d --build)
	@$(call timed,Stack/Lambdas deployed,$(DC) run --rm --build lambda-deploy)

# the functions a MiniStack runs carry its host name: those of the stacks of the other checkouts stay up
STACK_FUNCTIONS = for ministack in $$($(DC) ps -aq ministack); do \
	docker ps -aq --filter label=ministack.instance=$$(docker inspect -f '{{.Config.Hostname}}' $$ministack):4566; done
stop: ## Stop the stack of this checkout, the MiniStack of its tests and the functions its MiniStack runs
	@$(call timed,Stack/stopped,$(STACK_FUNCTIONS) | xargs -r docker rm -f >/dev/null && $(DC) --profile test stop)

down: ## Remove the stack of this checkout, its functions and its data, the caches shared by the checkouts kept: once a worktree is done with (ADR 0116)
	@$(STACK_FUNCTIONS) | xargs -r docker rm -f >/dev/null
	@$(DC) --profile cli --profile test --profile deploy down --volumes --remove-orphans

# the images of the stack by the directory of their build context, and the tags the checkouts of the repository expect
# of them, each by its own docker/ (make/common.mk); the tag local, of a Compose run by hand, kept. On demand only: a
# former tag stays until then, no command of the stack removing it
STACK_IMAGES = php:php node:node nginx:nginx lambda-deploy:lambda
image-name = $(word 1,$(subst :, ,$(1)))
image-context = $(word 2,$(subst :, ,$(1)))
EXPECTED_IMAGES = git worktree list --porcelain | sed -n 's/^worktree //p' | while read -r tree; do \
	$(foreach image,$(STACK_IMAGES),echo "prospecter-$(call image-name,$(image)):$$({ $(call image-digest,"$$tree",$(call image-context,$(image))); } 2>/dev/null)";) done
images-prune: ## Remove the images of the stack no checkout of the repository expects any more: those of a former docker/<image>, of a worktree removed
	@expected=$$($(EXPECTED_IMAGES)); \
	docker image ls $(foreach image,$(STACK_IMAGES),--filter reference=prospecter-$(call image-name,$(image))) --format '{{.Repository}}:{{.Tag}}' \
		| grep -v ':local$$' | grep -vxF "$$expected" | xargs -r -n1 sh -c 'docker image rm "$$0" || echo "Kept $$0: a container uses it"'

# the budget of the stacks of the worktrees together (ADR 0116), out of reach of the agents: a slice of systemd, written
# once with sudo, which the containers of the worktrees join; without it, only the bounds of each stack apply
AGENTS_CPUS ?= 10
AGENTS_MEMORY_GB ?= 40
define AGENTS_SLICE_UNIT
[Unit]
Description=The stacks of the worktrees of Prospecter, where the agents work (ADR 0116)

[Slice]
CPUQuota=$(AGENTS_CPUS)00%
CPUWeight=50
MemoryHigh=$(shell echo $$(( $(AGENTS_MEMORY_GB) * 4 / 5 )))G
MemoryMax=$(AGENTS_MEMORY_GB)G
endef
export AGENTS_SLICE_UNIT
agents-slice: ## Give the stacks of the worktrees their budget, with sudo: AGENTS_CPUS (10) CPUs and AGENTS_MEMORY_GB (40) together, behind the rest of the machine
	@echo "$$AGENTS_SLICE_UNIT" | sudo tee /etc/systemd/system/$(AGENTS_SLICE) >/dev/null
	@sudo systemctl daemon-reload
	@systemctl show $(AGENTS_SLICE) -p CPUQuotaPerSecUSec -p CPUWeight -p MemoryHigh -p MemoryMax

# openssl in the image of nginx, nothing on the host; the authority of the machine, whatever the checkout running it (ADR 0123)
ca: ## Create once the local authority of the machine and the certificate of prospecter.localhost it signs, served by the stack of every checkout: import docker/data/ssl/ca.pem in the browser once (README)
	@$(DC) --progress quiet build nginx
	@docker run --rm -e OWNER=$(UID):$(GID) -e SHOWN_DIR=$(LOCAL_CA_DIR) -v $(LOCAL_CA_DIR):/etc/ssl/local $(NGINX_IMAGE) local-ca

logs: ## Follow the logs of the stack
	@$(DC) logs -f

ps: ## List the containers of the stack
	@$(DC) ps

# the double of Google of nginx answering 503 to every call, as Google out of reach (docker/nginx/conf.d/google-double.conf)
GOOGLE_DOWN_FLAG = /tmp/google-down
google-down: ## Make the double of Google of the running stack stop answering (503), to try the connectors during an outage
	@$(DC) exec nginx touch $(GOOGLE_DOWN_FLAG)

google-up: ## Make the double of Google of the running stack answer again
	@$(DC) exec nginx rm -f $(GOOGLE_DOWN_FLAG)

##@ Quality
# the checks of the integration (ADR 0109, 0110): those the changes of the branch since BASE need, committed or not, a
# new file once known to git or not ignored (make/changed-checks.sh), every one with ALL=1, as the CI on the pushes of
# dev and main. Independent of one another once the images and the dependencies are there and the contract checked (it
# writes the types the front reads), they run together, and so do the lint and the tests of a project, each printing
# its output once it ends, the first failing stopping the others (FAIL_FAST); the CI runs them in jobs of their own
# (.github/workflows/ci.yml). The journeys are neither a check of the CI nor of make ci (ADR 0118, 0121): make check-e2e
# runs them, in a worktree, once its development is done
BASE ?= origin/dev
ALL_CHECKS = secrets infra core api storage front contract e2e
CHANGED_FILES = { git diff --name-only $$(git merge-base $(BASE) HEAD); git ls-files --others --exclude-standard; }
CHECKS = $(if $(ALL),$(ALL_CHECKS),$(shell $(CHANGED_FILES) | make/changed-checks.sh))
ci: export FAIL_FAST = 1
ci: ## Run the checks of the integration the changes since BASE (origin/dev) need, ALL=1 every one: secrets of the branch, Terraform, lint and tests of each project, build of the front, contract; the journeys apart (check-e2e)
	@echo "Checks: $(CHECKS)$(if $(ALL),, (those of the changes since $(BASE); ALL=1 for every one))"
	@$(MAKE) build vendor front-install
	@$(if $(filter contract,$(CHECKS)),$(MAKE) check-contract)
# the MiniStack of the tests, shared by those of the API and the storage: started once, before they would both create it
	@$(if $(filter api storage,$(CHECKS)),$(MAKE) ministack-test)
	@$(MAKE) -j --output-sync=recurse $(addprefix check-,$(filter-out contract e2e,$(CHECKS)))
	@$(if $(filter e2e,$(CHECKS)),echo "These changes need the journeys too: make check-e2e in a worktree once the development is done")

# make -j runs together the prerequisites of each check, and the targets each runs in its project
check-secrets: secrets-branch adr-references adr-index-check ## Check the commits of the branch for secrets, the references to the records, their index
check-infra: infra-lint ## Check the Terraform of the apps
check-contract: contract ## Check the OpenAPI contract and the front types against the code

# The journeys of the machine run one checkout at a time (ADR 0121): beside those of another, they outlast their waits.
# The lock is in the git directory every checkout shares; a run waiting for it says so
E2E_LOCK = $(GIT_COMMON_DIR)/prospecter-e2e.lock
WITH_E2E_LOCK = { flock -n $(E2E_LOCK) true || echo "The journeys of another checkout run: waiting for them..."; } && flock $(E2E_LOCK)

# In a worktree, never on the data of the main checkout (ADR 0118, 0121, 0205). On the running stack, a branch's journeys
# still start from the demonstration. The check deploys the API's and the storage's functions again, recycles the relay
# and the worker, empties the failure queues, and gives the tables and the user pool the demonstration back
# (make -C apps/api restore). It keeps the containers, the images, the relay's mappings, the bus rules and the buckets.
# It starts from nothing with FRESH=1, when no stack runs, or when what defines the stack changed since it was made
# (STACK_DEFINITION). It then starts the stack again (MiniStack without the memory a former run left it), deploys it and
# recreates its data. Either way the command loading the demonstration counts its figures, alone, in a few seconds,
# before any console gesture and any journey (ADR 0209). The journeys running alone run once the others are done
# (ADR 0117), the main journey then the dashboard, one after the other (ADR 0136), and not when one before failed: the
# first failure stops them (FAIL_FAST). It warms the relay and the worker, makes the console's gestures and doubles the
# geocoder once for both projects. Each step prints how long it took, and so does the whole run
STACK_RUNNING = $(shell $(DC) ps -q --status running nginx 2>/dev/null)
# what a running stack's restore does not make again: the containers and their images, the tables, the user pool, the
# buckets, the relay and the subscriptions' rules. The stack keeps the digest of the definition it was made from
STACK_DEFINITION = docker-compose.yml docker-compose.agents.yml docker/php docker/node docker/nginx docker/lambda \
	apps/api/config/subscriptions.json apps/api/src/Shared/Persistence/Tables.php apps/api/src/SignIn/UserPool.php \
	apps/storage/src/Bucket.php
STACK_DEFINITION_DIGEST = $(shell cd $(ROOT) && find $(STACK_DEFINITION) -type f | LC_ALL=C sort | xargs sha256sum | sha256sum | cut -c1-12)
STACK_MADE = $(ROOT)/.cache/stack-made-from
STACK_CHANGED = $(if $(filter $(STACK_DEFINITION_DIGEST),$(file < $(STACK_MADE))),,1)
FROM_NOTHING = $(or $(FRESH),$(if $(STACK_RUNNING),,1),$(STACK_CHANGED))
MAKE_THE_STACK = $(MAKE) stop && $(MAKE) deploy && $(MAKE) db && mkdir -p $(dir $(STACK_MADE)) && echo $(STACK_DEFINITION_DIGEST) >$(STACK_MADE)
RESTORE_THE_STACK = echo "Giving the running stack its demonstration back (FRESH=1 starts from nothing)" \
	&& $(call timed,Stack/API and storage deployed and reactions recycled,$(DC) --progress quiet run --rm --build lambda-deploy apps) \
	&& $(MAKE) -C apps/api restore
check-e2e: ## Run the journeys on this worktree's stack after giving it the demonstration back, stopping at the first failure. FRESH=1, no running stack or a change of what defines it starts the stack again, deploys it and recreates its data first (ADR 0117, 0121, 0205)
	@test -n "$(IN_WORKTREE)" || { echo "check-e2e recreates the data of the stack: in a worktree only; make e2e on the running stack"; exit 1; }
	@$(WITH_E2E_LOCK) $(MAKE) check-e2e-locked FROM_NOTHING=$(FROM_NOTHING)

check-e2e-locked:
	@$(call timed,check-e2e,$(MAKE) check-e2e-run)

check-e2e-run: export FAIL_FAST = 1
check-e2e-run:
	@$(if $(FROM_NOTHING),$(MAKE_THE_STACK),$(RESTORE_THE_STACK))
	@$(E2E_SETUP) && $(call timed,Journeys/together,$(FRONT_E2E) E2E_ARGS="--project='together*'" E2E_RESULTS=together); together=$$?; \
	test $$together -eq 0 || { echo "A journey running together failed: those running alone are not run"; exit $$together; }; \
	$(call timed,Journeys/main journey,$(FRONT_E2E) E2E_ARGS="--project=alone --no-deps" E2E_RESULTS=alone) && \
	$(call timed,Journeys/dashboard,$(FRONT_E2E) E2E_ARGS="--project=alone-dashboard --no-deps" E2E_RESULTS=alone-dashboard)

check-core: ## Lint and test the core
	@$(MAKE) -C packages/core lint test

check-api: ## Lint and test the API, but the tests re-testing a core rule (ALL=1 runs them)
	@$(MAKE) -C apps/api lint test

check-storage: ## Lint and test the storage
	@$(MAKE) -C apps/storage lint test

# the build after the tests: Vitest and the build both generate the tree of the routes
check-front: front-lint front-tested-build ## Lint, test and build the front
front-lint:
	@$(MAKE) -C apps/front lint
front-tested-build:
	@$(MAKE) -C apps/front test
	@$(MAKE) -C apps/front build

CHANGED_RANGE_FILES = git diff --name-only '$(RANGE)'
changed-checks: ## Print as JSON the checks of the integration a git range needs: make changed-checks RANGE=origin/dev...HEAD
	@test -n "$(RANGE)" || { echo "Usage: make changed-checks RANGE=<git range>, such as origin/dev...HEAD"; exit 1; }
	@$(CHANGED_RANGE_FILES) >/dev/null
	@echo '$(call json-strings,$(shell $(CHANGED_RANGE_FILES) | make/changed-checks.sh))'

test: test-core test-api test-storage test-front ## Run every test suite: core, API, storage, front
test-core: ## Run the business rules (Behat) and the unit tests of the core; FILTER= a scenario or a test by name; FAILED=1 those that failed last first, FAIL_FAST=1 stops at the first failure
	@$(MAKE) -C packages/core test

test-api: ## Run the tests of the API (on MiniStack: tables, user pool), but those re-testing a core rule (ALL=1 runs them); FILTER= a test by name; FAILED=1 those that failed last first, FAIL_FAST=1 stops at the first failure
	@$(MAKE) -C apps/api test

test-storage: ## Run the tests of the storage (on MiniStack); FILTER= a test by name; FAILED=1 those that failed last first, FAIL_FAST=1 stops at the first failure
	@$(MAKE) -C apps/storage test

test-front: ## Run the tests of the front (Vitest); FILTER= a test file by its path or a part of it, FAIL_FAST=1 stops at the first failure; TEST_WORKERS= the workers (one processor fewer than the container has by default)
	@$(MAKE) -C apps/front test

# the journeys depend on no service outside the stack: the API asks the double of the geocoder of nginx while they
# run, and the service of the IGN again afterwards, whatever their result. Its containers recycled by the change, as
# many are started again as the journeys ask at once (e2e/wait-for-the-stack.ts: six a page per worker)
GEOCODER_DOUBLE = http://nginx:8081/search
API_FUNCTIONS_AT_ONCE = $$((6 * $${E2E_WORKERS:-2}))
API_GEOCODER = $(DC) --progress quiet run --rm --build lambda-deploy geocoder
# the relay and the worker warmed first: a journey waits a second or so for a reaction
WARM_REACTIONS = $(DC) --progress quiet run --rm --build lambda-deploy warm
# a founder and a member the console opens and registers, their member and profiles written by the worker: a journey
# signs each in (e2e/console-gestures.spec.ts); the registration is tried again until the worker wrote the founder
E2E_RUN := $(shell date +%s)
API_CONSOLE = $(MAKE) -s -C apps/api console CMD=
CONSOLE_GESTURES = $(API_CONSOLE)"app:account:open 'E2E $(E2E_RUN)' founder-$(E2E_RUN)@e2e.test --password=password" >/dev/null \
	&& tries=0 && until $(API_CONSOLE)"app:member:register founder-$(E2E_RUN)@e2e.test member-$(E2E_RUN)@e2e.test --password=password" >/dev/null 2>&1; \
	do tries=$$((tries + 1)); test $$tries -lt 15 || { echo "The founder of the journeys is not a member: is the worker running?"; exit 1; }; sleep 1; done
# the three at once: the double of the geocoder, and the functions of the API it recycled, beside the others; the
# service of the IGN given back once the double is set, whatever stops the journeys
E2E_SETUP = geocoder= && trap 'test -z "$$geocoder" || wait $$geocoder 2>/dev/null; $(API_GEOCODER)' EXIT && trap 'exit 130' INT TERM \
	&& { $(call timed,Journeys/geocoder doubled,$(API_GEOCODER) $(GEOCODER_DOUBLE) $(API_FUNCTIONS_AT_ONCE)) & geocoder=$$!; } \
	&& $(call timed,Journeys/reactions warmed,$(WARM_REACTIONS)) && $(call timed,Journeys/console gestures,$(CONSOLE_GESTURES)) && wait $$geocoder
FRONT_E2E = $(MAKE) -C apps/front e2e E2E_RUN=$(E2E_RUN)
e2e: ## Run the Playwright journeys against the running stack, the API on the double of the geocoder meanwhile, once those of another checkout are done; FAILED=1 those that failed last, FAIL_FAST=1 stops at the first failure
	@$(WITH_E2E_LOCK) $(MAKE) e2e-locked

e2e-locked:
	@$(E2E_SETUP) && $(FRONT_E2E)

mutation: ## Mutate the lines the branch adds since BASE (origin/dev) in the core and the front, list the mutants their tests let survive; fail when the core skips one (ADR 0091, 0196)
	@$(MAKE) -C packages/core mutation
	@$(MAKE) -C apps/front mutation

mutation-api: ## Mutate the lines the branch adds in the API since BASE (origin/dev), on MiniStack: minutes per mutant, on demand
	@$(MAKE) -C apps/api mutation

# With FILTER, paths from the root, each project checks or fixes the paths under its directory. A project without any
# is passed over. The Terraform is passed over too. A project's checks run together, as in make ci. Each prints its
# output once it ends.
paths-in = $(patsubst $(1)/%,%,$(filter $(1)/%,$(FILTER)))
on-paths-in = $(if $(FILTER),$(if $(call paths-in,$(1)),$(2) FILTER="$(call paths-in,$(1))"),$(2))
lint-project = $(call on-paths-in,$(1),$(MAKE) -j --output-sync=recurse -C $(1) lint)
fix-project = $(call on-paths-in,$(1),$(MAKE) -C $(1) fix)

lint: $(if $(FILTER),,infra-lint) adr-references adr-index-check ## Check the code: PHPStan, php-cs-fixer, Rector, Biome, TypeScript, Terraform, the references to the records, their index; FILTER= paths from the root
	@$(call lint-project,packages/core)
	@$(call lint-project,apps/api)
	@$(call lint-project,apps/storage)
	@$(call lint-project,apps/front)

# a record entirely replaced is no reason any more: the history (the journal, the upgrade of 2026) aside
ADR_HISTORY := ':!docs/adr/' ':!docs/upgrade-2026.md'
adr-references: ## Check that no reference names a decision record entirely replaced (ADR 0090)
	@superseded=$$(grep -E '^\| \[[0-9]{4}\].*\| Superseded by' docs/adr/README.md | grep -oE '^\| \[[0-9]{4}' | grep -oE '[0-9]{4}' | paste -sd'|'); \
	found=$$( { git grep -nE "ADRs?[ ,0-9and]*\b($$superseded)\b" -- . $(ADR_HISTORY); git grep -nE "adr/($$superseded)-" -- . $(ADR_HISTORY); } ); \
	if [ -n "$$found" ]; then echo "References to records entirely replaced: cite the record in force (docs/adr/README.md)"; echo "$$found"; exit 1; fi

# the index of the records is written from their headers (ADR 0128): two branches adding a record each merge their
# lines by the union of .gitattributes, the index written again putting them in order
adr-index: ## Write the index of the decision records again from their headers, after adding a record or changing its status
	@make/adr-index.sh

adr-index-check: ## Fail while the index of the decision records differs from their headers
	@make/adr-index.sh --check

front-build: ## Build the front for production (TypeScript, Vite)
	@$(MAKE) -C apps/front build

fix: ## Fix the style of the code: php-cs-fixer, Biome and its rules (the spaces of the French labels); FILTER= paths from the root
	@$(call fix-project,packages/core)
	@$(call fix-project,apps/api)
	@$(call fix-project,apps/storage)
	@$(call fix-project,apps/front)

infra-lint: ## Check the Terraform of the apps: format, validity, tflint
	$(call infra-lint,$(INFRA_APPS))

##@ Infrastructure
# APP given on the command line only, like INSTANCE (checked by the app): a variable left in the shell would silently
# change the target
infra-target:
	@test "$(origin APP)" = "command line" -a "$(origin INSTANCE)" = "command line" -a "$(words $(APP) $(INSTANCE))" = 2 -a -n "$(filter $(APP),$(INFRA_APPS))" \
		|| { echo "Usage: make infra-plan APP=<$(INFRA_APPS)> INSTANCE=<dev|prod|...>, on the command line"; exit 1; }

# the apps a git range may change the deployment of (ADR 0060, 0061, 0063, 0083): an app whose code or infrastructure
# changed, an app requiring the core when the core changed, every app when a file every root depends on changed
INFRA_SHARED = .terraform-version Makefile make/% .github/workflows/terraform-%.yml .github/actions/infra-instance/%
CHANGED = $(shell git diff --name-only '$(RANGE)' 2>/dev/null)
CHANGED_APPS = $(if $(filter $(INFRA_SHARED),$(CHANGED)),$(INFRA_APPS), \
	$(filter $(INFRA_APPS),$(foreach file,$(filter apps/%,$(CHANGED)),$(word 2,$(subst /, ,$(file))))) \
	$(if $(filter packages/core/%,$(CHANGED)),$(foreach app,$(INFRA_APPS),$(if $(call requires-core,$(app)),$(app)))))
comma := ,
empty :=
space := $(empty) $(empty)
json-strings = [$(subst $(space),$(comma),$(patsubst %,"%",$(1)))]

changed-apps: ## Print as JSON the apps a git range may change the deployment of: make changed-apps RANGE=origin/dev...HEAD
	@test -n "$(RANGE)" || { echo "Usage: make changed-apps RANGE=<git range>, such as origin/dev...HEAD"; exit 1; }
	@git diff --name-only '$(RANGE)' >/dev/null
	@echo '$(call json-strings,$(sort $(CHANGED_APPS)))'

package-api: ## Package the API for its Lambda on AWS in build/api.zip (ADR 0063)
	@$(MAKE) -C apps/api package

package-storage: ## Package the storage for its Lambda on AWS in build/storage.zip (ADR 0063)
	@$(MAKE) -C apps/storage package

package-front: ## Package the front as a release in build/front (ADR 0066)
	@$(MAKE) -C apps/front package

release-front: ## Put the release of the front in the bucket of an instance, before its apply; CLEAN=1 after it removes the former ones (ADR 0066)
	@$(MAKE) -C apps/front release

console: ## Run a command of the API, on MiniStack or with INSTANCE= on AWS: make console CMD="app:account:open 'SOLEAN IT' me@example.com" [INSTANCE=dev [FORCE=1]]
	@$(MAKE) -C apps/api console

infra-init: infra-target ## Prepare the Terraform of an app for an instance: make infra-init APP=api INSTANCE=dev
	@$(MAKE) -C apps/$(APP) infra-init

infra-plan: infra-target ## Show what Terraform would change for an app and an instance: make infra-plan APP=api INSTANCE=dev
	@$(MAKE) -C apps/$(APP) infra-plan

infra-apply: infra-target ## Apply the Terraform of an app to an instance, after confirmation: make infra-apply APP=api INSTANCE=dev
	@$(MAKE) -C apps/$(APP) infra-apply

##@ API contract
api-doc: ## Regenerate the OpenAPI contract of the API and the front types after changing an endpoint
	@$(MAKE) -C apps/api openapi
	@$(MAKE) -C apps/front api-types

contract: api-doc ## Fail when the committed OpenAPI contract or the front types differ from the code
	@git diff --exit-code -- apps/api/openapi.json apps/front/src/shared/api/schema.d.ts

##@ Tools
# the wall of a context: its page, served under /docs/event-storming/<context>/ in the release of the front on the
# instances that open it, and its SVGs, shown by the repository's documents (docs/architecture/event-storming.md)
EVENT_STORMING_CONTEXT = $(or $(CONTEXT),Booking)
EVENT_STORMING_NAME = $(shell echo '$(EVENT_STORMING_CONTEXT)' | sed -E 's/([a-z])([A-Z])/\1-\2/g' | tr A-Z a-z)
EVENT_STORMING_DIR = public/docs/event-storming/$(EVENT_STORMING_NAME)
event-storming: ## Read a context's code and write its wall: its model, its page and its SVGs: CONTEXT= its name in the code (Booking)
	@mkdir -p apps/front/$(EVENT_STORMING_DIR)
	@$(MAKE) -C apps/api event-storming-model CONTEXT=$(EVENT_STORMING_CONTEXT) OUTPUT=../front/$(EVENT_STORMING_DIR)/model.json
	@$(MAKE) -C apps/front event-storming-page MODEL=$(EVENT_STORMING_DIR)/model.json PAGE=$(EVENT_STORMING_DIR)/index.html SVG=../../docs/event-storming/$(EVENT_STORMING_NAME)

commands: ## List the commands of the core and their handlers
	@$(MAKE) -C apps/api commands

profiler-clear: ## Empty the history of the requests kept by the profiler of the API, locally: https://api.prospecter.localhost/_profiler
	@$(MAKE) -C apps/api profiler-clear

exec-api: ## Open a shell in the PHP container
	@$(MAKE) -C apps/api shell

exec-front: ## Open a shell in the Node container
	@$(MAKE) -C apps/front shell

secrets: ## Fail when the staged changes hold a secret or an identifier of the AWS installation (the pre-commit hook)
	@$(GITLEAKS) git --pre-commit --staged --redact --no-banner --verbose .

# what a hook bypassed or never installed let through: the commits of the branch since dev (the CI, which fetches
# the whole history)
secrets-branch: ## Fail when a commit of the branch since dev holds a secret or an identifier of the AWS installation
	@$(GITLEAKS) git --log-opts="origin/dev..HEAD" --redact --no-banner --verbose .
