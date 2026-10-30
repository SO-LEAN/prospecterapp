include ../../make/common.mk

.PHONY: vendor test lint fix

##@ Core
vendor: ## Install the Composer dependencies of the core
	@echo "Core/Installing Composer dependencies..."
	@$(PHP) composer install

# with FILTER, only the scenarios and the unit tests whose name matches it: one filter for both suites, so a suite
# where nothing matches is passed over
BEHAT = vendor/bin/behat --strict -f progress $(if $(FILTER),--name="$(FILTER)")
UNLESS_NO_SCENARIO_MATCHES = $(if $(FILTER),vendor/bin/behat --dry-run -f progress --name="$(FILTER)" | grep -q "No scenarios" ||)
PHPUNIT = vendor/bin/phpunit $(if $(FILTER),--filter="$(FILTER)" --do-not-fail-on-empty-test-suite)

# business rules (Gherkin scenarios on the use cases, in-memory ports) then the unit tests
test: ## Run the business rules (Behat) and the unit tests of the core; FILTER= a scenario or a test by name
	@$(PHP) sh -c '{ $(UNLESS_NO_SCENARIO_MATCHES) $(BEHAT); } && $(PHPUNIT)'

lint: ## Check the code of the core: PHPStan, php-cs-fixer, Rector; FILTER= paths of the project, for php-cs-fixer and Rector
	@$(PHP) sh -c '$(PHP_LINT)'

fix: ## Fix the style of the code of the core: php-cs-fixer
	@$(PHP) vendor/bin/php-cs-fixer fix
