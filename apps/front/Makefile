include ../../make/common.mk ../../make/aws.mk ../../make/mutation.mk

.PHONY: install test mutation lint fix build api-types event-storming-page e2e shell package release

##@ Front
install: ## Install the pnpm dependencies of the front
	@echo "Front/Installing pnpm dependencies..."
	@$(NODE) pnpm install

test: ## Run the tests of the front (Vitest); FILTER= a test file by its path or a part of it; FAIL_FAST=1 stops at the first failure; TEST_WORKERS= the workers (a number or a share of the processors; by default, one processor fewer than the container has)
	@$(NODE) pnpm test --reporter=dot --silent=passed-only $(if $(FAIL_FAST),--bail=1) $(if $(TEST_WORKERS),--maxWorkers=$(TEST_WORKERS)) $(if $(FILTER),'$(FILTER)')

# the code of the front a branch may change and a test may protect: neither the tests, the generated files, nor the
# components of shadcn, whose class names no test reads
MUTATED_CODE = 'src/*.ts' 'src/*.tsx' ':!*.test.ts' ':!*.test.tsx' ':!*.d.ts' ':!src/test/*' ':!src/app/routeTree.gen.ts' ':!src/shared/ui/*'
# the event storming's generator, mutated apart, its tests in a Stryker of their own (stryker.event-storming.json): they
# never share a VM worker with the SPA's (ADR 0210). It leaves out generate.ts, which only reads and writes the files
# that check-event-storming checks, and the walls built before the tests of the page's script (docs/architecture/tests.md)
MUTATED_GENERATOR = 'event-storming/*.ts' ':!*.test.ts' ':!event-storming/generate.ts' ':!event-storming/test/*'
# Stryker reads no git diff: the hunks of the added lines become its ranges, <file>:<first>-<last>, comma separated
ADDED_LINE_RANGES = $(call ADDED_LINES,$(1)) | awk '/^\+\+\+ b\// { file = substr($$0, 7) } \
	/^@@/ { split(substr($$3, 2), new, ","); count = new[2] == "" ? 1 : new[2]; \
	if (count > 0) { printf "%s%s:%d-%d", separator, file, new[1], new[1] + count - 1; separator = "," } }'

# Stryker runs one test runner per processor of the container, one fewer beyond four: 2 in a worktree, whose node
# container has 2 processors and 2 GB (docker-compose.agents.yml). MUTATION_WORKERS=<n> runs n runners, and gives the
# container n processors and n + 1 GB: 4 runners within 2 GB were killed in their first run
STRYKER = $(if $(MUTATION_WORKERS),NODE_CPUS=$(MUTATION_WORKERS) NODE_MEMORY=$$(($(MUTATION_WORKERS) + 1))g) $(NODE) pnpm exec stryker run $(if $(MUTATION_WORKERS),--concurrency $(MUTATION_WORKERS))

mutation: ## Mutate the lines the branch adds in src and in the event storming's generator since BASE (origin/dev), killed by the tests of Vitest; list the survivors; MUTATION_WORKERS= the test runners (one per processor of the container by default)
	@ranges=$$($(call ADDED_LINE_RANGES,$(MUTATED_CODE))); \
	if [ -n "$$ranges" ]; then $(STRYKER) --mutate "$$ranges"; else $(call NO_ADDED_LINE,src); fi
	@ranges=$$($(call ADDED_LINE_RANGES,$(MUTATED_GENERATOR))); \
	if [ -n "$$ranges" ]; then $(STRYKER) stryker.event-storming.json --mutate "$$ranges"; else $(call NO_ADDED_LINE,event-storming); fi

# TypeScript checks the whole project: the types of a file depend on the others
lint: ## Check the code of the front: Biome, TypeScript; FILTER= paths of the project, for Biome
	@$(NODE) sh -c 'pnpm exec biome check $(or $(FILTER),.) && pnpm typecheck'

fix: ## Fix the style of the code of the front: Biome and its rules; FILTER= paths of the project
	@$(NODE) pnpm exec biome check --write $(or $(FILTER),.)

