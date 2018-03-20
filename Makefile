all: build-dev

build-dev:
	@echo "Generate environment..."
	@docker-compose build

build: composer yarn

composer:
	@echo "Composer/Building..."
	@docker-compose run --rm build composer install
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

test:
	@docker-compose run --rm build bin/phpunit
test-coverage:
	@docker-compose run --rm build bin/phpunit --coverage-html ./reports


.PHONY: all build-dev build composer assets watch assets-prod run stop logs test test-coverage