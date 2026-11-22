# 0191. Domain methods take four parameters at most, through argument objects

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/SharedKernel/Domain/Gesture.php`, `packages/core/src/Prospecting/Domain/Model/{Correspondent,Noting,Exchange,ExchangeFacts}.php`, `docs/architecture/clean-code.md` (section 2), `docs/architecture/core.md`, `docs/domain/prospecting.md`

## Context

The clean code guide asks for an argument object beyond three parameters (ADR 0022). Its rule had no threshold the code was checked against. In `packages/core/src`, 17 methods other than constructors took six parameters or more. Ten of them belonged to `Exchange` and `ExchangeFacts`, the factories of an exchange taking up to ten. Almost every factory repeated the same three values: the account, the member and the moment.

## Decision

- A public method of the domain takes four parameters at most. Constructors are exempt: a value object or an aggregate lists its fields there.
- Values that go together become an argument object named in the domain's words, never a bag of parameters.
- `Gesture`, in the shared kernel, carries who acts: the account, the member, the moment, and the `Origin`, by hand by default (ADR 0185). Every aggregate's factories take it.
- In Prospecting, `Correspondent` carries who an exchange is with and the organizations that keep it. It has three kinds: `ContactCorrespondent` (a contact and their organizations), `RecipientCorrespondent` (a campaign recipient, who also brings the campaign and its checks) and `OrganizationCorrespondent` (the organization itself). `Noting` carries what a member records about an exchange that took place: its facts and outcome, plus where the message was read or the imported e-mail when known.
- `Exchange` keeps three factories: `noted()`, `planned()` and `bookedByAContact()`. They replace eight factories, one per kind of correspondent and per origin. The types keep what the former signatures allowed: `noted()` takes any correspondent, `planned()` a contact or an organization, and `bookedByAContact()` a contact only. `ExchangeFacts::planned()` gets its venue from `withVenue()` and the request of a link from `askingALink()`.

## Consequences

- A call reads as who acts, on what, with what: `Exchange::noted($gesture, $id, $correspondent, $noting)`.
- One `noted()` serves a contact, an organization, a campaign recipient and an imported e-mail, so the rules they share are written once.
- The other aggregates' public methods over four parameters stay until a branch moves them to `Gesture` and their own argument objects. A PHPStan rule then keeps the threshold.

## Alternatives considered

- A threshold of five: the ticket left four or five open. Four won because the planned PHPStan rule already uses it.
- The campaign recipient in `Noting`, beside a `Correspondent` naming the same contact: the two could disagree, and a campaign exchange could have been noted with an organization. The review of the branch refused it.
- One factory per kind of correspondent, kept with their former names (`notedWithAContact()`, `importedWithTheOrganization()`...): once `Correspondent` and `Noting` existed, they had the same parameters and differed only by name.

## Revisit when

An argument object holds values that change for different reasons, or a method needs a fifth parameter that belongs to none of them.
