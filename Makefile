export UID := $(shell id -u)
export GID := $(shell id -g)

DC   = docker compose
PHP  = $(DC) run --rm --no-deps php
NODE = $(DC) run --rm --no-deps node
# the core has its own tooling (Behat, PHPUnit), run from its directory
CORE = $(DC) run --rm --no-deps -w /var/www/src/packages/core php
# the storage app (stored files and their sizes)
STORAGE = $(DC) run --rm --no-deps -w /var/www/src/apps/storage php
# the authorization server (sign-in, OAuth 2, credentials)
AUTH = $(DC) run --rm --no-deps -w /var/www/src/apps/auth php

.PHONY: install build vendor keys db front-install start stop logs ps test test-api test-auth test-storage test-front e2e test-core lint fix api-doc commands exec-api exec-front

## Setup
install: build vendor keys db front-install

build:
	@echo "Docker/Building images..."
	@$(DC) build

vendor:
	@echo "API/Installing Composer dependencies..."
	@$(PHP) composer install
	@echo "Core/Installing Composer dependencies..."
	@$(CORE) composer install
	@echo "Auth/Installing Composer dependencies..."
	@$(AUTH) composer install
	@echo "Storage/Installing Composer dependencies..."
	@$(STORAGE) composer install

keys:
	@echo "Auth/Generating the key pair the access tokens are signed with..."
	@$(AUTH) sh -c 'test -f config/jwt/private.pem || bin/console league:oauth2-server:generate-keypair'

db:
	@echo "API/Creating the database with fixtures..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console doctrine:schema:drop --force --full-database && bin/console doctrine:schema:create && bin/console doctrine:fixtures:load --no-interaction'
	@echo "Auth/Creating the database with fixtures (OAuth client, credentials of the prospector)..."
	@$(AUTH) sh -c 'bin/console cache:clear >/dev/null && bin/console doctrine:schema:drop --force --full-database && bin/console doctrine:schema:create && bin/console doctrine:fixtures:load --no-interaction'

front-install:
	@echo "Front/Installing pnpm dependencies..."
	@$(NODE) pnpm install

## Stack
start:
	@$(DC) up -d
	@echo "SPA: https://local.prospecter.io  API doc: https://api.local.prospecter.io/api/doc"

stop:
	@$(DC) stop

logs:
	@$(DC) logs -f

ps:
	@$(DC) ps

## Quality
test: test-core test-api test-auth test-storage test-front

# business rules (Gherkin scenarios on the use cases, in-memory ports) then the unit tests
test-core:
	@$(CORE) sh -c 'vendor/bin/behat --strict -f progress && vendor/bin/phpunit'

test-api:
	@$(PHP) bin/phpunit

test-auth:
	@$(AUTH) bin/phpunit

test-storage:
	@$(STORAGE) bin/phpunit

test-front:
	@$(NODE) pnpm test

# Playwright runs in its official image, the local hosts resolved to the running stack
E2E_HOSTS = --add-host local.prospecter.io:127.0.0.1 --add-host api.local.prospecter.io:127.0.0.1 --add-host static.local.prospecter.io:127.0.0.1

e2e:
	@docker run --rm --init --ipc=host --network host $(E2E_HOSTS) -u $(UID):$(GID) -e HOME=/tmp -v $(CURDIR)/apps/front:/work -w /work mcr.microsoft.com/playwright:v1.64.0-noble npx playwright test

lint:
	@$(CORE) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(PHP) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(AUTH) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(STORAGE) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(NODE) sh -c 'pnpm lint && pnpm typecheck'

fix:
	@$(CORE) vendor/bin/php-cs-fixer fix
	@$(PHP) vendor/bin/php-cs-fixer fix
	@$(AUTH) vendor/bin/php-cs-fixer fix
	@$(STORAGE) vendor/bin/php-cs-fixer fix
	@$(NODE) pnpm format

## API contract: regenerate the OpenAPI document and the front types after changing an endpoint
api-doc:
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'
	@$(AUTH) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'
	@$(NODE) pnpm api:types

## Tools
# the commands of the core and their handlers, on the command bus
commands:
	@$(PHP) bin/console debug:messenger

exec-api:
	@$(PHP) bash

exec-front:
	@$(NODE) sh
