export UID := $(shell id -u)
export GID := $(shell id -g)

DC   = docker compose
PHP  = $(DC) run --rm --no-deps php
NODE = $(DC) run --rm --no-deps node
# the core has its own tooling (Behat, PHPUnit), run from its directory
CORE = $(DC) run --rm --no-deps -w /var/www/src/packages/core php
# the storage app (stored files and their sizes)
STORAGE = $(DC) run --rm --no-deps -w /var/www/src/apps/storage php
# the AWS services emulated locally (DynamoDB, S3, Cognito; ADR 0048, 0051, 0052), started before what needs them
AWS = $(DC) up -d ministack
# the API and the storage run in Lambdas with the Bref layers (ADR 0055): the repository is mounted in the
# functions by its absolute path
export PROJECT_DIR := $(CURDIR)
# the commits checked for secrets and identifiers of an AWS installation (ADR 0059): the repository is mounted at
# its own path and the index git prepares for the commit is passed on, a commit -a or by paths using a temporary one
GITLEAKS = docker run --rm -u $(UID):$(GID) -e GIT_INDEX_FILE -v $(CURDIR):$(CURDIR) -w $(CURDIR) ghcr.io/gitleaks/gitleaks:v8.30.1
# Terraform and tflint run in their images, each app keeping its root in apps/<app>/infra (ADR 0059); the values of
# an instance stay out of the repository, in envs/<instance>.{tfvars,tfbackend,env} of the configuration directory;
# each instance keeps its own working directory, so that two instances can be planned at once
INFRA_APPS = api storage front
INFRA_CONFIG ?= $(abspath $(CURDIR)/../prospecterapp-infra)
INFRA_INSTANCE = $(INFRA_CONFIG)/envs/$(INSTANCE)
TERRAFORM_IMAGE = hashicorp/terraform:$(shell cat .terraform-version)
TERRAFORM = docker run --rm -u $(UID):$(GID) -e HOME=/tmp --env-file $(INFRA_INSTANCE).env -e TF_DATA_DIR=.terraform/$(INSTANCE) \
	-v $(HOME)/.aws:/tmp/.aws -v $(CURDIR):/src -v $(INFRA_CONFIG):/config:ro -w /src/apps/$(APP)/infra
TFLINT_IMAGE = ghcr.io/terraform-linters/tflint:v0.64.0

.DEFAULT_GOAL := help

.PHONY: help install hooks build vendor db aws front-install infra-target infra-init infra-plan infra-apply infra-lint start deploy stop logs ps ci test test-api test-storage test-front e2e test-core lint fix front-build api-doc contract commands exec-api exec-front secrets secrets-branch

##@ Help
help: ## List the commands (the default target)
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-z0-9-]+:.*## / {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ Setup
install: hooks build vendor db front-install ## Build the images, install the dependencies, create the tables, the bucket and the user pool, install the Git hooks
build: ## Build the Docker images, those of the command line and of the deployment included
	@echo "Docker/Building images..."
	@$(DC) --profile cli --profile deploy build

vendor: ## Install the Composer dependencies of the API, the core and the storage
	@echo "API/Installing Composer dependencies..."
	@$(PHP) composer install
	@echo "Core/Installing Composer dependencies..."
	@$(CORE) composer install
	@echo "Storage/Installing Composer dependencies..."
	@$(STORAGE) composer install

db: aws ## Recreate in MiniStack the tables and the user pool of the API and the bucket of the storage, with the demo data (destroys local data)
	@echo "API/Creating the DynamoDB table of each bounded context with the data of the demonstration..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console app:tables:recreate --demo'
	@echo "API/Creating the Cognito user pool with the members of the demonstration..."
	@$(PHP) bin/console app:user-pool:recreate --demo
	@echo "Storage/Creating the S3 bucket of the files..."
	@$(STORAGE) sh -c 'bin/console cache:clear >/dev/null && bin/console app:bucket:recreate'

front-install: ## Install the pnpm dependencies of the front
	@echo "Front/Installing pnpm dependencies..."
	@$(NODE) pnpm install

hooks: ## Install the Git hooks of the repository: gitleaks before each commit
	@git config core.hooksPath tools/git-hooks

##@ Stack
aws: ## Start the AWS services emulated locally (MiniStack)
	@$(AWS) >/dev/null 2>&1

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
# business rules (Gherkin scenarios on the use cases, in-memory ports) then the unit tests
test-core: ## Run the business rules (Behat) and the unit tests of the core
	@$(CORE) sh -c 'vendor/bin/behat --strict -f progress && vendor/bin/phpunit'

test-api: aws ## Run the tests of the API (on MiniStack: tables, user pool)
	@$(PHP) bin/phpunit

test-storage: aws ## Run the tests of the storage (on MiniStack)
	@$(STORAGE) bin/phpunit

test-front: ## Run the tests of the front (Vitest)
	@$(NODE) pnpm test

# Playwright runs in its official image, the local hosts resolved to the running stack
E2E_HOSTS = --add-host local.prospecter.io:127.0.0.1 --add-host api.local.prospecter.io:127.0.0.1 --add-host static.local.prospecter.io:127.0.0.1

