include ../../make/common.mk ../../make/aws.mk

.PHONY: install test lint fix build api-types e2e shell package release

##@ Front
install: ## Install the pnpm dependencies of the front
	@echo "Front/Installing pnpm dependencies..."
	@$(NODE) pnpm install

test: ## Run the tests of the front (Vitest)
	@$(NODE) pnpm test

lint: ## Check the code of the front: Biome, TypeScript
	@$(NODE) sh -c 'pnpm lint && pnpm typecheck'

fix: ## Fix the style of the code of the front: Biome
	@$(NODE) pnpm format

build: ## Build the front for production (TypeScript, Vite)
	@$(NODE) pnpm build

api-types: ## Generate the types of the API from its OpenAPI contract (src/shared/api/schema.d.ts)
	@$(NODE) pnpm api:types

# Playwright runs in its official image, the local hosts resolved to the running stack
E2E_HOSTS = --add-host local.prospecter.io:127.0.0.1 --add-host api.local.prospecter.io:127.0.0.1 --add-host static.local.prospecter.io:127.0.0.1

e2e: ## Run the Playwright journeys against the running stack
	@docker run --rm --init --ipc=host --network host $(E2E_HOSTS) -u $(UID):$(GID) -e HOME=/tmp -v $(CURDIR):/work -w /work mcr.microsoft.com/playwright:v1.64.0-noble npx playwright test

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