build: ## Build the front for production (TypeScript, Vite)
	@$(NODE) pnpm build

api-types: ## Generate the types of the API from its OpenAPI contract (src/shared/api/schema.d.ts)
	@$(NODE) pnpm api:types

event-storming-page: ## Write every context's wall from the document of the models: DOCUMENT=<file>, a path of the front; CONTEXT= one context only
	@test -n "$(DOCUMENT)" || { echo "make event-storming-page DOCUMENT=<file> [CONTEXT=<context>]"; exit 1; }
	@$(NODE) node event-storming/generate.ts $(DOCUMENT) public/docs/event-storming ../../docs/event-storming $(CONTEXT)

# Playwright runs in its official image, in the network of the stack, the local hosts resolved to its nginx (by its
# /etc/hosts for Node, by STACK_ADDRESS for Chromium, playwright.config.ts): on the network of the host, its Chromium
# would drop its requests each time a function starts its container there (net::ERR_NETWORK_CHANGED). The nginx of the stack of this checkout (ADR 0116): its network and its address
NGINX = $(shell $(DC) ps -q nginx 2>/dev/null)
E2E_NETWORK = $(if $(NGINX),$(shell docker inspect -f '{{range $$name, $$network := .NetworkSettings.Networks}}{{$$name}}{{end}}' $(NGINX)))
NGINX_IP = $(if $(NGINX),$(shell docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $(NGINX)))
# in a worktree, bounded as the containers of its stack: two Chromium took 1.7 GB and five processors
E2E_BOUNDS = $(if $(IN_WORKTREE),--cpus=4 --memory=3g)

e2e: ## Run the Playwright journeys against the running stack; E2E_WORKERS= the journeys at once (2), E2E_ARGS= of Playwright, E2E_RESULTS= the folder of its report; FAILED=1 those that failed last (in that folder), FAIL_FAST=1 stops at the first failure
	@ip=$(NGINX_IP); test -n "$$ip" || { echo "No nginx in the stack of this checkout: is it running (make start)?"; exit 1; }; \
	docker run --rm --init --ipc=host $(STACK_CGROUP) $(E2E_BOUNDS) --network $(E2E_NETWORK) $(foreach host,prospecter api.prospecter static.prospecter,--add-host $(host).localhost:$$ip) -e STACK_ADDRESS=$$ip \
		-u $(UID):$(GID) -e HOME=/tmp -e npm_config_update_notifier=false -e PUBLIC_PORT -e E2E_WORKERS -e E2E_RESULTS $(if $(E2E_RUN),-e E2E_RUN=$(E2E_RUN)) -v $(CURDIR):/work -w /work mcr.microsoft.com/playwright:v1.64.0-noble npx playwright test $(if $(FAILED),--last-failed) $(if $(FAIL_FAST),--max-failures=1) $(E2E_ARGS)

shell: ## Open a shell in the Node container
	@$(NODE) sh

# the files of the front are a release, under releases/<version>/ of its bucket (ADR 0066)
RELEASE = releases/$(shell printf %.12s $(call code-version,front))

package: ## Package the front as a release in build/front at the root (ADR 0066)
	@$(NODE) sh -c 'pnpm install --frozen-lockfile --silent && pnpm build --outDir ../../build/front --emptyOutDir --base /$(RELEASE)/'

release: ## Put the release of the front in the bucket of an instance, before its apply; CLEAN=1 after it removes the former ones (ADR 0066)
	@test "$(origin INSTANCE)" = "command line" -a -f $(INFRA_INSTANCE).env || { echo "Usage: make release INSTANCE=<dev|prod|...> [CLEAN=1] (make release-front at the root), on the command line"; exit 1; }
	@test -f $(ROOT)/build/front/index.html || { echo "No release to put: make package (make package-front at the root) first"; exit 1; }
	@$(AWS_CLI_RUN) -v $(ROOT):/src -w /src --entrypoint sh $(AWS_CLI_IMAGE) apps/front/script/release $(INSTANCE) $(notdir $(RELEASE)) $(if $(CLEAN),--clean)

include ../../make/infra.mk
