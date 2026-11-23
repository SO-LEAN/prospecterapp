# 0198. The API's controllers extend nothing

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Shared/JsonResponses.php`, every `apps/api/src/*/Controller/*Controller.php`, `apps/api/tests/Architecture/ControllersExtendNothingTest.php`, `docs/architecture/api.md`

## Context

Every controller of the API extended `AbstractController`. The parent class hands each controller a service locator, so a controller's constructor did not show everything it used (ADR 0044). The controllers used three of its methods: `json()`, `generateUrl()` and `redirect()`. One controller also used `denyAccessUnlessGranted()`. The storage's controllers were already `final readonly` classes that extend nothing.

`json()` writes the data with the Serializer. `new JsonResponse($data)` writes it with `json_encode`. The two can write a date, an enum or a private property differently.

## Decision

- A controller of the API is a `final readonly` class that extends nothing. It receives its dependencies through its constructor.
- One shared service, `App\Shared\JsonResponses`, writes every JSON answer. It uses the Serializer with the encoding options of a `JsonResponse`, as `json()` did.
- A controller builds a URL with `UrlGeneratorInterface` and a redirection with `new RedirectResponse()`.
- A right that depends on the resource is checked with `AuthorizationCheckerInterface` and refused with an `AccessDeniedException`. Any other right stays an `#[IsGranted]` attribute.
- `tests/Architecture/ControllersExtendNothingTest` fails when a controller of the API is not a `final readonly` class without a parent, or when it builds a `JsonResponse` itself.

## Consequences

Each controller's constructor lists what it uses, and the answers are written as before: the OpenAPI contract did not change. A controller that answers JSON takes one more dependency.

## Alternatives considered

The ticket allowed a PHPStan rule in `packages/phpstan-rules` instead of the test. The test was kept because the API's other rules on its controllers are architecture tests that read the router.

## Revisit when

A controller needs a helper that only `AbstractController` offers.
