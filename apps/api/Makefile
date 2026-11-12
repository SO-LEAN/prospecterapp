include ../../make/common.mk ../../make/aws.mk ../../make/composer.mk ../../make/mutation.mk

.PHONY: vendor db test mutation lint fix openapi console commands profiler-clear shell

##@ API
vendor: ## Install the Composer dependencies of the API
	@echo "API/Installing Composer dependencies..."
	@$(PHP) make composer-install

db: ministack ## Recreate in MiniStack the tables and the user pool, with the demo data (destroys local data), the relay of the outbox on their new streams, the figures counted
	@echo "API/Creating the DynamoDB table of each bounded context with the data of the demonstration..."
	@$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console app:tables:recreate --demo'
	@echo "API/Creating the Cognito user pool with the members of the demonstration..."
	@$(PHP) bin/console app:user-pool:recreate --demo
	@echo "API/Relaying the outbox of the new tables to the bus..."
	@$(DC) --progress quiet run --rm --build lambda-deploy relay
	@echo "API/Counting the figures of the demonstration..."
	@$(PHP) bin/console app:reporting:rebuild

test: ministack ## Run the tests of the API (on MiniStack: tables, user pool); FILTER= a test by name
	@$(PHP) bin/phpunit $(if $(FILTER),--filter='$(FILTER)')

# a few processes only, each with its own tables and user pool on MiniStack (TEST_TOKEN, .env.test)
mutation: INFECTION_THREADS = 4
mutation: ministack ## Mutate the lines the branch adds in src since BASE (origin/dev), killed by the tests of the API on MiniStack; list the survivors
	$(infection-on-added-lines)

lint: ## Check the code of the API: PHPStan, php-cs-fixer, Rector; FILTER= paths of the project, for php-cs-fixer and Rector
	@$(PHP) sh -c '$(PHP_LINT)'

fix: ## Fix the style of the code of the API: php-cs-fixer
	@$(PHP) vendor/bin/php-cs-fixer fix

openapi: ## Regenerate the OpenAPI contract of the API (openapi.json) after changing an endpoint
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'

# a command on MiniStack, or with INSTANCE= on the tables and the user pool of an instance on AWS, from this machine:
# the temporary credentials of its AWS profile, exported by the AWS CLI in its image, reach the php container through
# the environment only; the names are those of infra/ (the bus of app:outbox:republish, the queue of the failures and
# the worker of app:integration:redrive, the queues of the failures app:consistency:check counts too), the delivery
# that of the api, read in the values of the instance like its region, sync when they name none
console: ## Run a command of the API, on MiniStack or with INSTANCE= on AWS: make console CMD="app:account:open 'SOLEAN IT' me@example.com" [INSTANCE=dev [FORCE=1]]
	@test -n "$(CMD)" || { echo "Usage: make console CMD=\"<command of bin/console>\" [INSTANCE=<dev|prod|...> [FORCE=1]]"; exit 1; }
ifeq ($(origin INSTANCE),command line)
	@test -f $(INFRA_INSTANCE).env -a -f $(INFRA_INSTANCE).tfvars || { echo "No values for the instance $(INSTANCE) in $(INFRA_CONFIG)/envs (docs/architecture/infra.md)"; exit 1; }
	$(instance-code-check)
	@eval "$$($(AWS_CLI) configure export-credentials --format env)" && $(DC) run --rm --no-deps -w $(WORKDIR) \
		-e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN \
		-e AWS_REGION=$$(sed -nE 's/^region *= *"(.*)"/\1/p' $(INFRA_INSTANCE).tfvars) \
		-e DYNAMODB_ENDPOINT= -e COGNITO_ENDPOINT= -e DYNAMODB_TABLE_PREFIX=prospecter-$(INSTANCE)-api \
		-e EVENTBRIDGE_ENDPOINT= -e INTEGRATION_EVENT_BUS=prospecter-$(INSTANCE)-api-integration \
		-e INTEGRATION_EVENTS_DELIVERY=$$(sed -nE 's/^integration_events_delivery *= *"(.*)"/\1/p' $(INFRA_INSTANCE).tfvars | grep . || echo sync) \
		-e SQS_ENDPOINT= -e LAMBDA_ENDPOINT= \
		-e INTEGRATION_FAILURES_QUEUE=prospecter-$(INSTANCE)-api-integration-failures \
		-e RELAY_FAILURES_QUEUE=prospecter-$(INSTANCE)-api-relay-failures \
		-e INTEGRATION_WORKER=prospecter-$(INSTANCE)-api-worker \
		-e COGNITO_USER_POOL_NAME=prospecter-$(INSTANCE)-api-members php bin/console $(CMD)
else
	@$(START_MINISTACK)
	@$(PHP) bin/console $(CMD)
endif

commands: ## List the commands of the core and their handlers
	@$(PHP) bin/console debug:messenger

profiler-clear: ## Empty the history of the requests kept by the profiler of the API, locally
	@$(PHP) rm -rf var/profiler

shell: ## Open a shell in the PHP container
	@$(PHP) bash

include ../../make/lambda.mk ../../make/infra.mk
