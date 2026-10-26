# The Terraform of the app of the directory including it (apps/<app>/infra, ADR 0059, 0080), after make/aws.mk
INFRA_APP := $(notdir $(CURDIR))
# each instance keeps its own working directory, so that two instances can be planned at once; an apply asks for
# confirmation on the terminal, which the CI has not (CI is set there, with -auto-approve); the version of the code
# of the app (ADR 0063): a new one makes Terraform redeploy its package from build/
TERRAFORM_TTY = $(if $(CI),,-it)
TERRAFORM = docker run --rm -u $(UID):$(GID) -e HOME=/tmp --env-file $(INFRA_INSTANCE).env -e TF_DATA_DIR=.terraform/$(INSTANCE) \
	-e TF_VAR_code_version=$(call code-version,$(INFRA_APP)) \
	-v $(HOME)/.aws:/tmp/.aws -v $(ROOT):/src -v $(INFRA_CONFIG):/config:ro -w /src/apps/$(INFRA_APP)/infra

.PHONY: infra-instance infra-init infra-plan infra-apply infra-lint

##@ Infrastructure
# INSTANCE given on the command line only: a variable left in the shell would silently change the target
infra-instance:
	@test "$(origin INSTANCE)" = "command line" -a "$(words $(INSTANCE))" = 1 || { echo "Usage: make infra-plan INSTANCE=<dev|prod|...>, on the command line"; exit 1; }
	@test -f $(INFRA_INSTANCE).tfvars -a -f $(INFRA_INSTANCE).tfbackend -a -f $(INFRA_INSTANCE).env || { echo "No values for the instance $(INSTANCE): $(INFRA_INSTANCE).tfvars, .tfbackend and .env are needed (docs/infra.md), INFRA_CONFIG names their directory"; exit 1; }

infra-init: infra-instance ## Prepare the Terraform of the app for an instance: make infra-init INSTANCE=dev
	@$(TERRAFORM) $(TERRAFORM_IMAGE) init -reconfigure -input=false -backend-config=/config/envs/$(INSTANCE).tfbackend -backend-config=key=$(INFRA_APP)/$(INSTANCE).tfstate

infra-plan: infra-init ## Show what Terraform would change for an instance: make infra-plan INSTANCE=dev
	@$(TERRAFORM) $(TERRAFORM_IMAGE) plan -input=false -var-file=/config/envs/$(INSTANCE).tfvars $(INFRA_PLAN_FLAGS)

infra-apply: infra-init ## Apply the Terraform of the app to an instance, after confirmation: make infra-apply INSTANCE=dev
	@$(TERRAFORM) $(TERRAFORM_TTY) $(TERRAFORM_IMAGE) apply -input=false -var-file=/config/envs/$(INSTANCE).tfvars $(INFRA_APPLY_FLAGS)

infra-lint: ## Check the Terraform of the app: format, validity, tflint
	$(call infra-lint,$(INFRA_APP))
