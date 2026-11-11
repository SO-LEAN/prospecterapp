# The Composer dependencies of a PHP app using the SDK of AWS (ADR 0080), included by its Makefile. The SDK keeps only
# the services its composer.json lists (extra.aws/aws-sdk-php): its script removeUnusedServices deletes the others from
# vendor. An install finding the SDK at its version leaves it as it is, and its scripts fail on a service added to the
# list since: the SDK is reinstalled first when a listed service is missing, an install whose list did not change
# reinstalling nothing
AWS_SDK = vendor/aws/aws-sdk-php
AWS_SDK_SERVICES = composer config extra.aws/aws-sdk-php | tr -d '[]"' | tr , ' '

.PHONY: composer-install aws-sdk-services

# run by vendor in the php container, with its Composer
composer-install: aws-sdk-services
	@composer install

aws-sdk-services:
	@[ ! -d $(AWS_SDK) ] || for service in $$($(AWS_SDK_SERVICES)); do \
		[ -d $(AWS_SDK)/src/$$service ] || { echo "Restoring the services of the SDK of AWS ($$service missing)..."; composer reinstall aws/aws-sdk-php; exit; }; \
	done
