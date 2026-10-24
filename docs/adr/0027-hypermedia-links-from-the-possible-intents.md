# 0027. Resources carry hypermedia links built from the possible intents of the core

- Date: 2026-10-15
- Status: Accepted; who decides the links superseded by [0081](0081-the-api-checks-the-access-the-core-keeps-the-state.md): the state by the aggregate, the member by a voter of the API
- Implemented by: `Organization::possibleIntentsOf()`, `User::possibleIntents()`, `apps/api/src/Api/Links.php`, `apps/api/src/Presenter`, `apps/front/src/shared/ui/editable-section.tsx`

## Context

The front decided itself which buttons to show (the "Remove" button when a logo exists), repeating rules of the core. The user wanted the resource to tell which actions are possible, and a call to be refused whenever its link is missing.

## Decision

- The aggregate states what a user may do with it now: `Organization::possibleIntentsOf($user)` (nothing if it is not theirs, `RemoveLogo` only with a logo, `DetachFromHolding` only with a holding), `User::possibleIntents()`. Responses of the core carry them (`OrganizationResponse::$possibleIntents`, `AccountResponse::$possibleIntents` and `$organizationIntents`).
- Every intention method checks the same rule first and refuses what is not offered (`NotPossibleInItsState`, 409). `OrganizationIntentsTest` checks, intent by intent, state by state, that an intention is refused exactly when it is not offered.
- The API turns them into `_links`, HAL with the HTTP method: `{"href": "/api/organizations/…/logo", "method": "DELETE"}`; `self` always, the key of an intention's link absent when it is not possible (not a null link). The password link is always there: the password is not the business of the core (0011).
- The front shows an action only when its link is there (`editable`, `onUpload`, `onRemove`), and keeps calling the typed client.

## Consequences

- The front no longer repeats business rules; a new rule changes the links and the front follows.
- A button the user sees is a call the API accepts, and the reverse.
- Links are typed in the OpenAPI contract, one property per intention: a new intention adds a property.

## Alternatives considered

- HAL `_links` without the method, or Siren actions: offered to the user, who chose HAL with the method.

## Revisit when

The front needs to follow links it does not know (a generic client), or the links depend on roles (the API would then filter them by role too).
