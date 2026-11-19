# 0161. One daily schedule runs every scheduled task, each isolated

- Date: 2026-11-19
- Type: architecture
- Status: Accepted
- Supersedes: for the schedule every hour, [0147](0147-a-schedule-calls-a-function-of-the-api-every-hour-to-erase-the-contacts-left-unattached.md); for a schedule per task naming it in the input of the function, [0154](0154-gmail-is-pushed-through-a-topic-of-the-google-cloud-of-the-account.md)
- Implemented by: `apps/api/src/Shared/ScheduledTasks.php`, `apps/api/src/Shared/ScheduledTasksFunction.php`, `apps/api/src/Shared/Command/RunScheduledTasksCommand.php`, `apps/api/infra/schedule.tf`

## Context

Two tasks run without a member at their origin, in the function of ADR 0154: the erasure of the contacts left unattached (ADR 0146), on a schedule every hour (ADR 0147), and the renewal of the watches of Gmail, on a schedule every day. Each new task meant a schedule more in the Terraform, and each instance as many rhythms to watch. Erasing within the hour after the 30th day adds nothing to the rule of ADR 0146: a day is enough. Both schedules cost nothing either way, within the free tier.

## Decision

- On an instance, one schedule of EventBridge Scheduler, `prospecter-<instance>-api-scheduled`, calls the function of the scheduled tasks once a day (`rate(1 day)`), without input and without retry.
- The function runs every `App\Shared\ScheduledTask` of the container (`App\Shared\ScheduledTasks`), one after the other, each isolated: a task failing is logged at the level `error` with its name and its exception, the following ones run, and the answer of the function names it among `failed`, next to what each other task counted (`ran`). The invocation itself does not fail.
- A new task is a class of the api implementing `ScheduledTask`: nothing to change in the Terraform.
- Locally, MiniStack running no schedule, `app:scheduled-tasks:run` does the same, failing when a task failed; `app:contacts:erase-unattached` and `app:gmail:renew-watches` still run each task alone.

## Consequences

- A contact left unattached is erased within the 24 hours following the start of their 30th day, no longer within the hour; the alerts of the SPA still say the day.
- A run missed is made up by the next, a day later: each task does all that is due at its moment (every contact whose day has passed, every watch to renew before its week ends).
- A task failing no longer shows in the metric `Errors` of the function: only its logs say so, and no alarm watches them, as none watches the queues of the failures (`messaging.md`).
- The tasks share the timeout of one invocation (300 s): some hundreds of commands each at most today.
- The apply of the code bringing it destroys the two former schedules and creates this one.

## Alternatives considered

None: the ticket named one daily schedule.

## Revisit when

A task must run more often than once a day, or the tasks together approach the timeout of the function.
