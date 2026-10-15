export UID := $(shell id -u)
export GID := $(shell id -g)

DC   = docker compose
PHP  = $(DC) run --rm --no-deps php
NODE = $(DC) run --rm --no-deps node
# the core has its own tooling (Behat, PHPUnit), run from its directory
CORE = $(DC) run --rm --no-deps -w /var/www/src/packages/core php
# the storage app (stored files and their sizes)
STORAGE = $(DC) run --rm --no-deps -w /var/www/src/apps/storage php
# the entity managers of the API, one database per bounded context (ADR 0038)
API_CONTEXTS = accounts personal_data prospecting
# the authorization server (sign-in, OAuth 2, credentials)
AUTH = $(DC) run --rm --no-deps -w /var/www/src/apps/auth php

.DEFAULT_GOAL := help

.PHONY: help install build vendor keys db front-install start stop logs ps test test-api test-auth test-storage test-front e2e test-core lint fix api-doc commands exec-api exec-front

##@ Help
help: ## List the commands (the default target)
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-z0-9-]+:.*## / {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ Setup
install: build vendor keys db front-install ## Build the images, install the dependencies, generate the keys, create the databases
build: ## Build the Docker images
	@echo "Docker/Building images..."
	@$(DC) build

vendor: ## Install the Composer dependencies of the API, the core, the auth and the storage
	@echo "API/Installing Composer dependencies..."
	@$(PHP) composer install
	@echo "Core/Installing Composer dependencies..."
	@$(CORE) composer install
	@echo "Auth/Installing Composer dependencies..."
	@$(AUTH) composer install
	@echo "Storage/Installing Composer dependencies..."
	@$(STORAGE) composer install

keys: ## Generate the key pair the auth signs the access tokens with (kept if it exists)
	@echo "Auth/Generating the key pair the access tokens are signed with..."
	@$(AUTH) sh -c 'test -f config/jwt/private.pem || bin/console league:oauth2-server:generate-keypair'

db: ## Recreate the databases of the API (one per context) and of the auth, with fixtures (destroys local data)
	@echo "API/Creating the database of each bounded context with fixtures..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && for context in $(API_CONTEXTS); do bin/console doctrine:schema:drop --force --full-database --em=$$context && bin/console doctrine:schema:create --em=$$context || exit 1; done && bin/console doctrine:fixtures:load --no-interaction --append'
	@echo "Auth/Creating the database with fixtures (OAuth client, credentials of the fixture members)..."
	@$(AUTH) sh -c 'bin/console cache:clear >/dev/null && bin/console doctrine:schema:drop --force --full-database && bin/console doctrine:schema:create && bin/console doctrine:fixtures:load --no-interaction'

front-install: ## Install the pnpm dependencies of the front
	@echo "Front/Installing pnpm dependencies..."
	@$(NODE) pnpm install

##@ Stack
start: ## Start the stack: https://local.prospecter.io
	@$(DC) up -d
	@echo "SPA: https://local.prospecter.io  API doc: https://api.local.prospecter.io/api/doc"

stop: ## Stop the stack
	@$(DC) stop

logs: ## Follow the logs of the stack
	@$(DC) logs -f

ps: ## List the containers of the stack
	@$(DC) ps

##@ Quality
test: test-core test-api test-auth test-storage test-front ## Run every test suite: core, API, auth, storage, front
# business rules (Gherkin scenarios on the use cases, in-memory ports) then the unit tests
test-core: ## Run the business rules (Behat) and the unit tests of the core
	@$(CORE) sh -c 'vendor/bin/behat --strict -f progress && vendor/bin/phpunit'

test-api: ## Run the tests of the API
	@$(PHP) bin/phpunit

test-auth: ## Run the tests of the auth
	@$(AUTH) bin/phpunit

test-storage: ## Run the tests of the storage
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
	@$(AUTH) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(STORAGE) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(NODE) sh -c 'pnpm lint && pnpm typecheck'

fix: ## Fix the style of the code: php-cs-fixer, Biome
	@$(CORE) vendor/bin/php-cs-fixer fix
	@$(PHP) vendor/bin/php-cs-fixer fix
	@$(AUTH) vendor/bin/php-cs-fixer fix
	@$(STORAGE) vendor/bin/php-cs-fixer fix
	@$(NODE) pnpm format

##@ API contract
api-doc: ## Regenerate the OpenAPI contracts (API, auth) and the front types after changing an endpoint
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'
	@$(AUTH) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'
	@$(NODE) pnpm api:types

##@ Tools
commands: ## List the commands of the core and their handlers
	@$(PHP) bin/console debug:messenger

exec-api: ## Open a shell in the PHP container
	@$(PHP) bash

exec-front: ## Open a shell in the Node container
	@$(NODE) sh
