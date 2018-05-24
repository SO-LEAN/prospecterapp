all: build-dev composer yarn assets db fixtures

build-dev:
	@echo "Generate environment..."
	@docker-compose build --no-cache

build: composer yarn assets
db:
	@echo "Database/creating..."
	@docker-compose run --rm build bin/console doctrine:schema:create
dbup:
	@echo "Database/Updating..."
	@docker-compose run --rm build bin/console doctrine:schema:update --force
dbdrop:
	@echo "Database/dropping..."
	@docker-compose run --rm build bin/console doctrine:schema:drop --force
fixtures:
	@echo "Fixtures/Running..."
	@docker-compose run --rm build bin/console doctrine:fixtures:load
composer:
	@echo "Composer/Building..."
	@docker-compose run --rm build composer install
composerup:
	@echo "Composer/Updating..."
	@docker-compose run --rm build composer install
libup:
	@echo "Composer/updating cleanprospecter..."
	@docker-compose run --rm build composer update so-lean/cleanprospecter
yarn:
	@echo "Yarn/Building..."
	@docker-compose run --rm build yarn install
assets:
	@echo "Assets/Building..."
	@docker-compose run --rm build yarn run encore dev
watch:
	@echo "Assets/Watching..."
	@docker-compose run --rm build yarn run encore dev --watch
assets-prod:
	@echo "Assets/Watching..."
	@docker-compose run --rm build yarn run encore production

run:
	@docker-compose up -d
stop:
	@docker-compose stop
logs:
	@docker-compose logs
ps:
	@docker-compose ps

test:
	@docker-compose run --rm build bin/phpunit
test-web:
	@docker-compose run --rm build bin/phpunit --group web
test-unit:
	@docker-compose run --rm build bin/phpunit --group unit
testdox:
	@docker-compose run --rm build bin/phpunit --testdox
test-coverage:
	@docker-compose run --rm build bin/phpunit --coverage-html ./reports

cs:
	@docker-compose run --rm build php-cs-fixer fix ./src --dry-run --verbose --rules=@Symfony
cs-fix:
	@docker-compose run --rm build php-cs-fixer fix ./src --verbose --rules=@Symfony

usecases:
	@docker-compose run --rm build bin/console app:use-cases
exec:
	@docker-compose run --rm build /bin/bash

.PHONY: all build-dev build composer assets watch assets-prod run stop logs test test-coverage
