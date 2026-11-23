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
# the local authority of the machine and the certificate it signs (make ca), in the main checkout: the stacks of
# every checkout serve that certificate, a port changing nothing to it
MAIN_CHECKOUT := $(or $(patsubst %/.git,%,$(GIT_COMMON_DIR)),$(ROOT))
export LOCAL_CA_DIR := $(MAIN_CHECKOUT)/docker/data/ssl
# a container run beside the stack goes where its stack is
STACK_CGROUP = $(if $(IN_WORKTREE),--cgroup-parent=$(AGENTS_SLICE))
# the images are shared by the checkouts, tagged with a digest of their build context: a checkout changing one builds its
# own, the others keep theirs; named on the host only, a make run in a container starting none (the image of php has no
# find)
ifeq ($(wildcard /.dockerenv),)
image-digest = cd $(1) && { find docker/$(2) -type f | LC_ALL=C sort | xargs sha256sum; echo $(UID):$(GID); } | sha256sum | cut -c1-12
image-tag = $(shell $(call image-digest,$(ROOT),$(1)))
export PHP_IMAGE := prospecter-php:$(call image-tag,php)
export NODE_IMAGE := prospecter-node:$(call image-tag,node)
export NGINX_IMAGE := prospecter-nginx:$(call image-tag,nginx)
export LAMBDA_DEPLOY_IMAGE := prospecter-lambda-deploy:$(call image-tag,lambda)
# the downloads of Composer and pnpm, shared by the stacks of every checkout (ADR 0116): volumes of no Compose project
# (external in docker-compose.yml), which make down keeps; created when missing, once per run of make, before its first
# docker compose
SHARED_CACHES = prospecter-composer-cache prospecter-pnpm-store
ifndef SHARED_CACHES_CREATED
export SHARED_CACHES_CREATED := $(shell for volume in $(SHARED_CACHES); do \
	docker volume inspect $$volume >/dev/null 2>&1 || docker volume create $$volume >/dev/null; done; echo 1)
endif
endif
# the whole repository is mounted in the containers: a project runs in its own directory there
WORKDIR = /var/www/src$(patsubst $(ROOT)%,%,$(CURDIR))
# a command prints its own output only, not the creation of its container: what the agents read stays short
# CLOCK_SHIFT=+365d: the command on the clock of the system moved by it, by the libfaketime of the images (ADR 0150),
# its monotonic clock left alone (the timeouts of the tests)
ON_THE_CLOCK = $(if $(CLOCK_SHIFT),-e LD_PRELOAD=/usr/local/lib/faketime/libfaketime.so.1 -e FAKETIME='$(CLOCK_SHIFT)' -e FAKETIME_DONT_FAKE_MONOTONIC=1)
PHP  = $(DC) --progress quiet run --rm --no-deps $(ON_THE_CLOCK) -w $(WORKDIR) php
NODE = $(DC) --progress quiet run --rm --no-deps $(ON_THE_CLOCK) -w $(WORKDIR) node
# the API and the storage run in Lambdas with the Bref layers (ADR 0055): the repository is mounted in the
# functions by its absolute path
export PROJECT_DIR := $(ROOT)

# $(call timed,<step>,<command>) runs the command, then prints how long its step took, so that a slow preparation shows
# its slowest step. It fails when the command fails
timed = { started=$$(date +%s); { $(2); }; status=$$?; echo "Time/$(1): $$(($$(date +%s) - started)) s"; test $$status -eq 0; }

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
