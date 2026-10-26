# 0035. Requests and responses are plain data; the use cases translate the refusals of the domain

- Date: 2026-10-15
- Status: Accepted; the authentication query superseded by [0040](0040-security-reads-the-caller-with-consult-my-account.md), the response a query returns by [0084](0084-a-query-presents-its-answer-to-a-presenter-built-for-the-call.md)
- Implemented by: `packages/core/src/*/Application`, `packages/core/src/SharedKernel/Application/{RefusalTranslatingHandler,Exception/UseCaseRefused,Exception/ErrorType}.php`, `apps/api/src/Shared/DependencyInjection/RefusalTranslatingCommandHandlersPass.php`, `packages/core/tests/Architecture/ApplicationBoundaryTest.php`, `apps/api/tests/Architecture/DomainBehindTheApplicationTest.php`

## Context

Requests and responses carried value objects and identities of the domain (`OrganizationId`, `Address`, `LegalIdentity`, the intent enums), and the API caught the exceptions of the domain to answer 404, 409, 403 or 422. The domain crossed the application and ended in the code of the client. The user wants the boundary Robert C. Martin describes: request and response models are simple data structures, the client knows the use cases, not the entities.

## Decision

- A request (command or query) carries strings, numbers, booleans, `\DateTimeImmutable` and `\SplFileInfo` only, flat: `Relocate` has `street`, `postalCode`, `city`, `country`. The handler builds the value objects.
- A response carries the same kinds of data, and data structures of the application (`FileResponse`), built by named constructors from the aggregate (`OrganizationResponse::of()`); the possible intentions are their names.
- The use cases translate every refusal of the domain, in one place: a handler implements `CommandHandler` or `QueryHandler` and knows no error handling; the client calls it through the decorator `RefusalTranslatingHandler`, which turns a `BusinessException` into `UseCaseRefused::because()`. The API registers every command handler on Messenger through it (a compiler pass, the command named explicitly since the decorator takes any object) and wraps the query handlers it calls. `UseCaseRefused` keeps its message for the client, the domain exception as previous, the faulty field, and an `ErrorType` (`NotFound`, `NotAllowed`, `NotPossibleInItsState`, `BusinessRuleViolated`) from which the client deduces its answer (the API: a status code). Any other exception, a failure of the infrastructure, goes through untouched.
- The exceptions of the domain live in a `Domain\Exception` package of their context, with named constructors (`UnknownOrganization::identifiedBy()`, `BusinessRuleViolation::because()`).
- Two tests guard the rule: in the core, no type of a public property of a request or response, nor of `__invoke()`, comes from a `Domain` namespace; in the API, only the adapters of the ports (persistence, anticorruption layers, gateways, fixtures) use the domain. Authentication goes through a query (`IdentifyMember`).

## Consequences

- The client no longer breaks when the domain changes its value objects; it depends on the application only.
- More code: flat requests are long (`StartProspecting` has fifteen fields), responses repeat the fields of the value objects.
- A handler called without the decorator lets the domain exceptions out: the scenarios and the API always go through it, and a test requires every handler to be a `CommandHandler` or a `QueryHandler`. Through the decorator a query answers `mixed`: the precise type of the response is in the handler only.
- Intention names are strings: a renamed case of an intent enum silently loses its link in the API (its tests catch it).

## Alternatives considered

- Flattening the requests only, the responses keeping their value objects: the user wanted both.
- Moving the refusals out of the domain into a package known by both sides, or letting them cross the application: the user preferred translating them, with a type to handle them all the same way.
- A try/catch in every handler (the first implementation): the user asked for a decorator of the command and query handlers instead.

## Revisit when

- A client needs a refusal the four error types cannot tell apart.
