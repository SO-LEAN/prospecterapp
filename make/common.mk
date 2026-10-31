# What every Makefile of the repository includes (ADR 0080): Docker run from the directory of any project, and the
# list of the commands
ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))..)
HELP_MAKEFILE := $(lastword $(MAKEFILE_LIST))

export UID := $(shell id -u)
export GID := $(shell id -g)
MAKEFLAGS += --no-print-directory

DC = docker compose --project-directory $(ROOT)
# the whole repository is mounted in the containers: a project runs in its own directory there
WORKDIR = /var/www/src$(patsubst $(ROOT)%,%,$(CURDIR))
# a command prints its own output only, not the creation of its container: what the agents read stays short
PHP  = $(DC) --progress quiet run --rm --no-deps -w $(WORKDIR) php
NODE = $(DC) --progress quiet run --rm --no-deps -w $(WORKDIR) node
# the checks of a PHP project, on its whole code or on the paths of FILTER (php-cs-fixer keeping the exclusions of
# its finder); PHPStan analyses the whole project, a class reading its properties in traits it may not be given
PHP_LINT = vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff --show-progress=none $(if $(FILTER),--path-mode=intersection $(FILTER)) && vendor/bin/rector process --dry-run $(FILTER)
# the API and the storage run in Lambdas with the Bref layers (ADR 0055): the repository is mounted in the
# functions by its absolute path
export PROJECT_DIR := $(ROOT)

.DEFAULT_GOAL := help
.PHONY: help ministack

##@ Help
help: ## List the commands (the default target)
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-z0-9-]+:.*## / {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}' $(HELP_MAKEFILE) $(filter-out $(HELP_MAKEFILE),$(MAKEFILE_LIST))

# the AWS services emulated locally (DynamoDB, S3, Cognito; ADR 0048, 0051, 0052), started before what needs them
START_MINISTACK = $(DC) up -d ministack >/dev/null 2>&1
ministack:
	@$(START_MINISTACK)
