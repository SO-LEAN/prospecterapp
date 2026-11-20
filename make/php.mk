# The checks of the code of a PHP project (ADR 0080, 0110), included by its Makefile: each in its own container, so
# that make -j runs them together, beside the tests; on its whole code or on the paths of FILTER (php-cs-fixer
# keeping the exclusions of its finder). PHPStan analyses the whole project, a class reading its properties in traits
# it may not be given.
.PHONY: lint-phpstan lint-style lint-rector

# the options of PHPUnit and Paratest: FAILED=1 the tests that failed last first (the cache of PHPUnit, .phpunit.cache),
# FAIL_FAST=1 stopping at the first failure
PHPUNIT_FAILED_FAST = $(if $(FAILED),--order-by=defects) $(if $(FAIL_FAST),--stop-on-failure)

# its own limit of memory, the main process and each worker, over the 256M of the command line (php.ini): a worker of
# the API, given its share of the files, outgrew it on a runner of the CI; the whole API analysed in one process peaks
# at some 360M
PHPSTAN_MEMORY_LIMIT = 1G

lint-phpstan:
	@$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=$(PHPSTAN_MEMORY_LIMIT)

lint-style:
	@$(PHP) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --show-progress=none $(if $(FILTER),--path-mode=intersection $(FILTER))

# with FILTER, in one process (rector.php)
lint-rector:
	@$(PHP) $(if $(FILTER),env RECTOR_IN_ONE_PROCESS=1) vendor/bin/rector process --dry-run $(FILTER)
