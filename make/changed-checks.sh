#!/bin/sh
# The checks of the integration the changed files read on the standard input need (ADR 0110), one per line: a
# project checks itself when its files change, the API and the contract the core they use, the contract the API and
# the front; a file every check depends on (the Makefiles, the images, the workflow) needs them all, and so does a
# file of no known project. The secrets of the branch and the references to the records are always checked.
ALL="secrets infra core api storage front contract"

checks_of() {
	case $1 in
	apps/*/infra/* | .terraform-version) echo infra ;;
	packages/core/*) echo core api contract ;;
	packages/phpstan-rules/*) echo core api storage ;;
	apps/api/*) echo api contract ;;
	apps/storage/*) echo storage ;;
	apps/front/*) echo front contract ;;
	docker/php/*) echo core api storage contract ;;
	docker/node/*) echo front contract ;;
	# the stack and the journeys only (make e2e), the deployments (their own workflows), the documentation
	docker/nginx/* | docker/lambda/* | .github/actions/* | .github/workflows/terraform-* | .github/workflows/backlog-*) ;;
	docs/* | *.md | LICENSE | .editorconfig | .gitignore | .gitleaks.toml | .githooks/* | .claude/*) ;;
	*) echo "$ALL" ;;
	esac
}

{
	echo secrets
	while IFS= read -r file; do
		[ -n "$file" ] && checks_of "$file"
	done
} | tr ' ' '\n' | grep . | sort -u
