# 0116. Each checkout runs its own stack, the worktrees within a budget; the journeys run in the integration

- Date: 2026-11-14
- Type: architecture
- Status: Accepted
- Supersedes: for the journeys staying out of the integration, [0057](0057-continuous-integration-around-dev.md)
- Implemented by: `docker-compose.yml`, `docker-compose.host.yml`, `docker-compose.agents.yml`, `make/common.mk`, `Makefile` (`stop`, `down`, `agents-slice`, `check-e2e`, `ci`), `apps/front/Makefile` (`e2e`), `make/changed-checks.sh`, `.github/workflows/ci.yml`

## Context

Several agents work at once, each in a worktree, but the machine ran one stack: its containers had fixed names (`pro-*`), it published the ports 80, 443 and 4566 of the host, `make stop` removed the functions of every MiniStack, and its Lambdas mount the code of one checkout. The agents waited their turn for it to run the journeys, the only check needing it (the tests of the API and the storage have a MiniStack of their own, 0109). The user asked that each agent run its own stack without one agent taking the workstation, reusing the images, and to look for a better way: the journeys were moved to the integration as well.

## Decision

- The stack of a checkout is its own Compose project, named after its directory, without a fixed container name. The main checkout publishes it on the ports of the host (`docker-compose.host.yml`); a worktree publishes nothing (`docker-compose.agents.yml`): its journeys already run in its Docker network, the local hosts resolved to its nginx (0084), so that the domains, the certificate, the CORS and Cognito stay the same. make picks the file (`make/common.mk`).
- `make stop` removes only the functions of its MiniStack (the label `ministack.instance`); `make down` removes the stack of a worktree done with, and its data.
- The images are shared by the checkouts, tagged with a digest of their build context: a checkout changing one builds its own, the others keep theirs; so are the caches of Composer and pnpm.
- The containers of a worktree join the slice of systemd `prospecter-agents.slice`, whose budget together (10 CPUs, 40 GB, a lower weight than the rest of the machine) `make agents-slice` writes once with sudo; each container is bounded too (`cpus`, `mem_limit`), the functions to the 1024 MB of AWS, kept warm 10 minutes instead of an hour.
- The journeys are a check of the integration (`check-e2e`): a change of what the stack runs (the core, the apps, the images) starts the stack on the runner, recreates its data and runs them; locally, `make ci` leaves them to the integration, `make e2e` running them on the stack of the checkout.

## Consequences

- Agents no longer wait for one another: each pushes and reads the check `e2e`, or runs `make e2e` on its own stack to debug a journey.
- A pull request touching the code lasts its journeys, after the stack started on the runner, its data recreated and its figures counted (two minutes, 0106).
- MiniStack 1.5.27 passes the memory to the functions it starts, neither their CPU nor their cgroup: they stay out of the slice, bounded by their memory and their warm TTL only.
- A worktree removed without `make down` leaves its volumes and its containers.
- The wrapper serialising the shared stack in the tooling of the agents, outside this repository, has to give each worktree its own stack.

## Alternatives considered

- One MiniStack shared, its resources prefixed per worktree: less memory, but the API, the deployment and the commands would take a prefix, one MiniStack stays the bottleneck, and the `make db` of an agent touches the others. Weighed by the assistant and the user, set aside.
- A pool of stacks lent to the agents: a MiniStack mounts the code of one checkout in its functions, lending it means restarting it. Set aside.
- Ports of the host varying per stack: useless to the journeys, in the network of their stack, and the CORS, Cognito and the domains would take the port. Set aside.
- Bounding the functions running at once (`LAMBDA_ACCOUNT_CONCURRENCY`): MiniStack refuses an invocation beyond it without waiting, and a rule does not retry it: a reaction would be lost. Set to unbounded instead, its default being derived from the memory of its container.

## Revisit when

The integration lasts the journeys too long, MiniStack passes the CPU or the cgroup of its functions, or the machine still saturates with the agents at work.
