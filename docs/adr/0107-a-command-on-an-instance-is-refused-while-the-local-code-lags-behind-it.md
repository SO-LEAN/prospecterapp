# 0107. A command on an instance is refused while the local code lags behind it

- Date: 2026-11-12
- Type: architecture
- Status: Accepted
- Implemented by: `make/aws.mk` (`instance-code-check`, the default of the configuration directory), `apps/api/Makefile` (`console`)

## Context

`make console INSTANCE=<instance>` runs a command of the api from the machine of an administrator, on the tables and the user pool of the instance (ADR 0064): the code of the local checkout, not the code deployed. From a branch lagging behind `dev`, a command deployed was unknown, nothing saying why; a former command could have misread, or written, a former shape of the data of the instance. From a worktree, the configuration directory of the instances, by default next to the checkout, was looked for next to the worktree.

## Decision

- Before a command on an instance, the branch it deploys is fetched (`origin/main` for `prod`, `origin/dev` for the others): the command is refused while the local code lacks one of its changes, the message saying how to update it; `FORCE=1` runs it all the same.
- The changes are compared, not the commits (`git log --no-merges --cherry-pick --right-only HEAD...origin/<branch>`): `main` receives `dev` by merge commits, and its hotfixes come back to `dev` cherry-picked (ADR 0058), a branch of work containing neither.
- The default configuration directory is next to the main checkout, the parent of the common git directory: the same from a worktree.

## Consequences

- A command on an instance needs the network to reach the remote, and runs code at least as recent as the code deployed, local changes on top.
- A change of a hotfix reworded while cherry-picked to `dev` would look missing: `FORCE=1`.
- An instance other than `dev` and `prod` is taken as deploying `dev`.

## Alternatives considered

- Warn and run the command all the same: weighed by Michel, refused: a former command on newer data possibly damaging them; `FORCE=1` keeps the gesture possible.
- Running the command in the Lambda deployed, the code being then that of the instance: out of the scope of the ticket.

## Revisit when

The commands of an instance run in its Lambda rather than from a machine.
