include ../../make/common.mk ../../make/aws.mk

.PHONY: vendor db test lint fix openapi console commands shell package

##@ API
vendor: ## Install the Composer dependencies of the API
	@echo "API/Installing Composer dependencies..."
	@$(PHP) composer install

db: ministack ## Recreate in MiniStack the tables and the user pool, with the demo data (destroys local data)
	@echo "API/Creating the DynamoDB table of each bounded context with the data of the demonstration..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console app:tables:recreate --demo'
	@echo "API/Creating the Cognito user pool with the members of the demonstration..."
	@$(PHP) bin/console app:user-pool:recreate --demo

test: ministack ## Run the tests of the API (on MiniStack: tables, user pool)
	@$(PHP) bin/phpunit

lint: ## Check the code of the API: PHPStan, php-cs-fixer, Rector
	@$(PHP) sh -c 'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff && vendor/bin/rector process --dry-run'

fix: ## Fix the style of the code of the API: php-cs-fixer
	@$(PHP) vendor/bin/php-cs-fixer fix

openapi: ## Regenerate the OpenAPI contract of the API (openapi.json) after changing an endpoint
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'

# a command on MiniStack, or with INSTANCE= on the tables and the user pool of an instance on AWS, from this machine:
# the temporary credentials of its AWS profile, exported by the AWS CLI in its image, reach the php container through
# the environment only; the names are those of infra/
console: ## Run a command of the API, on MiniStack or with INSTANCE= on AWS: make console CMD="app:account:open 'SOLEAN IT' me@example.com" [INSTANCE=dev]
	@test -n "$(CMD)" || { echo "Usage: make console CMD=\"<command of bin/console>\" [INSTANCE=<dev|prod|...>]"; exit 1; }
ifeq ($(origin INSTANCE),command line)
	@test -f $(INFRA_INSTANCE).env -a -f $(INFRA_INSTANCE).tfvars || { echo "No values for the instance $(INSTANCE) in $(INFRA_CONFIG)/envs (docs/infra.md)"; exit 1; }
	@eval "$$($(AWS_CLI) configure export-credentials --format env)" && $(DC) run --rm --no-deps -w $(WORKDIR) \
		-e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN \
		-e AWS_REGION=$$(sed -nE 's/^region *= *"(.*)"/\1/p' $(INFRA_INSTANCE).tfvars) \
		-e DYNAMODB_ENDPOINT= -e COGNITO_ENDPOINT= -e DYNAMODB_TABLE_PREFIX=prospecter-$(INSTANCE)-api \
		-e COGNITO_USER_POOL_NAME=prospecter-$(INSTANCE)-api-members php bin/console $(CMD)
else
	@$(START_MINISTACK)
	@$(PHP) bin/console $(CMD)
endif

commands: ## List the commands of the core and their handlers
	@$(PHP) bin/console debug:messenger

shell: ## Open a shell in the PHP container
	@$(PHP) bash

package: ## Package the API for its Lambda on AWS in build/api.zip at the root (ADR 0063)
	@$(DC) run --rm --no-deps -w /var/www/src php tools/package/lambda api

include ../../make/infra.mk
