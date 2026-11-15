# Connectors

The connections of the members to the services outside Prospecter, Google Calendar first (ADR 0120). Code: `packages/core/src/Connectors`.

## Language

- **Connection** (`Connection`): the account a member connected at a provider (`externalAccount`), the lasting access it gave (`RefreshToken`), and its **state** (`ConnectionState`): **connected**, or **to reconnect** once the provider refused the access. One per member and provider, theirs only.
- **Provider** (`Provider`): the service outside, `google` the one so far.
- **Authorization** (`PendingAuthorization`): what a member asked a provider, waiting for its answer: the `state` the provider gives back, the verifier of its code (PKCE), bound to the member for ten minutes.
- **Grant** (`Grant`): what the provider granted for a code: the external account, its token, and whether it granted everything asked (the calendar).

## Rules

- A provider is offered only while a client of Prospecter at it serves the account of the member (that of the instance so far): without it, it is not offered to the member (`ConsultMyConnections` presents the providers offered), and an authorization asked of it is refused (`ProviderUnavailable`).
- A member asks for an authorization (`AskForAuthorization`), the client drawing its `state` and its verifier; the provider brings them back with a code, which connects their account (`Connect`) while the authorization waits: an answer no authorization of the member awaits, unknown, expired or asked by another member, is refused (`UnknownAuthorization`), and so is a code the provider refuses (`AccessNotGranted`), or a grant without the calendar (`IncompleteGrant`). An access refused at the provider connects nothing.
- Connecting again replaces the account and the token of the connection, connected again; the former access to another account is revoked at the provider, that of the same account is not, Google revoking with a token the whole grant of its account (`Connection::renew()`).
- A connection is checked with its provider (`CheckMyConnections`, as the page of the connectors opens): a token the provider refuses makes it **to reconnect** (`Connection::check()`), once; without a client of the provider it is not checked, kept as last seen.
- Disconnecting revokes the access at the provider, then forgets the connection (`Disconnect`), refused without a connection (`NoConnection`); a revocation failing does not keep it.
- A member reads their own connections only (`ConsultMyConnections`); the address of their external account, personal data kept outside PersonalData, is theirs alone and forgotten when they disconnect (ADR 0120).

## What it publishes

Nothing yet; its domain events (`Connected`, `ConnectionLost`, `Disconnected`) stay in the context. It reads the account of a member from Accounts (`Members`, `ConsultMembersService`).

## Features

`connecter-mon-compte-google`.

## In the code

- `src/Connectors/Domain`: `Connection`, `PendingAuthorization`, `Grant`, `RefreshToken`, `Provider`, `ConnectionState`; the repositories `ConnectionRepository`, `AuthorizationRepository`; the ports `ProviderAccess` (tell whether a client serves the provider, exchange a code, tell whether a token still grants, revoke it) and `Members`, implemented by the API.
- `src/Connectors/Application`: `AskForAuthorization`, `Connect`, `CheckMyConnections`, `Disconnect`, `ConsultMyConnections` (`MyConnection`, the port `MyConnectionsRetriever`).
- In the API: the table and its items in `docs/architecture/persistence.md`, the endpoints in `docs/architecture/api.md`, Google and its double in `docs/architecture/local-stack.md` and `infra.md`.
