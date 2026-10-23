include ../../make/common.mk ../../make/aws.mk

.PHONY: vendor db test lint fix package

##@ Storage
vendor: ## Install the Composer dependencies of the storage
	@echo "Storage/Installing Composer dependencies..."
	@$(PHP) composer install

db: ministack ## Recreate in MiniStack the buckets of the files (destroys local data)
	@echo "Storage/Creating the S3 bucket of the files..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console app:bucket:recreate'

test: ministack ## Run the tests of the storage (on MiniStack)
	@$(PHP) bin/phpunit

lint: ## Check the code of the storage: PHPStan, php-cs-fixer, Rector
	@$(PHP) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'

fix: ## Fix the style of the code of the storage: php-cs-fixer
	@$(PHP) vendor/bin/php-cs-fixer fix

package: ## Package the storage for its Lambda on AWS in build/storage.zip at the root (ADR 0063)
	@$(DC) run --rm --no-deps -w /var/www/src php tools/package/lambda storage

include ../../make/infra.mk
