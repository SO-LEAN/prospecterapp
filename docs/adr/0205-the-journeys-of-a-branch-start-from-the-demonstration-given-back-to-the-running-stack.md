# 0205. The journeys of a branch start from the demonstration given back to the running stack

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Supersedes: for `make check-e2e` running on the stack as it runs, and for when it starts from nothing, [0121](0121-the-journeys-run-apart-from-make-ci-and-stop-at-the-first-failure.md)
- Implemented by: `Makefile` (`check-e2e`, `STACK_DEFINITION`, `timed` in `make/common.mk`), `apps/api/Makefile` (`restore`), `docker/lambda/deploy.sh` (`apps`), `apps/api/src/Shared/Command/RestoreDemonstrationCommand.php`, `Tables::empty()`, `App\Demo\DemoMembers`

## Context

A check of the journeys from nothing (`FRESH=1`) lasted 13 to 14 minutes, and the journeys themselves 2.5. Since 0121, a run on the stack as it runs kept the data that the runs before left, so the first run of a branch used `FRESH=1`. One checkout at a time runs the journeys on the machine, so every minute also delays the developers waiting for the lock. Timed step by step, over three runs, the preparation spent its time on two things. The relay and the worker were deployed twice, in the deployment and again in `make db`, at 2.5 to 4 minutes each time: 46 subscriptions, each a rule and its target written by the AWS CLI. The figures were counted for 5 minutes. The tables, the user pool, the buckets and the queues took a few seconds. The API tests had already learnt to give the demonstration back instead of recreating it (ADR 0180).

## Decision

- `make check-e2e` gives the running stack of the worktree the branch and the demonstration back before the journeys:
  - It deploys the functions of the API and the storage again. It recycles the relay and the worker, which load the code once per container. A new variable in their environment makes MiniStack start their containers again.
  - It keeps the relay's mappings, the worker's queue and the bus rules. A new mapping would start from the oldest record of each stream and publish again what earlier runs wrote.
  - It empties the failure queues. Then `app:demo:restore` empties the tables, loads the demonstration again and gives the user pool its members back. The relay publishes what the demonstration loads, as after `make db`. The tags are rebuilt, and the figures are counted beside the journeys running together.
  - It keeps the containers, their images and the buckets.
- It starts from nothing, as `FRESH=1` did, in three cases: with `FRESH=1`, when no stack runs, or when what defines the stack changed since it was made. That definition is the compose files, the images (`docker/`), the subscriptions, the tables, the user pool and the bucket (`STACK_DEFINITION`). Once made, the stack keeps the definition's digest beside it.
- Each step of the preparation and of the journeys prints how long it took, and so does the whole run.

## Consequences

- Over three runs, a check on the running stack lasted 396 to 401 seconds. The preparation took about 37 of them. Every run starts from the demonstration, so no journey depends on what the run before left.
- Counting the figures is now the longest step: 5 minutes, with two waits of a minute (ADR 0106). It outlasts the journeys running together by some 2.5 minutes.
- A change of the stack's definition that `STACK_DEFINITION` does not list, such as an environment variable of the apps, reaches the functions all the same. A change that only a recreated resource takes needs `FRESH=1`.
- The files that the journeys upload stay in the bucket. No item names them.

## Alternatives considered

- Restoring the tables item by item from a snapshot, as `DataOfTheDemonstration` does in the API tests. That snapshot lives in the memory of a test process. On a stack, it would have to be kept between runs, and the reactions that the demonstration starts would have to be settled before it is taken. Loading the demonstration again on the emptied tables gives what `make db` gives.
- Deploying the relay and the worker again on every run. That would cost their 2.5 to 4 minutes, and the new mappings would publish the streams again from their oldest record.

## Revisit when

A journey fails after a restore but passes from nothing. Or the figures' count is made shorter, which would make the journeys running together the longest step again.
