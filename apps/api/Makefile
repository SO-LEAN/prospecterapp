include ../../make/common.mk ../../make/aws.mk ../../make/composer.mk ../../make/mutation.mk ../../make/php.mk

# the API on the real clock whatever CLOCK_SHIFT: its tests sign in on the Cognito of MiniStack, which issues its
# tokens on the real clock (ADR 0150); the PHPStan rule and the fixed clock of its tests keep it from the real date
override CLOCK_SHIFT =

.PHONY: vendor db restore test mutation lint fix openapi console commands profiler-clear event-storming-model shell

##@ API
vendor: ## Install the Composer dependencies of the API
	@echo "API/Installing Composer dependencies..."
	@$(PHP) make composer-install

# db and restore both empty the failure queues first, because their messages are about the data made again. The command
# loading the demonstration counts its figures, alone, without waiting for the worker (ADR 0209). Both then rebuild the
# contacts' tags
PURGE_FAILURES = echo "API/Emptying the failure queues, whose messages are about the data made again..." \
	&& $(call timed,API/queues of the failures emptied,$(DC) --progress quiet run --rm --build lambda-deploy purge)
REBUILD_TAGS = echo "API/Rebuilding the tags of the contacts of the demonstration..." \
	&& $(call timed,API/tags rebuilt,$(PHP) bin/console app:tags:rebuild)

db: ministack ## Recreate in MiniStack the tables and the user pool, with the demo data (destroys local data), the queues of the failures emptied, the figures counted, the relay of the outbox on their new streams, the tags of the contacts rebuilt
	@$(PURGE_FAILURES)
	@echo "API/Creating the DynamoDB table of each bounded context with the data of the demonstration, its figures counted..."
	@$(call timed,API/tables recreated with the demonstration,$(PHP) sh -c 'bin/console cache:clear >/dev/null && bin/console app:tables:recreate --demo')
	@echo "API/Creating the Cognito user pool with the members of the demonstration..."
	@$(call timed,API/user pool recreated with the members,$(PHP) bin/console app:user-pool:recreate --demo)
	@echo "API/Relaying the outbox of the new tables to the bus..."
	@$(call timed,API/relay on the new streams,$(DC) --progress quiet run --rm --build lambda-deploy relay)
	@$(REBUILD_TAGS)

# the relay already reads the streams of the tables kept, so it publishes the demonstration loaded again
restore: ministack ## Give the tables and the user pool the demo data back without recreating them (destroys local data). It empties the failure queues, counts the figures and rebuilds the contacts' tags
	@$(PURGE_FAILURES)
	@echo "API/Giving the tables and the user pool the demonstration back, its figures counted..."
	@$(call timed,API/demonstration restored,$(PHP) bin/console app:demo:restore)
	@$(REBUILD_TAGS)

# the tests recreate their tables and their user pool once in each process, each test then getting the data and the
# members of the demonstration back (ADR 0180): shared among a few processes, each with its own on MiniStack
# (TEST_TOKEN, .env.test), the cache of the kernel warmed once before they start it together
TEST_PROCESSES ?= 4
# the tests re-testing a rule the core proves (the core-rule group) run after each push to dev and main, where the CI
# gives ALL=1, and not in a branch's check; a test named by FILTER runs whatever its group
EXCLUDED_GROUPS = $(if $(or $(ALL),$(FILTER)),,--exclude-group=core-rule)
test: ministack-test ## Run the tests of the API (on MiniStack: tables, user pool), in TEST_PROCESSES processes, but those re-testing a core rule (ALL=1 runs them); FILTER= a test by name; FAILED=1, FAIL_FAST=1
	@$(PHP) sh -c 'bin/console cache:warmup --env=test >/dev/null && vendor/bin/paratest --processes=$(TEST_PROCESSES) $(if $(FILTER),--filter="$(FILTER)") $(EXCLUDED_GROUPS) $(PHPUNIT_FAILED_FAST)'

