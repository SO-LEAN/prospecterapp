# What reaches an instance on AWS (ADR 0059, 0080). The values of an instance stay out of the repository, in
# envs/<instance>.{tfvars,tfbackend,env} of the configuration directory
INFRA_CONFIG ?= $(abspath $(ROOT)/../prospecterapp-infra)
INFRA_INSTANCE = $(INFRA_CONFIG)/envs/$(INSTANCE)
# the temporary credentials of the AWS profile of an instance (envs/<instance>.env), from this machine
AWS_CLI_IMAGE = amazon/aws-cli:2.37.2
AWS_CLI_RUN = docker run --rm -u $(UID):$(GID) -e HOME=/tmp --env-file $(INFRA_INSTANCE).env -v $(HOME)/.aws:/tmp/.aws
AWS_CLI = $(AWS_CLI_RUN) $(AWS_CLI_IMAGE)

# the apps deploying the core with their code: those requiring it
requires-core = $(shell grep -l '"prospecter/core"' $(ROOT)/apps/$(1)/composer.json 2>/dev/null)
# the version of the code an app deploys (ADR 0063, 0083): a digest of its tracked files, and of the core when it requires
# it, without its infrastructure, its tests, its scripts and its Makefile, so that only a change of what runs
# redeploys it
code-version = $(shell cd $(ROOT) && git ls-files -s -- apps/$(1) $(if $(call requires-core,$(1)),packages/core) \
	':(exclude,glob)apps/*/infra/**' ':(exclude,glob)apps/*/script/**' ':(exclude,glob)**/tests/**' ':!:packages/core/features' \
	':(exclude,glob)apps/*/Makefile' ':!:packages/core/Makefile' | sha256sum | cut -c1-64)

# Terraform and tflint run in their images, each app keeping its root in apps/<app>/infra
TERRAFORM_IMAGE = hashicorp/terraform:$(shell cat $(ROOT)/.terraform-version)
TFLINT_IMAGE = ghcr.io/terraform-linters/tflint:v0.64.0

# format, validity and tflint of the Terraform roots of the apps given
define infra-lint
@docker run --rm -u $(UID):$(GID) -e HOME=/tmp -e TF_PLUGIN_CACHE_DIR=/tmp/plugins -v $(ROOT):/src -w /src --entrypoint sh $(TERRAFORM_IMAGE) -c \
		'mkdir -p /tmp/plugins && { [ ! -d /src/infra ] || terraform fmt -check -diff -recursive /src/infra; } && for app in $(1); do cd /src/apps/$$app/infra && terraform fmt -check -diff && TF_DATA_DIR=/tmp/data/$$app terraform init -backend=false -input=false >/dev/null && TF_DATA_DIR=/tmp/data/$$app terraform validate -no-color >/dev/null || exit 1; done'
@docker run --rm -u $(UID):$(GID) -e HOME=/tmp -e GITHUB_TOKEN -v $(ROOT):/src -w /src --entrypoint sh $(TFLINT_IMAGE) -c \
		'for app in $(1); do tflint --chdir=apps/$$app/infra --init >/dev/null && tflint --chdir=apps/$$app/infra || exit 1; done'
endef
