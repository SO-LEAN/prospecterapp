# 0187. A change holds to the revision its client read

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Supersedes: for "the revision compared is the one the command read, not the one the screen or the AI read", [0181](0181-an-aggregate-is-written-under-the-lock-of-the-revision-its-command-read.md)
- Implemented by: `apps/api/src/Shared/Persistence/RevisionRead.php`, `RevisionsRead.php`, `RevisionLock.php`, `DynamoUnitOfWork.php`, `Table.php`, `apps/api/src/Shared/CommandBus.php`, `apps/api/src/Prospecting/Persistence/OrganizationRevisions.php`, `ExchangeRevisions.php`, `apps/api/src/PersonalData/Persistence/PersonRevisions.php`, the readers, controllers and tools of the organizations, contacts and exchanges, `apps/front/src/shared/forms/section-form.tsx`, `apps/front/src/features/exchanges/components/exchange-detail.tsx`

## Context

ADR 0181 locks the save of an organization, a person or an exchange on the revision its command reads. That stops two writes made at the same moment from erasing each other. It does not cover a screen loaded long before: a member opens Acme's card, another member or an AI changes its phone number, and the first member then saves the card with the old number, without anyone knowing. ADR 0181 left that case to its own ticket (backlog #243).

## Decision

- The revision stays outside the core (ADR 0048): the maintainer chose to keep it in the API (readers, resources, command bus), the rules proven by the API tests and Vitest.
- The resource of an organization, a contact and an exchange carries `revision`, the revision of the item the resource was read from (0 for an item written before the revisions). The reader asks the table to watch that item before reading it (`RevisionsRead`): only watched items are kept, for one request.
- The changes that overwrite a sheet's fields accept the revision their client read as the optional query parameter `revision` (an integer, 0 or more, 422 otherwise): the four forms of an organization (legal identity, contact details, address, observations), the four of a contact (name, title, job title, contact details), and the three corrections of an exchange (facts, text, venue), with their eleven MCP tools, which take a `revision` argument.
- With it, the command bus holds the unit of work to that revision: the first read of the item in the unit locks on the client's revision instead of the one it reads, so the check is the condition of the write itself, with no window between a check and the write. Changed since, the transaction is cancelled and the change refused with the 409 `changedMeanwhile` of ADR 0181. Without it, ADR 0181 applies as before.
- The revision is a query parameter, not a body field: the inputs of these changes are shared with creations (`StartProspectingInput` nests the contact details and the legal identity, `AddressInput` serves the member's own organization too), and the revision is a precondition of the change, not one of its fields.
- Additions (a contact collected or attached, a file attached) and the other gestures (move, cancel, mark done or missed, logo, holding, duplicates, removal) take no revision: ADR 0181 as before, the domain refusing what the record's state forbids.
- The SPA keeps the revision of the record when a form opens and sends it with the save; a refetch while the member types does not move it.

## Consequences

- A member or an AI saving a sheet read before another change is told the sheet changed and reads it again, instead of erasing that change.
- The revision changes with anything saved on the item: a contact attached to the organization, a reaction relocating it. A screen is then told to reload though the fields it edits did not change.
- A client that sends no revision is not protected; the SPA always sends one, an AI is told to by each tool's description.
- The organizations recognized by a phone number are read again by their id, to be shown with their revision.

## Alternatives considered

- ETag and If-Match: the MCP tools would have no header to give, and the SPA reads a field from its cached resource more simply.
- A field `revision` in the body of each change: refused because the inputs are shared with creations, where a revision means nothing.
- The revision in the core (its views and form commands, Behat scenarios on the doubles): weighed by the maintainer, who kept it beside the core (backlog #243).

## Revisit when

A change outside these eleven overwrites a sheet's fields, or the false refusals caused by other changes on the same item become a nuisance.
