# The façade of the projects (ADR 0080): what one project does is in its own Makefile, run here by its directory;
# only what crosses the projects is written here
include make/common.mk make/aws.mk

# the commits checked for secrets and identifiers of an AWS installation (ADR 0059): the repository is mounted at
# its own path and the index git prepares for the commit is passed on, a commit -a or by paths using a temporary one;
# the common git directory is mounted at its own path too: in a worktree, .git only points to it, out of the checkout
GIT_COMMON_DIR = $(shell git rev-parse --path-format=absolute --git-common-dir)
GITLEAKS = docker run --rm -u $(UID):$(GID) -e GIT_INDEX_FILE -v $(CURDIR):$(CURDIR) -v $(GIT_COMMON_DIR):$(GIT_COMMON_DIR) \
	-w $(CURDIR) ghcr.io/gitleaks/gitleaks:v8.30.1
INFRA_APPS = api storage front

.PHONY: install hooks build vendor db aws front-install infra-target infra-init infra-plan infra-apply infra-lint package-api package-storage package-front release-front console start deploy stop logs ps ci test test-api test-storage test-front e2e test-core lint fix front-build api-doc contract commands exec-api exec-front secrets secrets-branch

##@ Setup
install: hooks build vendor db front-install ## Build the images, install the dependencies, create the tables, the bucket and the user pool, install the Git hooks
build: ## Build the Docker images, those of the command line and of the deployment included
	@echo "Docker/Building images..."
	@$(DC) --profile cli --profile deploy build

vendor: ## Install the Composer dependencies of the API, the core and the storage
	@$(MAKE) -C apps/api vendor
	@$(MAKE) -C packages/core vendor
	@$(MAKE) -C apps/storage vendor

db: ## Recreate in MiniStack the tables and the user pool of the API and the bucket of the storage, with the demo data (destroys local data)
	@$(MAKE) -C apps/api db
	@$(MAKE) -C apps/storage db

front-install: ## Install the pnpm dependencies of the front
	@$(MAKE) -C apps/front install

hooks: ## Install the Git hooks of the repository: gitleaks before each commit
	@git config core.hooksPath tools/git-hooks

##@ Stack
aws: ministack ## Start the AWS services emulated locally (MiniStack)

start: deploy ## Start the stack, the API and the storage in their Lambdas: https://local.prospecter.io
	@echo "SPA: https://local.prospecter.io  API doc: https://api.local.prospecter.io/api/doc"

deploy: ## Start the stack and (re)deploy the Lambdas of the API and the storage on MiniStack
	@$(DC) up -d --build
	@$(DC) run --rm --build lambda-deploy

stop: ## Stop the stack and the functions MiniStack runs
	@$(DC) stop
	@docker ps -aq --filter label=ministack=lambda | xargs -r docker rm -f >/dev/null

logs: ## Follow the logs of the stack
	@$(DC) logs -f

ps: ## List the containers of the stack
	@$(DC) ps

##@ Quality
ci: build vendor front-install secrets-branch lint test contract front-build ## Run what the continuous integration runs: secrets of the branch, build, lint, tests, contract, build of the front
test: test-core test-api test-storage test-front ## Run every test suite: core, API, storage, front
test-core: ## Run the business rules (Behat) and the unit tests of the core
	@$(MAKE) -C packages/core test

test-api: ## Run the tests of the API (on MiniStack: tables, user pool)
	@$(MAKE) -C apps/api test

test-storage: ## Run the tests of the storage (on MiniStack)
	@$(MAKE) -C apps/storage test

test-front: ## Run the tests of the front (Vitest)
	@$(MAKE) -C apps/front test

e2e: ## Run the Playwright journeys against the running stack
	@$(MAKE) -C apps/front e2e

lint: infra-lint ## Check the code: PHPStan, php-cs-fixer, Rector, Biome, TypeScript, Terraform
	@$(MAKE) -C packages/core lint
	@$(MAKE) -C apps/api lint
	@$(MAKE) -C apps/storage lint
	@$(MAKE) -C apps/front lint

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

package-api: ## Package the API for its Lambda on AWS in build/api.zip (ADR 0063)
	@$(MAKE) -C apps/api package

package-storage: ## Package the storage for its Lambda on AWS in build/storage.zip (ADR 0063)
	@$(MAKE) -C apps/storage package

package-front: ## Package the front as a release in build/front (ADR 0066)
	@$(MAKE) -C apps/front package

release-front: ## Put the release of the front in the bucket of an instance, before its apply; CLEAN=1 after it removes the former ones (ADR 0066)
	@$(MAKE) -C apps/front release

console: ## Run a command of the API, on MiniStack or with INSTANCE= on AWS: make console CMD="app:account:open 'SOLEAN IT' me@example.com" [INSTANCE=dev]
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
