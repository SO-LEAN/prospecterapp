include ../../make/common.mk ../../make/aws.mk ../../make/composer.mk ../../make/php.mk

.PHONY: vendor db test lint fix

##@ Storage
vendor: ## Install the Composer dependencies of the storage
	@echo "Storage/Installing Composer dependencies..."
	@$(PHP) make composer-install

db: ministack ## Recreate in MiniStack the buckets of the files (destroys local data)
	@echo "Storage/Creating the S3 bucket of the files..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console app:bucket:recreate'

test: ministack-test ## Run the tests of the storage (on MiniStack); FILTER= a test by name; FAILED=1, FAIL_FAST=1
	@$(PHP) bin/phpunit $(if $(FILTER),--filter='$(FILTER)') $(PHPUNIT_FAILED_FAST)

lint: lint-phpstan lint-style lint-rector ## Check the code of the storage: PHPStan, php-cs-fixer, Rector; FILTER= paths of the project, for php-cs-fixer and Rector

fix: ## Fix the style of the code of the storage: php-cs-fixer
	@$(PHP) vendor/bin/php-cs-fixer fix

include ../../make/lambda.mk ../../make/infra.mk