# a few processes only, each with its own tables and user pool on MiniStack (TEST_TOKEN, .env.test)
mutation: INFECTION_THREADS = 4
mutation: ministack-test ## Mutate the lines the branch adds in src since BASE (origin/dev), killed by the tests of the API on MiniStack; list the survivors
	$(infection-on-added-lines)

lint: lint-phpstan lint-style lint-rector ## Check the code of the API: PHPStan, php-cs-fixer, Rector; FILTER= paths of the project, for php-cs-fixer and Rector

fix: ## Fix the style of the code of the API: php-cs-fixer; FILTER= paths of the project
	@$(PHP) vendor/bin/php-cs-fixer fix --show-progress=none $(if $(FILTER),--path-mode=intersection $(FILTER))

openapi: ## Regenerate the OpenAPI contract of the API (openapi.json) after changing an endpoint
	@$(PHP) sh -c 'bin/console nelmio:apidoc:dump --format=json > openapi.json'

# a command on MiniStack, or with INSTANCE= on the tables and the user pool of an instance on AWS, from this machine:
# the temporary credentials of its AWS profile, exported by the AWS CLI in its image, reach the php container through
# the environment only; the names are those of infra/ (the bus of app:outbox:republish, the queue of the failures and
# the worker of app:integration:redrive, the queues of the failures app:consistency:check counts too), the delivery
# that of the api, read in the values of the instance like its region, outbox when they name none (ADR 0108)
console: ## Run a command of the API, on MiniStack or with INSTANCE= on AWS: make console CMD="app:account:open 'SOLEAN IT' me@example.com" [INSTANCE=dev [FORCE=1]]
	@test -n "$(CMD)" || { echo "Usage: make console CMD=\"<command of bin/console>\" [INSTANCE=<dev|prod|...> [FORCE=1]]"; exit 1; }
ifeq ($(origin INSTANCE),command line)
	@test -f $(INFRA_INSTANCE).env -a -f $(INFRA_INSTANCE).tfvars || { echo "No values for the instance $(INSTANCE) in $(INFRA_CONFIG)/envs (docs/architecture/infra.md)"; exit 1; }
	$(instance-code-check)
	@eval "$$($(AWS_CLI) configure export-credentials --format env)" && $(DC) run --rm --no-deps -w $(WORKDIR) \
		-e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN \
		-e AWS_REGION=$$(sed -nE 's/^region *= *"([^"]*)".*/\1/p' $(INFRA_INSTANCE).tfvars) \
		-e DYNAMODB_ENDPOINT= -e COGNITO_ENDPOINT= -e DYNAMODB_TABLE_PREFIX=prospecter-$(INSTANCE)-api \
		-e EVENTBRIDGE_ENDPOINT= -e INTEGRATION_EVENT_BUS=prospecter-$(INSTANCE)-api-integration \
		-e INTEGRATION_EVENTS_DELIVERY=$$(sed -nE 's/^integration_events_delivery *= *"([^"]*)".*/\1/p' $(INFRA_INSTANCE).tfvars | grep . || echo outbox) \
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

# every context's event storming, read by PHPStan from the whole core and the whole delivery, where the callers of a
# context's published services and many of its controllers sit (docs/architecture/event-storming.md): OUTPUT= the file
# of the document of the models
EVENT_STORMING_PATHS = ../../packages/core/src src
event-storming-model: ## Write the document of every context's event storming model: OUTPUT=<file>
	@test -n "$(OUTPUT)" || { echo "make event-storming-model OUTPUT=<file>"; exit 1; }
	@$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=1G \
		-c ../../packages/phpstan-rules/EventStorming/event-storming.neon --error-format=eventStorming $(EVENT_STORMING_PATHS) > $(OUTPUT).tmp \
		&& mv $(OUTPUT).tmp $(OUTPUT) || { cat $(OUTPUT).tmp; rm -f $(OUTPUT).tmp; exit 1; }

shell: ## Open a shell in the PHP container
	@$(PHP) bash

include ../../make/lambda.mk ../../make/infra.mk
