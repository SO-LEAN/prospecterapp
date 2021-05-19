SYMFONY_MAX_DEPRECATED ?= 500

install: build-dev build-ci composer yarn assets fixtures

build-dev:
	@echo "Generate environment..."
	@docker-compose build --no-cache
	@cp .env.dist .env
build-ci:
	@echo "Ci build image..."
	@cd docker/build && docker build --no-cache -t buildci .
build: composer yarn assets fixtures
db:
	@echo "Database/creating..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci bin/console doctrine:schema:create
dbup:
	@echo "Database/Updating..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci bin/console doctrine:schema:update --force
dbdrop:
	@echo "Database/dropping..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci bin/console doctrine:schema:drop --force
fixtures: dbdrop db
	@echo "Fixtures/Running..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci bin/console doctrine:fixtures:load  --no-interaction
composer:
	@echo "Composer/Building..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci composer install
composerup:
	@echo "Composer/Updating..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci composer update
libup:
	@echo "Composer/updating cleanprospecter..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci composer update so-lean/cleanprospecter
yarn:
	@echo "Yarn/Building..."
	@docker run -v ${PWD}:/app  --rm --user="$(shell id -u):$(shell id -g)" buildci yarn install
assets:
	@echo "Assets/Building..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci yarn run encore dev
watch:
	@echo "Assets/Watching..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci yarn run encore dev --watch
assets-prod:
	@echo "Assets/Watching..."
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci yarn run encore production

start:
	@docker-compose up -d
	@xdg-open https://local.prospecter.io
stop:
	@docker-compose stop
logs:
	@docker-compose logs
ps:
	@docker-compose ps

test:
	@docker run -v ${PWD}:/app --rm -e SYMFONY_DEPRECATIONS_HELPER=max[total]=$(SYMFONY_MAX_DEPRECATED) buildci bin/phpunit
test-web:
	@docker run -v ${PWD}:/app --rm -e SYMFONY_DEPRECATIONS_HELPER=max[total]=$(SYMFONY_MAX_DEPRECATED) buildci bin/phpunit --group web
test-unit:
	@docker run -v ${PWD}:/app --rm -e SYMFONY_DEPRECATIONS_HELPER=max[total]=$(SYMFONY_MAX_DEPRECATED) buildci bin/phpunit --group unit
testdox:
	@docker run -v ${PWD}:/app --rm buildci bin/phpunit --testdox
test-coverage:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci bin/phpunit --coverage-html ./reports/coverage

cs:
	@docker run -v ${PWD}:/app --rm buildci php-cs-fixer fix ./src --dry-run --verbose --rules=@Symfony
cs-fix:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci php-cs-fixer fix ./src --verbose --rules=@Symfony

lint:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci find -L src -name '*.php' -print0 | xargs -0 -n 1 -P 4 php -l

stan:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci vendor/bin/phpstan analyse -l 0 src

phpcpd:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci vendor/bin/phpcpd --regexps-exclude="#(var/)|(vendor/).+#" ./

phpmd-text:
	@mkdir -p reports/phpmd/
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci vendor/bin/phpmd ./src html cleancode,codesize,design,naming,unusedcode > reports/phpmd/report.html

phpmd-ci:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci vendor/bin/phpmd ./src github cleancode,codesize,design,naming,unusedcode

package:
	@docker run -v ${PWD}:/app --rm --user="$(shell id -u):$(shell id -g)" buildci tar --exclude-from='package-excludes.lst' -czvf build.tar.gz ./

usecases:
	@docker run -v ${PWD}:/app --rm buildci bin/console app:use-cases

exec:
	@docker run -v ${PWD}:/app --user="$(shell id -u):$(shell id -g)" -it --rm buildci /bin/sh

.PHONY: all build-dev build composer assets watch assets-prod run stop logs test test-coverage
