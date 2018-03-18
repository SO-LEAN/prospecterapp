all: build-env

build-env:
	@echo "Generate environment..."
	@docker-compose build

build-assets:
	@echo "Building..."
	@docker-compose run --rm build composer install
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


.PHONY: all build-env test test-coverage cs
