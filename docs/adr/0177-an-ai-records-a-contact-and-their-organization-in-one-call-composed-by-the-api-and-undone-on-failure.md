# 0177. An AI records a contact and their organization in one call, composed by the API and undone on failure

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Identification/{ContactRecorder,Mcp/RecordContactTool}.php`, `packages/core/src/PersonalData/Application/{CompleteContact,CompareWithContact}`, `packages/core/src/Prospecting/Application/{CompleteOrganization,CompareWithOrganization}`, `packages/core/src/SharedKernel/Domain/Completion.php`, `packages/core/features/{completer-un-contact,completer-une-organisation}.feature`, `apps/api/tests/Api/RecordContactToolApiTest.php`

## Context

Once it knows who Prospecter knows (0173), an AI records a person and the organization they work for. Done tool by tool, it would make four or five calls, any of them failing halfway, and decide alone what to fill. The gesture spans two contexts, Prospecting and PersonalData, which never use each other, and the core runs one aggregate per use case, in one unit of work (0042, 0095): no use case of the core can write both. The member must never lose what they noted, and must learn what the AI filled and what it saw otherwise.

## Decision

- **The API composes the gesture**, `Identification\ContactRecorder` beside `IdentificationReader` (0173), for the MCP tool `enregistrer-un-contact`, without an endpoint: an exception to « a gesture runs one use case » (0100). Everything is read first: the organization, then the person, identified by their keys or retained by the member (`organizationId`, `personId`), or said another one (`newOrganization`, `newPerson`); what a known one would get filled. One to confirm writes nothing: the answer gives the candidates. Then the writes, each use case in its own unit of work: the organization started (its SIRET noted after, its start not taking it) or completed, then the contact completed and attached unless the organization names them already, or collected for it, the last write.
- **Idempotent step by step**: called again, the call finds by their keys what the first one wrote and creates nothing more. The reaction attaching a contact collected leaves alone an organization naming them already, attached meanwhile by a second call.
- **Undone on failure**: a step refused or failing once the organization was created, the call stops prospecting it (`StopProspecting`, the gesture of the domain erasing it, its terminal fact reaching Reporting and Campaigns), then answers the failure. The contact being collected by the last write, a contact created never has a later step to fail. What existed before the call is never touched: a completion already written stays, having filled only what was empty.
- **Completing never overwrites**, a rule of the core: `CompleteContact` (PersonalData, with the right to manage personal data) and `CompleteOrganization` (Prospecting) fill what is empty, the links while there is room among the five, nothing changing when nothing is to fill; uniqueness checked as when a member notes it.
- **What was completed and what differed** is answered by two queries asking the aggregate itself, restored by its repository, the rule its completion follows (`Person::completionWith()`, `Organization::completionWith()`, a `Completion` of the shared kernel): `CompareWithContact`, `CompareWithOrganization`, read before the commands, so that the answer and the write never differ. An exception to the queries reading a read model without restoring the aggregate (0088).

## Consequences

- The AI makes one call, and the member never loses what they noted; the tool answers the names of the fields, never their values.
- The rules of the gesture (found, created, completed, to confirm, twice, compensated) are proven by the API tests of the tool, not by a feature of the core; those of the completion by the features of the core.
- The compensation is no transaction: between the creation and its undoing, the reactions to the organization created may already have run (its facts, its tags); its erasure is followed by theirs. A failure of the compensation itself leaves the organization for the next call to find.
- The queries restoring the aggregate break with 0088 for one rule: this choice is to be reviewed, the maintainer not keen on it.

## Alternatives considered

- An application layer of the core above the contexts, dispatching the commands of both and keeping the rules in a feature: weighed in the plan, not kept, it would change `BoundedContextsTest` and the rules of the core.
- A process of the core reacting to the events: the answer of the call (created, completed, to confirm) would not be known within it.
- For what differed, the rule written again on the read model of the card (0088 kept, the rule duplicated), or a comparison of the read models before and after in the API (a rule in the API): not kept.

## Revisit when

A screen of creation or the import of Gmail records a contact and their organization too, or the comparison of the aggregates is to be replaced by a read model.
