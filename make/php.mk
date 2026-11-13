# The checks of the code of a PHP project (ADR 0080, 0110), included by its Makefile: each in its own container, so
# that make -j runs them together, beside the tests; on its whole code or on the paths of FILTER (php-cs-fixer
# keeping the exclusions of its finder). PHPStan analyses the whole project, a class reading its properties in traits
# it may not be given.
.PHONY: lint-phpstan lint-style lint-rector

lint-phpstan:
	@$(PHP) vendor/bin/phpstan analyse --no-progress

lint-style:
	@$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --show-progress=none $(if $(FILTER),--path-mode=intersection $(FILTER))

lint-rector:
	@$(PHP) vendor/bin/rector process --dry-run $(FILTER)
