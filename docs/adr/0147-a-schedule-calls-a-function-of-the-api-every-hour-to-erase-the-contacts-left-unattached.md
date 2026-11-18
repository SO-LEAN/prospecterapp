# 0147. A schedule calls a function of the api every hour to erase the contacts left unattached, the console locally

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/infra/schedule.tf`, `apps/api/src/Prospecting/EraseContactsLeftUnattachedFunction.php`, `apps/api/src/Prospecting/ContactsLeftUnattachedEraser.php`, `apps/api/src/Prospecting/Command/EraseContactsLeftUnattachedCommand.php`, `apps/api/src/Prospecting/Persistence/DynamoContactsToAttachRetriever.php`

## Context

A contact to attach nobody attaches is erased from the start of the 30th day after their last meeting (ADR 0146). No gesture of a member happens then: until now every change of the platform followed a request of the SPA, a command of the console, or a reaction of the worker (ADR 0101). MiniStack runs no schedule, as it runs no rule of the bus with its retries (ADR 0131).

## Decision

- On an instance, an EventBridge Scheduler schedule calls every hour a function of the package of the api (`prospecter-<instance>-api-erase-unattached`), its handler a service of its container like the relay and the worker (`App\Prospecting\EraseContactsLeftUnattachedFunction`), with the data statements and the environment of the api.
- The function lists the contacts to attach whose moment of erasure has passed, in every account, from `GSI2` of their items (`UNATTACHED_ERASURE`, by that moment), and gives each the command `EraseContactLeftUnattached` on the command bus: each in its own unit of work, its commit publishing what follows through its outbox, like any command. The command does nothing for a contact attached, erased already, or not yet due: a run twice, or two runs at once, erase once.
- The schedule retries nothing: the next run, an hour later, finds every contact whose day has passed.
- The console runs the same (`app:contacts:erase-unattached`): by hand on the local stack, which has no schedule, and on an instance whose schedule missed some; `app:consistency:check` reports a contact to attach past their day, the command correcting it.

## Consequences

- A contact is erased within the hour their day starts; the alerts of the SPA say the day.
- Locally, nothing is erased unless the command is run: the local stack differs from AWS by the schedule (`local-vs-aws.md`).
- One partition holds the contacts to attach of every account, read up to a moment: some hundreds at most, each erased within the hour.
- The function and its schedule are created by the apply of the code that brings them.

## Alternatives considered

The ticket named EventBridge Scheduler or a cron; on AWS, the schedules of EventBridge Scheduler are that cron. No other was weighed.

## Revisit when

Another task must run without a member at its origin: the schedule and its function become one for every such task. Or MiniStack runs the schedules of EventBridge Scheduler.
