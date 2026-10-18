# 0073. A refusal is named by its class, and worded by the client in its language

- Date: 2026-10-18
- Status: Accepted
- Implemented by: `packages/core/src/*/Domain/Exception/*`, `UseCaseRefused::$reason` and `$context`, `apps/api/src/Shared/{UseCaseExceptionListener,Resource/ProblemResource,Resource/ViolationResource}.php`, `apps/front/src/shared/api/problem.ts`, `refusals` of `apps/front/src/shared/i18n/fr.ts`, `apps/api/tests/Architecture/RefusalsAreWordedTest.php`

## Context

The refusals of the domain reached the members as the domain words them, in English ("Phone number "03554411" is not valid", "Email already used"), the SPA showing the title of each violation as it came: the screens are in French.

## Decision

- A refusal worth its own words has its own class of exception, extending its kind (`BusinessRuleViolation`, `NotAllowed`, `NotPossibleInItsState`, `NotFound`): `InvalidPhoneNumber`, `EmailAlreadyUsed`, `NoLogoToRemove`... and carries what it is about by name (`['phoneNumber' => '03554411']`). No list of codes: a new refusal is a new class, nothing else changes, as Robert C. Martin advises against an enumeration of error codes every module depends on.
- `UseCaseRefused` names the refusal by the class of the domain exception (`invalidPhoneNumber`) and keeps its context; the API gives both in the problem details, `reason` on the problem and on its violation, with `context`.
- The SPA words a refusal from its reason in `refusals` of `fr.ts`, with its context; a reason it does not know is shown as the API worded it. A test of the API fails while a refusal of the core has no words in the SPA.

## Consequences

- The messages of the domain stay in English, for the logs and the developers; the words of the members are in the SPA with its other texts.
- Renaming a class of refusal renames its reason: its words in `fr.ts` follow, the test tells.
- The violations of the shape of an input (Symfony validation) have no reason; the forms check the same constraints in French before sending.

## Alternatives considered

- The API translating with the Translator of Symfony and `Accept-Language`: the texts in two places, the API in charge of a language its clients choose.
- A code per refusal kept in an enumeration: the dependency magnet Robert C. Martin warns of.

## Revisit when

- A client in another language: its words for the same reasons.
