#!/bin/sh
# The checks of the integration that the changed files on the standard input need (ADR 0110), one per line. A
# project's files need its own check. The core needs the API's and the contract's checks, and the API and the front
# need the contract's. The core, the API, the event storming's generator and its walls need the walls' check. What the
# stack runs needs the journeys (ADR 0116): make ci runs them in a worktree, never the CI (ADR 0118). A file every
# check depends on (the Makefiles, the images, the workflow) needs them all, and so does a file of no known project.
# The secrets of the branch and the references to the records are always checked. The parts of the code a pull
# request's CI mutates follow, named mutation-<part>.
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

# The parts the CI of a pull request mutates (ADR 0219), named mutation-<part>: those whose mutated code a file is,
# the code that make mutation-<part> mutates, its tests and generated files left out. make ci runs none.
mutations_of() {
	case $1 in
	packages/core/src/*.php | packages/phpstan-rules/EventStorming/*.php) echo mutation-core ;;
	apps/front/event-storming/*.test.ts | apps/front/event-storming/test/* | apps/front/event-storming/generate.ts) ;;
	apps/front/event-storming/*.ts) echo mutation-event-storming ;;
	apps/front/src/*.test.ts | apps/front/src/*.test.tsx | apps/front/src/*.d.ts | apps/front/src/test/* | apps/front/src/app/routeTree.gen.ts | apps/front/src/shared/ui/*) ;;
	apps/front/src/*.ts | apps/front/src/*.tsx) echo mutation-front ;;
	esac
}

{
	echo secrets
	while IFS= read -r file; do
		[ -n "$file" ] && checks_of "$file" && mutations_of "$file"
	done
} | tr ' ' '\n' | grep . | sort -u
