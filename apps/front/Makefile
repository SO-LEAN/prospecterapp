include ../../make/common.mk ../../make/aws.mk ../../make/mutation.mk

.PHONY: install test mutation lint fix build api-types e2e shell package release

##@ Front
install: ## Install the pnpm dependencies of the front
	@echo "Front/Installing pnpm dependencies..."
	@$(NODE) pnpm install

test: ## Run the tests of the front (Vitest); FILTER= a test file by its path or a part of it
	@$(NODE) pnpm test --reporter=dot --silent=passed-only $(if $(FILTER),'$(FILTER)')

# the code of the front a branch may change and a test may protect: neither the tests, the generated files, nor the
# components of shadcn, whose class names no test reads
MUTATED_CODE = 'src/*.ts' 'src/*.tsx' ':!*.test.ts' ':!*.test.tsx' ':!*.d.ts' ':!src/test/*' ':!src/app/routeTree.gen.ts' ':!src/shared/ui/*'
# Stryker reads no git diff: the hunks of the added lines become its ranges, <file>:<first>-<last>, comma separated
ADDED_LINE_RANGES = $(call ADDED_LINES,$(MUTATED_CODE)) | awk '/^\+\+\+ b\// { file = substr($$0, 7) } \
	/^@@/ { split(substr($$3, 2), new, ","); count = new[2] == "" ? 1 : new[2]; \
	if (count > 0) { printf "%s%s:%d-%d", separator, file, new[1], new[1] + count - 1; separator = "," } }'

mutation: ## Mutate the lines the branch adds in src since BASE (origin/dev), killed by the tests of Vitest; list the survivors
	@ranges=$$($(ADDED_LINE_RANGES)); \
	if [ -n "$$ranges" ]; then $(NODE) pnpm exec stryker run --mutate "$$ranges"; else $(call NO_ADDED_LINE,src); fi

# TypeScript checks the whole project: the types of a file depend on the others
lint: ## Check the code of the front: Biome, TypeScript; FILTER= paths of the project, for Biome
	@$(NODE) sh -c 'pnpm exec biome check $(or $(FILTER),.) && pnpm typecheck'

fix: ## Fix the style of the code of the front: Biome
	@$(NODE) pnpm format

build: ## Build the front for production (TypeScript, Vite)
	@$(NODE) pnpm build

api-types: ## Generate the types of the API from its OpenAPI contract (src/shared/api/schema.d.ts)
	@$(NODE) pnpm api:types

# Playwright runs in its official image, in the network of the stack, the local hosts resolved to its nginx: on the
# network of the host, its Chromium would drop its requests each time a function starts its container there
# (net::ERR_NETWORK_CHANGED). The nginx of the stack of this checkout (ADR 0116): its network and its address
NGINX = $(shell $(DC) ps -q nginx 2>/dev/null)
E2E_NETWORK = $(if $(NGINX),$(shell docker inspect -f '{{range $$name, $$network := .NetworkSettings.Networks}}{{$$name}}{{end}}' $(NGINX)))
NGINX_IP = $(if $(NGINX),$(shell docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $(NGINX)))

e2e: ## Run the Playwright journeys against the running stack
	@ip=$(NGINX_IP); test -n "$$ip" || { echo "No nginx in the stack of this checkout: is it running (make start)?"; exit 1; }; \
	docker run --rm --init --ipc=host $(STACK_CGROUP) --network $(E2E_NETWORK) $(foreach host,local api.local static.local,--add-host $(host).prospecter.io:$$ip) \
		-u $(UID):$(GID) -e HOME=/tmp -e PUBLIC_PORT $(if $(E2E_RUN),-e E2E_RUN=$(E2E_RUN)) -v $(CURDIR):/work -w /work mcr.microsoft.com/playwright:v1.64.0-noble npx playwright test

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
