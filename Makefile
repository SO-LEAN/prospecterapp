export UID := $(shell id -u)
export GID := $(shell id -g)

DC   = docker compose
PHP  = $(DC) run --rm --no-deps php
NODE = $(DC) run --rm --no-deps node
# the core has its own tooling (Behat, PHPUnit), run from its directory
CORE = $(DC) run --rm --no-deps -w /var/www/src/packages/core php

.PHONY: install build vendor keys db front-install start stop logs ps test test-api test-front e2e test-core lint fix api-doc usecases exec-api exec-front

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

keys:
	@echo "OAuth/Generating the key pair..."
	@$(PHP) sh -c 'test -f config/jwt/private.pem || bin/console league:oauth2-server:generate-keypair'

db:
	@echo "API/Creating the database with fixtures (OAuth client included)..."
	@$(PHP) sh -c 'bin/console doctrine:schema:drop --force --full-database && bin/console doctrine:schema:create && bin/console doctrine:fixtures:load --no-interaction'

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
test: test-core test-api test-front

# business rules (Gherkin scenarios on the use cases, in-memory ports) then the unit tests
test-core:
	@$(CORE) sh -c 'vendor/bin/behat --strict -f progress && vendor/bin/phpunit'

test-api:
	@$(PHP) bin/phpunit

test-front:
	@$(NODE) pnpm test

# Playwright runs in its official image, the local hosts resolved to the running stack
E2E_HOSTS = --add-host local.prospecter.io:127.0.0.1 --add-host api.local.prospecter.io:127.0.0.1 --add-host static.local.prospecter.io:127.0.0.1

e2e:
	@docker run --rm --init --ipc=host --network host $(E2E_HOSTS) -u $(UID):$(GID) -e HOME=/tmp -v $(CURDIR)/apps/front:/work -w /work mcr.microsoft.com/playwright:v1.64.0-noble npx playwright test

lint:
	@$(CORE) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(PHP) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'
	@$(NODE) sh -c 'pnpm lint && pnpm typecheck'

fix:
	@$(CORE) vendor/bin/php-cs-fixer fix
	@$(PHP) vendor/bin/php-cs-fixer fix
	@$(NODE) pnpm format

## API contract: regenerate the OpenAPI document and the front types after changing an endpoint
api-doc:
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'
	@$(NODE) pnpm api:types

## Tools
usecases:
	@$(PHP) bin/console app:use-cases

exec-api:
	@$(PHP) bash

exec-front:
	@$(NODE) sh
