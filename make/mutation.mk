# The tests of mutation of a branch (ADR 0091): only the lines it adds since BASE are mutated, committed or not, a new
# file once added to git, and the mutants its tests let survive are listed; no minimum score, a survivor is killed by a
# test or justified
BASE ?= origin/dev
SINCE = $(shell git merge-base $(BASE) HEAD)

# the hunks of the lines the branch adds in the files of a git pathspec, with paths relative to the project
ADDED_LINES = git diff -U0 --diff-filter=AM --relative $(SINCE) -- $(1)
# true when the branch adds at least one line in the files of a git pathspec: a hunk whose new side counts lines
ADDS_LINES = $(ADDED_LINES) | grep -qE '^@@ -[0-9,]+ \+[0-9]+(,[1-9][0-9]*)? @@'
NO_ADDED_LINE = echo "No line added in $(1) since $(BASE): no mutant to test"

# Infection reads the diff with git in the php container, the coverage measured by Xdebug: in a worktree, .git only
# points to the common git directory, out of the checkout, mounted at its own path
# INFECTION_THREADS= a number of threads, given on the command line, gives a worktree's php container as many
# processors, and a GB per thread and one more (2 and 2 GB otherwise, docker-compose.agents.yml); max, the default,
# takes as many threads as the host has processors
INFECTION_THREADS ?= max
THREADS_ASKED = $(and $(filter command line environment,$(origin INFECTION_THREADS)),$(filter-out max,$(INFECTION_THREADS)))
INFECTION = $(if $(THREADS_ASKED),PHP_CPUS=$(THREADS_ASKED) PHP_MEMORY=$$(($(THREADS_ASKED) + 1))g) $(DC) --progress quiet run --rm --no-deps -e XDEBUG_MODE=coverage -v $(GIT_COMMON_DIR):$(GIT_COMMON_DIR):ro -w $(WORKDIR) php \
	vendor/bin/infection --threads=$(INFECTION_THREADS) --git-diff-lines --git-diff-base=$(SINCE) --show-mutations=max --no-progress

# the mutants of the PHP lines a branch adds in the code a project mutates, its survivors printed with their diff: src,
# and what a project adds to it (MUTATED_PHP, git pathspecs from the project), as Infection's source directories do
MUTATED_PHP ?= 'src/*.php'
ADDS_PHP_LINES = git diff -U0 --diff-filter=AM $(SINCE) -- $(MUTATED_PHP) | grep -qE '^@@ -[0-9,]+ \+[0-9]+(,[1-9][0-9]*)? @@'
define infection-on-added-lines
	@if $(ADDS_PHP_LINES); then $(INFECTION); else $(call NO_ADDED_LINE,$(MUTATED_PHP)); fi
endef

# the mutants Infection skipped, their covering tests slower than its timeout in its first run, counted from its summary
# (logs.summaryJson) when it ran: their lines are judged by no test, so the target fails when there is one
SKIPPED_MUTANTS = grep -o '"skippedCount":[0-9]*' $(1) 2>/dev/null | cut -d: -f2
define fail-on-skipped-mutants
	@skipped=$$($(call SKIPPED_MUTANTS,$(1))); \
	if [ -n "$$skipped" ]; then echo "Skipped mutants: $$skipped"; fi; \
	if [ "$${skipped:-0}" -gt 0 ]; then echo "Their lines are judged by no test: see docs/architecture/tests.md, Mutation testing"; exit 1; fi
endef
