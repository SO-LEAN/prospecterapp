# 0113. A to-do is an aggregate of Prospecting, kept like an exchange, erased with its contact

- Date: 2026-11-13
- Type: domain
- Status: Accepted
- Supersedes: for the lists of to-dos, the slices after a cursor of [0049](0049-lists-in-slices-after-a-cursor.md)
- Implemented by: `packages/core/src/Prospecting` (`ToDo`, `Due`, `ToDoIntent`, `ToDoPossibilities`, `ToDoRepository`, `NoteToDo`, `NoteOrganizationToDo`, `ReviseToDo`, `TickOffToDo`, `UntickToDo`, `EraseToDo`, `ConsultToDo`, `ConsultToDos`, `ConsultOrganizationToDos`, `ConsultMyToDos`, `ToDoListRetriever`, `StopKeepingToDos`, `EraseToDoLeftWithoutOrganization`, `EraseToDosOfForgottenContact`), `features/*-action-a-faire*.feature`, `apps/api/src/Prospecting/Persistence` (`ToDoDocument`, `DynamoToDoRepository`, `DynamoToDoListRetriever`), `apps/api/src/ToDos`

## Context

The prospectors want to note what they have to prepare for a contact or an organization (send a CV, prepare a proposal), due on a day, sometimes at an hour, be reminded of it, then tick it off. It is no exchange: nobody is in front, nothing happened in the real world yet. Its text may hold personal data, like the text of an exchange (ADR 0074, 0078).

## Decision

- A `ToDo` is an aggregate of Prospecting: its text (never empty), when it is due (`Due`: a day, an hour of it when said, as the prospector reads them on their clock, without a time zone), the member who noted it, who holds it, the contact or the organization it is for, and when it was ticked off.
- Like an exchange, the to-do of a contact is kept for the organizations the contact is part of when it is noted, and goes with the last of them (`StopKeepingToDos` reacts to `ProspectingStopped`, then `EraseToDoLeftWithoutOrganization`); the to-do of an organization is kept for it alone. The card of an organization reads its to-dos and those of its contacts.
- Unlike an exchange (ADR 0079), the to-dos of a forgotten contact are erased (`EraseToDosOfForgottenContact` reacts to `ContactForgotten`): they are no commercial history, and their text may hold the personal data the forgetting must remove.
- Every gesture on a to-do, reading included, needs the right to manage personal data: an assistant without it neither reads nor notes them, rather than reading them flagged as the exchanges (ADR 0078); every prospector of the account with the right ticks off, revises or erases any of them, the holder being who noted it, not who alone may act.
- A list of to-dos, of a contact, of an organization or of a member (those still to do), is read whole, never sliced (ADR 0049): a handful each; those to do the soonest due first, a day without an hour before its hours, then those done, the last done first (`ToDoList`).
- In the table of Prospecting: the item `TODO#<id>` / `TODO`, listed in `GSI1` among those of its contact (`CONTACT_TODOS#<account>#<person>`) and in `GSI2` among those its holder still has to do (`STILL_TO_DO#<member>`), and the links `TODOS#<organization>` / `TODO#<id>`.

## Consequences

- The calendar, the list of what is to handle and the reminders (to come) read the to-dos of a member through `ConsultMyToDos`.
- A contact with hundreds of to-dos would be read whole: slicing the lists is the change to make then.
- The to-dos are not counted by Reporting.

## Alternatives considered

Weighed while developing:

- Slicing the lists of to-dos after a cursor, as every list (ADR 0049): it needed keys ordered by the state and the due day, the done ones the last done first, for lists of a handful; reading them whole was kept, slicing being the change to make when they grow.
- Letting a prospector without the right to manage personal data read the to-dos, their text flagged, as the exchanges (ADR 0078): a to-do is nothing but its text, a flagged one tells nothing; the to-dos are not offered to them.
- Keeping the to-dos of a forgotten contact to make conform, as its exchanges (ADR 0079): a to-do is no history, erasing it is the conformity.

## Revisit when

A to-do is assigned to a colleague or recurs, or the lists of to-dos grow beyond a handful.
