# 0105. A read-only check of the consistency reads the tables of every context

- Date: 2026-11-11
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Consistency`, `apps/api/tests/Consistency/CheckConsistencyCommandTest.php`, `apps/api/tests/Architecture/TablePerContextTest.php`, `apps/api/config/services.yaml`, `apps/api/Makefile`

## Context

The reactions between the contexts run after the command, in the worker (ADR 0101), and the figures of Reporting are kept from the facts the other contexts publish (ADR 0103, 0104). An event may end in a queue of failures, a reaction may be missed or a figure may drift, and nothing tells it: no alarm is planned. Each context reads only its own table (ADR 0038, 0048), and the others only through the services they publish (ADR 0045, 0089).

## Decision

- `bin/console app:consistency:check` (`--account=<id>` for one account), run on demand, from the command line on an instance (`make console INSTANCE=`), checks the result rather than watching the failures: the five figures of each account counted again from the tables of Accounts, PersonalData and Prospecting against the active version of Reporting; what the reactions between the contexts should have written (each account has its members, each member their profile, each contact collected is attached to organizations, none forgotten still is, no organization no longer prospected still keeps exchanges); the messages waiting in the queues of the failures of the worker and of the relay.
- It lists each discrepancy with its account, what it concerns and the command correcting it (`app:reporting:rebuild`, `app:integration:redrive`, `app:outbox:republish`), and fails when there is one; it corrects nothing.
- It counts again by direct requests on the tables (a `Query` counting on the indexes, the items read where a figure needs their content, a `Scan` where no index lists them), never through the services the contexts publish nor their retrievers: counting by the retrievers of the facts would repeat their bugs without seeing them.
- It lives out of the contexts, in `apps/api/src/Consistency`: `ContextTables` is the only code reading the tables of several contexts, and it only reads them, an exception `tests/Architecture/TablePerContextTest` names.

## Consequences

- A reaction missed or a figure gone wrong is seen by running one command, and corrected by the command it names.
- The check depends on the shape of the items of every context: it uses the keys of their persistence (`AccountsKeys`, `PersonalDataKeys`, `OrganizationKeys`, `ExchangeKeys`, `ReportingKeys`), and a change of a document changes it too.
- It reads every organization, exchange and contact of the accounts it checks: its cost grows with the data, acceptable on demand.

## Alternatives considered

Counting again through the services of facts the contexts publish, as the rebuild of Reporting does: it would share their bugs, hence not see them.

## Revisit when

The data grows beyond what a check on demand reads in reasonable time, or an alarm is wanted on the failures.
