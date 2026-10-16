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

.DEFAULT_GOAL := help

.PHONY: help install build vendor db aws front-install start deploy stop logs ps ci test test-api test-storage test-front e2e test-core lint fix front-build api-doc contract commands exec-api exec-front

##@ Help
help: ## List the commands (the default target)
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-z0-9-]+:.*## / {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ Setup
install: build vendor db front-install ## Build the images, install the dependencies, create the tables, the bucket and the user pool
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
ci: build vendor front-install lint test contract front-build ## Run what the continuous integration runs: build, lint, tests, contract, build of the front
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

lint: ## Check the code: PHPStan, php-cs-fixer, Rector, Biome, TypeScript
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
