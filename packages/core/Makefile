include ../../make/common.mk

.PHONY: vendor test lint fix

##@ Core
vendor: ## Install the Composer dependencies of the core
	@echo "Core/Installing Composer dependencies..."
	@$(PHP) composer install

# business rules (Gherkin scenarios on the use cases, in-memory ports) then the unit tests
test: ## Run the business rules (Behat) and the unit tests of the core
	@$(PHP) sh -c 'vendor/bin/behat --strict -f progress && vendor/bin/phpunit'

lint: ## Check the code of the core: PHPStan, php-cs-fixer, Rector
	@$(PHP) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'

fix: ## Fix the style of the code of the core: php-cs-fixer
	@$(PHP) vendor/bin/php-cs-fixer fix
