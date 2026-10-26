# The package of a PHP app for its Lambda on AWS (ADR 0063), included by the Makefile of the app: build/<app>.zip at
# the root, its code without what only develops, tests or deploys it, its dependencies without those of development,
# the core copied in when it requires it (docs/monorepo.md) and the cache of the prod environment warmed
LAMBDA_APP := $(notdir $(CURDIR))
LAMBDA_BUILD = $(ROOT)/build/$(LAMBDA_APP)
LAMBDA_LEFT_OUT = vendor var tests infra .env.* phpunit.xml.dist phpunit.dist.xml phpstan.dist.neon rector.php AGENTS.md \
	CLAUDE.md Makefile
# what the core needs for its own tests; the doc of the api loads Swagger UI from its CDN (assets_mode of
# nelmio_api_doc): its copies are of no use
LAMBDA_DEV_FILES = vendor/prospecter/core/vendor vendor/prospecter/core/tests vendor/prospecter/core/features \
	vendor/prospecter/core/Makefile vendor/nelmio/api-doc-bundle/public public/bundles
# the routes of the api are bound to the host of the instance (APP_API_HOST), unknown here: the router is compiled at
# the first request, in /tmp
LAMBDA_ROUTERS = var/cache/prod/url_matching_routes.php* var/cache/prod/url_generating_routes.php*
LAMBDA_COMPOSER = composer --working-dir=$(LAMBDA_BUILD) --no-interaction --quiet

.PHONY: package lambda-package

##@ Lambda

package: ## Package the app for its Lambda on AWS in build/<app>.zip at the root (ADR 0063)
	@$(PHP) make lambda-package

# run by package in the php container, with its PHP, Composer, tar and zip
lambda-package:
	@rm -rf $(LAMBDA_BUILD) $(LAMBDA_BUILD).zip
	@mkdir -p $(LAMBDA_BUILD)
	@tar $(foreach file,$(LAMBDA_LEFT_OUT),--exclude='./$(file)') -cf - . | tar -C $(LAMBDA_BUILD) -xf -
	@COMPOSER_MIRROR_PATH_REPOS=1 $(LAMBDA_COMPOSER) install --no-dev --no-autoloader --no-progress --no-scripts
# only the services of the SDK of AWS the app uses (extra.aws/aws-sdk-php of its composer.json), then the autoloader
	@$(LAMBDA_COMPOSER) run-script pre-autoload-dump
	@$(LAMBDA_COMPOSER) dump-autoload --no-dev --classmap-authoritative --no-scripts
	@cd $(LAMBDA_BUILD) && rm -rf $(LAMBDA_DEV_FILES)
# the deprecations it logs are those make lint reports already; the share dir is compiled in the container: /tmp,
# the only writable place of a Lambda (the cache is moved there by BrefKernel)
	@cd $(LAMBDA_BUILD) && APP_ENV=prod APP_DEBUG=0 APP_SHARE_DIR=/tmp/share php bin/console cache:warmup --no-interaction --quiet 2>/dev/null
	@cd $(LAMBDA_BUILD) && rm -f $(LAMBDA_ROUTERS) && zip -q -r -y ../$(LAMBDA_APP).zip .
