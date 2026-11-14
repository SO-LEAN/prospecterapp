# The façade of the projects (ADR 0080): what one project does is in its own Makefile, run here by its directory;
# only what crosses the projects is written here
include make/common.mk make/aws.mk

# the commits checked for secrets and identifiers of an AWS installation (ADR 0059): the repository is mounted at
# its own path and the index git prepares for the commit is passed on, a commit -a or by paths using a temporary one;
# the common git directory is mounted at its own path too
GITLEAKS = docker run --rm -u $(UID):$(GID) -e GIT_INDEX_FILE -v $(CURDIR):$(CURDIR) -v $(GIT_COMMON_DIR):$(GIT_COMMON_DIR) \
	-w $(CURDIR) ghcr.io/gitleaks/gitleaks:v8.30.1
INFRA_APPS = api storage front

.PHONY: install hooks build vendor db aws front-install check-secrets check-infra check-contract check-core check-api check-storage check-front check-e2e front-lint front-tested-build changed-checks infra-target infra-init infra-plan infra-apply infra-lint changed-apps package-api package-storage package-front release-front console start deploy stop down agents-slice logs ps ci test test-api test-storage test-front e2e test-core mutation mutation-api lint adr-references fix front-build api-doc contract commands profiler-clear exec-api exec-front secrets secrets-branch

##@ Setup
install: hooks build vendor db front-install ## Build the images, install the dependencies, create the tables, the bucket and the user pool, install the Git hooks
build: ## Build the Docker images, those of the command line and of the deployment included
	@echo "Docker/Building images..."
	@$(DC) --profile cli --profile deploy build

vendor: ## Install the Composer dependencies of the API, the core and the storage
	@$(MAKE) -C apps/api vendor
	@$(MAKE) -C packages/core vendor
	@$(MAKE) -C apps/storage vendor

db: ## Recreate in MiniStack the tables and the user pool of the API and the bucket of the storage, with the demo data (destroys local data) and the queues of the failures emptied, the relay of the outbox on their new streams, the figures counted
	@$(MAKE) -C apps/api db
	@$(MAKE) -C apps/storage db

front-install: ## Install the pnpm dependencies of the front
	@$(MAKE) -C apps/front install

hooks: ## Install the Git hooks of the repository: gitleaks before each commit
	@git config core.hooksPath .githooks

##@ Stack
aws: ministack ## Start the AWS services emulated locally (MiniStack)

start: deploy ## Start the stack, the API and the storage in their Lambdas: https://local.prospecter.io
	@echo "SPA: https://local.prospecter.io$(PUBLIC_PORT_SUFFIX)  API doc: https://api.local.prospecter.io$(PUBLIC_PORT_SUFFIX)/api/doc"

deploy: ## Start the stack and (re)deploy the Lambdas of the API and the storage on MiniStack
	@$(DC) up -d --build
	@$(DC) run --rm --build lambda-deploy

# the functions a MiniStack runs carry its host name: those of the stacks of the other checkouts stay up
STACK_FUNCTIONS = for ministack in $$($(DC) ps -aq ministack); do \
	docker ps -aq --filter label=ministack.instance=$$(docker inspect -f '{{.Config.Hostname}}' $$ministack):4566; done
stop: ## Stop the stack of this checkout and the functions its MiniStack runs
	@$(STACK_FUNCTIONS) | xargs -r docker rm -f >/dev/null
	@$(DC) stop

down: ## Remove the stack of this checkout, its functions and its data: once a worktree is done with (ADR 0116)
	@$(STACK_FUNCTIONS) | xargs -r docker rm -f >/dev/null
	@$(DC) --profile cli --profile test --profile deploy down --volumes --remove-orphans

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

logs: ## Follow the logs of the stack
	@$(DC) logs -f

ps: ## List the containers of the stack
	@$(DC) ps

##@ Quality
# the checks of the integration (ADR 0109, 0110): those the changes of the branch since BASE need, committed or not, a
# new file once known to git or not ignored (make/changed-checks.sh), every one with ALL=1, as the CI on the pushes of
# dev and main. Independent of one another once the images and the dependencies are there and the contract checked (it
# writes the types the front reads), they run together, and so do the lint and the tests of a project, each printing
# its output once it ends; the CI runs them in jobs of their own (.github/workflows/ci.yml). The journeys run in the
# CI only, on a stack of their own (ADR 0116): locally, make e2e on the stack of the checkout
BASE ?= origin/dev
ALL_CHECKS = secrets infra core api storage front contract e2e
CHANGED_FILES = { git diff --name-only $$(git merge-base $(BASE) HEAD); git ls-files --others --exclude-standard; }
CHECKS = $(if $(ALL),$(ALL_CHECKS),$(shell $(CHANGED_FILES) | make/changed-checks.sh))
ci: ## Run the checks of the integration the changes since BASE (origin/dev) need, ALL=1 every one: secrets of the branch, Terraform, lint and tests of each project, build of the front, contract
	@echo "Checks: $(CHECKS)$(if $(ALL),, (those of the changes since $(BASE); ALL=1 for every one))"
	@$(MAKE) build vendor front-install
	@$(if $(filter contract,$(CHECKS)),$(MAKE) check-contract)
