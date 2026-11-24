#!/bin/sh
# The checks of the integration the changed files read on the standard input need (ADR 0110), one per line: a
# project checks itself when its files change, the API and the contract the core they use, the contract the API and
# the front, the walls of the event storming the code they are drawn from and their generator; the journeys what the stack runs (ADR 0116), run by make ci in a worktree, never by the CI (ADR 0118); a
# file every check depends on (the Makefiles, the images, the workflow) needs them all, and so does a file of no known
# project. The secrets of the branch and the references to the records are always checked.
ALL="secrets infra core api storage front contract event-storming e2e"

checks_of() {
	case $1 in
	apps/*/infra/* | .terraform-version) echo infra ;;
	packages/core/*) echo core api contract event-storming e2e ;;
	packages/phpstan-rules/*) echo core api storage event-storming ;;
	apps/api/*) echo api contract event-storming e2e ;;
	apps/storage/*) echo storage e2e ;;
	apps/front/event-storming/* | apps/front/public/docs/event-storming/*) echo front event-storming ;;
	apps/front/*) echo front contract e2e ;;
	docker/php/*) echo core api storage contract event-storming e2e ;;
	docker/node/*) echo front contract event-storming e2e ;;
	# the walls generated from the code, which the repository's documents show
	docs/event-storming/*) echo event-storming ;;
	# the stack only
	docker/nginx/* | docker/lambda/*) echo e2e ;;
	# the deployments (their own workflows), the documentation
	.github/actions/* | .github/workflows/terraform-* | .github/workflows/backlog-*) ;;
	docs/* | *.md | LICENSE | .editorconfig | .gitignore | .gitattributes | .gitleaks.toml | .githooks/* | .claude/*) ;;
	*) echo "$ALL" ;;
	esac
}

{
	echo secrets
	while IFS= read -r file; do
		[ -n "$file" ] && checks_of "$file"
	done
} | tr ' ' '\n' | grep . | sort -u