e2e: ## Run the Playwright journeys against the running stack
	@docker run --rm --init --ipc=host --network host $(E2E_HOSTS) -u $(UID):$(GID) -e HOME=/tmp -v $(CURDIR)/apps/front:/work -w /work mcr.microsoft.com/playwright:v1.64.0-noble npx playwright test

lint: infra-lint ## Check the code: PHPStan, php-cs-fixer, Rector, Biome, TypeScript, Terraform
	@$(CORE) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(PHP) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(STORAGE) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(NODE) sh -c 'pnpm lint && pnpm typecheck'

front-build: ## Build the front for production (TypeScript, Vite)
	@$(NODE) pnpm build

fix: ## Fix the style of the code: php-cs-fixer, Biome
	@$(CORE) vendor/bin/php-cs-fixer fix
	@$(PHP) vendor/bin/php-cs-fixer fix
	@$(STORAGE) vendor/bin/php-cs-fixer fix
	@$(NODE) pnpm format

infra-lint: ## Check the Terraform of the apps: format, validity, tflint
	@docker run --rm -u $(UID):$(GID) -e HOME=/tmp -e TF_PLUGIN_CACHE_DIR=/tmp/plugins -v $(CURDIR):/src -w /src --entrypoint sh $(TERRAFORM_IMAGE) -c \
		'mkdir -p /tmp/plugins && { [ ! -d /src/infra ] || terraform fmt -check -diff -recursive /src/infra; } && for app in $(INFRA_APPS); do cd /src/apps/$$app/infra && terraform fmt -check -diff && TF_DATA_DIR=/tmp/data/$$app terraform init -backend=false -input=false >/dev/null && TF_DATA_DIR=/tmp/data/$$app terraform validate -no-color >/dev/null || exit 1; done'
	@docker run --rm -u $(UID):$(GID) -e HOME=/tmp -e GITHUB_TOKEN -v $(CURDIR):/src -w /src --entrypoint sh $(TFLINT_IMAGE) -c \
		'for app in $(INFRA_APPS); do tflint --chdir=apps/$$app/infra --init >/dev/null && tflint --chdir=apps/$$app/infra || exit 1; done'

##@ Infrastructure
# APP and INSTANCE given on the command line only: a variable left in the shell would silently change the target
infra-target:
	@test "$(origin APP)" = "command line" -a "$(origin INSTANCE)" = "command line" -a "$(words $(APP) $(INSTANCE))" = 2 -a -n "$(filter $(APP),$(INFRA_APPS))" \
		|| { echo "Usage: make infra-plan APP=<$(INFRA_APPS)> INSTANCE=<dev|prod|...>, on the command line"; exit 1; }
	@test -f $(INFRA_INSTANCE).tfvars -a -f $(INFRA_INSTANCE).tfbackend -a -f $(INFRA_INSTANCE).env || { echo "No values for the instance $(INSTANCE): $(INFRA_INSTANCE).tfvars, .tfbackend and .env are needed (docs/infra.md), INFRA_CONFIG names their directory"; exit 1; }

infra-init: infra-target ## Prepare the Terraform of an app for an instance: make infra-init APP=api INSTANCE=dev
	@$(TERRAFORM) $(TERRAFORM_IMAGE) init -reconfigure -input=false -backend-config=/config/envs/$(INSTANCE).tfbackend -backend-config=key=$(APP)/$(INSTANCE).tfstate

infra-plan: infra-init ## Show what Terraform would change for an app and an instance: make infra-plan APP=api INSTANCE=dev
	@$(TERRAFORM) $(TERRAFORM_IMAGE) plan -input=false -var-file=/config/envs/$(INSTANCE).tfvars

infra-apply: infra-init ## Apply the Terraform of an app to an instance, after confirmation: make infra-apply APP=api INSTANCE=dev
	@$(TERRAFORM) -it $(TERRAFORM_IMAGE) apply -var-file=/config/envs/$(INSTANCE).tfvars

##@ API contract
api-doc: ## Regenerate the OpenAPI contract of the API and the front types after changing an endpoint
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'
	@$(NODE) pnpm api:types

contract: api-doc ## Fail when the committed OpenAPI contract or the front types differ from the code
	@git diff --exit-code -- apps/api/openapi.json apps/front/src/shared/api/schema.d.ts

##@ Tools
commands: ## List the commands of the core and their handlers
	@$(PHP) bin/console debug:messenger

exec-api: ## Open a shell in the PHP container
	@$(PHP) bash

exec-front: ## Open a shell in the Node container
	@$(NODE) sh

secrets: ## Fail when the staged changes hold a secret or an identifier of the AWS installation (the pre-commit hook)
	@$(GITLEAKS) git --pre-commit --staged --redact --no-banner --verbose .

# what a hook bypassed or never installed let through: the commits of the branch since dev (the CI, which fetches
# the whole history)
secrets-branch: ## Fail when a commit of the branch since dev holds a secret or an identifier of the AWS installation
	@$(GITLEAKS) git --log-opts="origin/dev..HEAD" --redact --no-banner --verbose .