# the MiniStack of the tests, shared by those of the API and the storage: started once, before they would both create it
	@$(if $(filter api storage,$(CHECKS)),$(MAKE) ministack-test)
	@$(MAKE) -j --output-sync=recurse $(addprefix check-,$(filter-out contract e2e,$(CHECKS)))
	@$(if $(filter e2e,$(CHECKS)),echo "The journeys run in the CI: make e2e runs them on the stack of this checkout")

# make -j runs together the prerequisites of each check, and the targets each runs in its project
check-secrets: secrets-branch adr-references ## Check the commits of the branch for secrets, the references to the records
check-infra: infra-lint ## Check the Terraform of the apps
check-contract: contract ## Check the OpenAPI contract and the front types against the code

# a stack started from nothing, its data recreated: in the CI, or in a worktree, never on the data of the main checkout
check-e2e: ## Run the journeys on the stack of this checkout, deployed and its data recreated first: the CI (ADR 0116)
	@test -n "$(CI)$(IN_WORKTREE)" || { echo "check-e2e recreates the data of the stack: in the CI or a worktree only; make e2e on the running stack"; exit 1; }
	@$(MAKE) deploy
	@$(MAKE) db
	@$(MAKE) e2e

check-core: ## Lint and test the core
	@$(MAKE) -C packages/core lint test

check-api: ## Lint and test the API
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
test-core: ## Run the business rules (Behat) and the unit tests of the core; FILTER= a scenario or a test by name
	@$(MAKE) -C packages/core test

test-api: ## Run the tests of the API (on MiniStack: tables, user pool); FILTER= a test by name
	@$(MAKE) -C apps/api test

test-storage: ## Run the tests of the storage (on MiniStack); FILTER= a test by name
	@$(MAKE) -C apps/storage test

test-front: ## Run the tests of the front (Vitest); FILTER= a test file by its path or a part of it
	@$(MAKE) -C apps/front test

# the journeys depend on no service outside the stack: the API asks the double of the geocoder of nginx while they
# run, and the service of the IGN again afterwards, whatever their result
GEOCODER_DOUBLE = http://nginx:8081/search
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
e2e: ## Run the Playwright journeys against the running stack, the API on the double of the geocoder meanwhile
	@$(WARM_REACTIONS) && $(CONSOLE_GESTURES) && $(API_GEOCODER) $(GEOCODER_DOUBLE) && trap '$(API_GEOCODER)' EXIT && trap 'exit 130' INT TERM && $(MAKE) -C apps/front e2e E2E_RUN=$(E2E_RUN)

mutation: ## Mutate the lines the branch adds since BASE (origin/dev) in the core and the front, list the mutants their tests let survive (ADR 0091)
	@$(MAKE) -C packages/core mutation
	@$(MAKE) -C apps/front mutation

mutation-api: ## Mutate the lines the branch adds in the API since BASE (origin/dev), on MiniStack: minutes per mutant, on demand
	@$(MAKE) -C apps/api mutation

# with FILTER, paths from the root: each project checks those under its directory, a project without any is passed
# over, and so is the Terraform
paths-in = $(patsubst $(1)/%,%,$(filter $(1)/%,$(FILTER)))
lint-project = $(if $(FILTER),$(if $(call paths-in,$(1)),$(MAKE) -C $(1) lint FILTER="$(call paths-in,$(1))"),$(MAKE) -C $(1) lint)

lint: $(if $(FILTER),,infra-lint) adr-references ## Check the code: PHPStan, php-cs-fixer, Rector, Biome, TypeScript, Terraform, the references to the records; FILTER= paths from the root
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

front-build: ## Build the front for production (TypeScript, Vite)
	@$(MAKE) -C apps/front build

fix: ## Fix the style of the code: php-cs-fixer, Biome
	@$(MAKE) -C packages/core fix
	@$(MAKE) -C apps/api fix
	@$(MAKE) -C apps/storage fix
	@$(MAKE) -C apps/front fix

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
commands: ## List the commands of the core and their handlers
	@$(MAKE) -C apps/api commands

profiler-clear: ## Empty the history of the requests kept by the profiler of the API, locally: https://api.local.prospecter.io/_profiler
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
