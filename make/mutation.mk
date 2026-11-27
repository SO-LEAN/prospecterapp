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

# SHARD=<i>/<n> mutates the i-th of n shares of the files whose lines the branch adds, so that the CI mutates a part in
# n jobs (ADR 0222). The files are dealt by the lines the branch adds to them, the biggest first, each to the share that
# holds the fewest lines so far. Without SHARD, one share holds them all
SHARD ?= 1/1
SHARE = $(word 1,$(subst /, ,$(SHARD)))
SHARES = $(word 2,$(subst /, ,$(SHARD)))
# "<lines> <file>" for each file of a diff (git diff -U0 on the standard input) that gains lines, the biggest first
LINES_BY_FILE = awk '/^\+\+\+ b\// { file = substr($$0, 7) } \
	/^@@/ { split(substr($$3, 2), new, ","); lines[file] += new[2] == "" ? 1 : new[2] } \
	END { for (file in lines) if (lines[file] > 0) print lines[file], file }' | sort -k1,1nr -k2,2
# "<share> <file>" for each line of LINES_BY_FILE
DEAL = awk -v shares=$(SHARES) '{ least = 1; for (share = 2; share <= shares; share++) if (load[share] < load[least]) least = share; \
	load[least] += $$1; print least, $$2 }'
# the files of a diff's hunks (the command that prints them) that the shard mutates, and those the other shards mutate
FILES_OF_SHARD = $(1) | $(LINES_BY_FILE) | $(DEAL) | awk '$$1 == $(SHARE) { print $$2 }'
FILES_OF_OTHER_SHARDS = $(1) | $(LINES_BY_FILE) | $(DEAL) | awk '$$1 != $(SHARE) { print $$2 }'
# how many shards the CI mutates a diff's hunks in: one per MUTATION_LINES_PER_SHARD added lines, at most
# MUTATION_MAX_SHARDS, and never more shards than files. Each shard runs all the tests once first, under coverage: a
# shard of few lines would cost more than it saves
MUTATION_LINES_PER_SHARD ?= 300
MUTATION_MAX_SHARDS ?= 3
SHARDS_OF = $(1) | $(LINES_BY_FILE) | awk -v per=$(MUTATION_LINES_PER_SHARD) -v most=$(MUTATION_MAX_SHARDS) \
	'{ lines += $$1; files++ } END { shards = int((lines + per - 1) / per); \
	if (shards > most) shards = most; if (shards > files) shards = files; print (shards > 1 ? shards : 1) }'

# Infection reads the diff with git in the php container, the coverage measured by Xdebug: in a worktree, .git only
# points to the common git directory, out of the checkout, mounted at its own path
# INFECTION_THREADS=<n>, given on the command line, runs n threads, and gives a worktree's php container n processors
# and n + 1 GB (2 and 2 GB otherwise, docker-compose.agents.yml); max, the default, takes as many threads as the host
# has processors
INFECTION_THREADS ?= max
THREADS_ASKED = $(and $(filter command line environment,$(origin INFECTION_THREADS)),$(filter-out max,$(INFECTION_THREADS)))
INFECTION = $(if $(THREADS_ASKED),PHP_CPUS=$(THREADS_ASKED) PHP_MEMORY=$$(($(THREADS_ASKED) + 1))g) $(DC) --progress quiet run --rm --no-deps -e XDEBUG_MODE=coverage -v $(GIT_COMMON_DIR):$(GIT_COMMON_DIR):ro -w $(WORKDIR) php \
	vendor/bin/infection --threads=$(INFECTION_THREADS) --git-diff-lines --git-diff-base=$$base --show-mutations=max --no-progress

# the mutants of the PHP lines a branch adds in the code a project mutates, its survivors printed with their diff: src,
# and what a project adds to it (MUTATED_PHP, git pathspecs from the project), as Infection's source directories do.
# The paths of the diff are from the root: MUTATED_PHP may reach out of the project
MUTATED_PHP ?= 'src/*.php'
ADDED_PHP_LINES = git diff -U0 --diff-filter=AM $(SINCE) -- $(MUTATED_PHP)
ADDS_PHP_LINES = $(call FILES_OF_SHARD,$(ADDED_PHP_LINES)) | grep -q .
# Infection reads the added lines from a diff of the working tree against the base it is given. A shard's base is a tree:
# the branch's base, with the other shards' files as the branch has them, so that only the shard's own files differ.
# Infection finds no merge-base of a tree, and diffs against the tree itself
PHP_SHARD_BASE = index=$$(mktemp -u) && GIT_INDEX_FILE=$$index git read-tree $(SINCE) \
	&& $(call FILES_OF_OTHER_SHARDS,$(ADDED_PHP_LINES)) | GIT_INDEX_FILE=$$index git -C $(ROOT) add --pathspec-from-file=- \
	&& GIT_INDEX_FILE=$$index git write-tree && rm -f $$index
PHP_DIFF_BASE = $(if $(filter-out 1,$(SHARES)),$$($(PHP_SHARD_BASE)),$(SINCE))
define infection-on-added-lines
	@if $(ADDS_PHP_LINES); then base=$(PHP_DIFF_BASE) && $(INFECTION); else $(call NO_ADDED_LINE,$(MUTATED_PHP)); fi
endef

# the mutants Infection skipped, their covering tests slower than its timeout in its first run, counted from its summary
# (logs.summaryJson) when it ran: their lines are judged by no test, so the target fails when there is one
SKIPPED_MUTANTS = grep -o '"skippedCount":[0-9]*' $(1) 2>/dev/null | cut -d: -f2
define fail-on-skipped-mutants
	@skipped=$$($(call SKIPPED_MUTANTS,$(1))); \
	if [ -n "$$skipped" ]; then echo "Skipped mutants: $$skipped"; fi; \
	if [ "$${skipped:-0}" -gt 0 ]; then echo "Their lines are judged by no test: see docs/architecture/tests.md, Mutation testing"; exit 1; fi
endef
