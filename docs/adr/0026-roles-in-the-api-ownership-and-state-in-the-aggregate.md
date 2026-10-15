# 0026. Roles are checked by the API, ownership and state are decided by the aggregate

- Date: 2026-10-15
- Status: Accepted
- Supersedes: [0019](0019-organizations-reachable-only-by-their-prospector.md)
- Implemented by: `packages/core/src/Domain/Organization/Organization.php`, `apps/api/src/Controller/Api` (`#[IsGranted]`), `apps/api/src/Security/ApiAccessDeniedHandler.php`

## Context

Use cases received the signed-in user as a `UseCaseConsumer` and decided themselves who could run them: roles in `canBeExecutedBy()`, checked by `UseCasesFacade`, and ownership through `ProspectedOrganizations` in the base classes `OrganizationUseCase` and `AccountUseCase` (0019). The user wanted the aggregate, not the use case, to decide whether an action is allowed. The user also usually checked access to resources in the controller, with voters or a `Retrieve` use case telling beforehand whether a resource belongs to the user.

## Decision

- A request to the core carries the id of the acting user (`prospectorId`, `userId`); `UseCaseConsumer`, the base use cases and `ProspectedOrganizations` are gone.
- Roles are infrastructure, like authentication (0011): each controller declares `#[IsGranted('ROLE_PROSPECTOR')]` or `#[IsGranted('ROLE_USER')]`, `ROLE_ADMIN` inherits both; a missing role is a 403 problem+json.
- Ownership is a business rule decided by the aggregate: each intention method takes the acting user (`relocateTo($address, $geoPoint, by: $user)`). An organization is managed by its own users and by the users of the organization prospecting it; for anybody else it does not exist (`UnknownOrganization`, 404), and a holding must be prospected by the user's organization.
- The state of the aggregate decides too: an intention it does not offer now is refused (`NotPossibleInItsState`, 409), with the same rule that removes its link (0027).

## Consequences

- No use case can forget the check: an intention cannot be called without the acting user, and the scenarios cover the rule.
- A use case loads the acting user on every call (one more read).
- A user may now change their own organization through the organization endpoints too, not only through `/api/me/organization`.
- The core no longer knows roles; a business rule that depends on a role would have to come back into an aggregate.

## Alternatives considered

- Checking access in the controller, with a Symfony voter or a `Retrieve` use case called beforehand (the user's habit): kept for roles only. For ownership it would leave the rule outside the scenarios and outside any other client (command bus, data recovery), and a `Retrieve` use case would exist only for the controller.
- Keeping the decision in the use cases (`OrganizationUseCase`, `ProspectedOrganizations`): the user wanted the aggregate to decide.

## Revisit when

A rule depends on a role (an administrator acting for another organization), or organizations are shared between prospecting organizations.
