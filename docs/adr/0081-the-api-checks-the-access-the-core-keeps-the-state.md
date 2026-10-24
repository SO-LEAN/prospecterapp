# 0081. The API checks the access to a resource with a voter; the core keeps its state and what a member reads

- Date: 2026-10-25
- Status: Accepted
- Supersedes: for the ownership of a resource and the right to manage personal data of an intention of Prospecting, [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md); for who decides the links, [0027](0027-hypermedia-links-from-the-possible-intents.md)
- Implemented by: `apps/api/src/Security/{AccountResourceVoter,ApiAccessDeniedHandler,IntentLinks}.php`, `apps/api/src/Prospecting/{OrganizationVoter,OrganizationResolver,TimelineVoter}.php`, `apps/api/src/Exchanges/{ExchangeVoter,ExchangeResolver}.php`, `packages/core/src/Prospecting/Domain/{Organization,Exchange}.php` (`possibleIntents()`, the intentions `by:` a `MemberId`)

## Context

Every use case of Prospecting received the id of the member who acts, and the aggregate decided whether the resource was theirs (`Organization::ensureReachableBy`, `Exchange::ensureReachableBy`, the `by:` of each intention, 0026), and whether their right to manage personal data allowed the intention. A use case could not be run without a signed-in member: from a command of the console, for instance. Michel wants the check that the signed-in member may reach a resource in the controller layer, by Symfony voters, as a general rule (review of the pull request #56).

## Decision

- **The API decides the access** with a voter per resource of Prospecting: `OrganizationVoter`, `ExchangeVoter`, and `TimelineVoter` for noting in a timeline. Its subject is the read model of the resource, the response of the query of the core, which carries the account of the resource; a value resolver reads it from the `id` of the route (`OrganizationResolver`, `ExchangeResolver`), and the controller declares `#[IsGranted(<intention>, subject: ...)]`, the attribute named after the intent of the core. The voter checks:
  - that the resource belongs to the account of the member: a resource of another account is unknown to them, a 404 named like the refusal of the core (`unknownOrganization`), the voter telling it in its vote to `ApiAccessDeniedHandler`;
  - the right to manage personal data when the intention needs it (managing the contacts of an organization, stopping to prospect one that has some, noting an exchange, correcting its facts, erasing it, its attachments): a 403.
- **The state stays the business of the core**: the aggregate says what its state offers now (`possibleIntents()`, without the member), and refuses the rest (`NotPossibleInItsState`, 409, with its reason for the client to word it, 0073). A presenter shows the link of an intention when both agree (`IntentLinks`): a link shown is a call accepted.
- **Prospecting** keeps its business rules (`NotAContact`, the uniqueness in an account, a contact never alone, a holding of the same account as the organization, a contact of the account of the organization). Its intentions take the member only as the author of what they record (`by:` a `MemberId`): the commands keep the id of the member who acts, kept by the events and the activity journal. What a member reads still depends on them, through `ProspectorService`: the text of an exchange flagged, its attachments hidden and their download refused without the right to manage personal data (0078), the timeline of a person read only when they are a contact of the account.
- **PersonalData** is unchanged: it decides what its reader sees and may do (masking, searching, collecting and forgetting a contact, the intents of a person) through `ReaderService`, and checks that a person belongs to the account of its reader. The personal data are never read without a member: by an administrator or a command of the console.
- **Accounts** is unchanged: the right to register a member stays decided by the aggregate (0075).
- The scenarios of the core keep the rules of the state; those of the access (unknown for another account, refused without the right) became tests of the API.

## Consequences

- A use case of Prospecting that changes an organization or an exchange runs without a signed-in member, given the member it records as the author.
- The rules of the access are no longer in the scenarios of the core but in the tests of the API; a client of the core other than the API checks them itself.
- The resolver reads the resource before the command, the controller reads it again to answer it: one more read by key per change.

## Alternatives considered

- The voter deciding the state too, the links deduced from the read model by the voter alone: a call its state does not offer would have been a 403 instead of the 409 naming the refusal of the core, which the client words.
- The aggregate computing the links for a member, the voters guarding only the access: Michel chose the links filtered by the voters.

## Revisit when

- A client other than the API (a command of the console) needs the rules of the access, or a resource is shared between accounts.
