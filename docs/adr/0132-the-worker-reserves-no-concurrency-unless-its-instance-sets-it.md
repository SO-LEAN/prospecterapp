# 0132. The worker reserves no concurrency on AWS unless its instance sets it

- Date: 2026-11-16
- Type: architecture
- Status: Accepted
- Supersedes: for the default of `worker_concurrency` (10 on AWS), [0131](0131-the-worker-runs-a-bounded-number-of-invocations-at-once.md)
- Implemented by: `apps/api/infra/variables.tf` (`worker_concurrency`), `apps/api/infra/messaging.tf`

## Context

ADR 0131 gave the worker a reserved concurrency of 10 by default on AWS. AWS keeps a minimum of the executions of an account unreserved: an account limited to 10 executions at once refuses any reservation, and the apply of the `dev` instance failed at the first apply after it (`PutFunctionConcurrency`: `InvalidParameterValueException`). Raising the quota of the account is an AWS request (#101), not code; the validation of the variable (`>= 1`) left an instance no way to reserve nothing.

## Decision

`worker_concurrency` is null by default, and null reserves nothing: the worker keeps the unreserved concurrency of the account (`-1`), as before ADR 0131. An instance sets it in its tfvars once the quota of its account allows the reservation. The local stack keeps its cap (`WORKER_CONCURRENCY`, ADR 0131).

## Consequences

- The apply of an instance on an account limited to 10 passes again.
- Until its instance sets it, a burst of reactions may take every execution of the account from the api, the risk ADR 0131 removed; it comes back with the value set, after #101.

## Alternatives considered

None: the default of 10 cannot be applied on such an account, and the quota is not raised by code.

## Revisit when

Every instance runs on an account whose quota allows the reservation: the default can be a number again.
