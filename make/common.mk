# What every Makefile of the repository includes (ADR 0080): Docker run from the directory of any project, and the
# list of the commands
ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))..)
HELP_MAKEFILE := $(lastword $(MAKEFILE_LIST))

export UID := $(shell id -u)
export GID := $(shell id -g)
MAKEFLAGS += --no-print-directory

# each checkout runs its own stack, its project named after its directory (ADR 0116): the main checkout publishes it on
# the ports of the host 80, 443 and 4566, a worktree, where an agent works, on a port of its own, in the budget of the agents.
# In a worktree, .git only points to the common git directory, out of the checkout mounted in the containers
GIT_DIR := $(shell git rev-parse --path-format=absolute --git-dir 2>/dev/null)
GIT_COMMON_DIR := $(shell git rev-parse --path-format=absolute --git-common-dir 2>/dev/null)
IN_WORKTREE := $(filter-out $(GIT_COMMON_DIR),$(GIT_DIR))
AGENTS_SLICE = prospecter-agents.slice
STACK_FILES = docker-compose.yml $(if $(IN_WORKTREE),docker-compose.agents.yml,docker-compose.host.yml)
DC = docker compose --project-directory $(ROOT) $(foreach file,$(STACK_FILES),-f $(ROOT)/$(file))
# the port of the host its hosts are published on: 443 for the main checkout, one of its own for a worktree
export PUBLIC_PORT := $(if $(IN_WORKTREE),$(shell $(ROOT)/make/stack-port.sh $(ROOT)),443)
PUBLIC_PORT_SUFFIX = $(if $(filter-out 443,$(PUBLIC_PORT)),:$(PUBLIC_PORT))
# a container run beside the stack goes where its stack is
STACK_CGROUP = $(if $(IN_WORKTREE),--cgroup-parent=$(AGENTS_SLICE))
# the images are shared by the checkouts, tagged with a digest of their build context: a checkout changing one builds its
# own, the others keep theirs
image-tag = $(shell cd $(ROOT) && { find docker/$(1) -type f | LC_ALL=C sort | xargs sha256sum; echo $(UID):$(GID); } | sha256sum | cut -c1-12)
export PHP_IMAGE := prospecter-php:$(call image-tag,php)
export NODE_IMAGE := prospecter-node:$(call image-tag,node)
export NGINX_IMAGE := prospecter-nginx:$(call image-tag,nginx)
export LAMBDA_DEPLOY_IMAGE := prospecter-lambda-deploy:$(call image-tag,lambda)
# the whole repository is mounted in the containers: a project runs in its own directory there
WORKDIR = /var/www/src$(patsubst $(ROOT)%,%,$(CURDIR))
# a command prints its own output only, not the creation of its container: what the agents read stays short
PHP  = $(DC) --progress quiet run --rm --no-deps -w $(WORKDIR) php
NODE = $(DC) --progress quiet run --rm --no-deps -w $(WORKDIR) node
# the API and the storage run in Lambdas with the Bref layers (ADR 0055): the repository is mounted in the
# functions by its absolute path
export PROJECT_DIR := $(ROOT)

.DEFAULT_GOAL := help
.PHONY: help ministack ministack-test

##@ Help
help: ## List the commands (the default target)
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-z0-9-]+:.*## / {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}' $(HELP_MAKEFILE) $(filter-out $(HELP_MAKEFILE),$(MAKEFILE_LIST))

# the AWS services emulated locally (DynamoDB, S3, Cognito; ADR 0048, 0051, 0052), started before what needs them
START_MINISTACK = $(DC) up -d ministack >/dev/null 2>&1
ministack:
	@$(START_MINISTACK)

# those of the tests of the API and the storage (.env.test): MiniStack again, of this checkout only, out of the stack
ministack-test:
	@$(DC) --progress quiet up -d --wait ministack-test
