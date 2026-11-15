# Connectors

The connections of the members to the services outside Prospecter, Google Calendar first (ADR 0120), through the client each account made at the provider (ADR 0125). Code: `packages/core/src/Connectors`.

## Language

- **Client** (`ProviderClient`): what an account made at a provider for its members to connect through, in its own space there (its Google Cloud): its **credentials** (`ClientCredentials`), the id of the client and its secret, both required (`IncompleteClient`). One per account and provider, configured by an administrator of the account.
- **Connection** (`Connection`): the account a member connected at a provider (`externalAccount`), the lasting access it gave (`RefreshToken`), the client of their account it was granted to (its id), and its **state** (`ConnectionState`): **connected**, or **to reconnect** once the provider refused the access. One per member and provider, theirs only.
- **Provider** (`Provider`): the service outside, `google` the one so far.
- **Authorization** (`PendingAuthorization`): what a member asked a provider, waiting for its answer: the `state` the provider gives back, the verifier of its code (PKCE), bound to the member for ten minutes.
- **Grant** (`Grant`): what the provider granted for a code: the external account, its token, and whether it granted everything asked (the calendar).

## Rules

- An administrator of the account configures its client at a provider, or another one (`ConfigureClient`), and removes it (`RemoveClient`, refused without one: `NoClient`); they alone see it, its secret never answered, only its id (`ConsultMyAccountClients`); any other member is refused (`NotTheAdministrator`), whether they are one being read in Accounts (`Members`). The client of an account serves it alone: there is no client of the instance.
- A provider is offered only while a client of the account of the member serves it: without it, it is not offered to the member (`ConsultMyConnections` presents the providers offered, with the id of their client), and an authorization asked of it is refused (`ProviderUnavailable`); the code is exchanged with the client of the account.
- A member asks for an authorization (`AskForAuthorization`), the client drawing its `state` and its verifier; the provider brings them back with a code, which connects their account (`Connect`) while the authorization waits: an answer no authorization of the member awaits, unknown, expired or asked by another member, is refused (`UnknownAuthorization`), and so is a code the provider refuses (`AccessNotGranted`), or a grant without the calendar (`IncompleteGrant`). An access refused at the provider connects nothing.
- Connecting again replaces the account and the token of the connection, connected again; the former access to another account is revoked at the provider, that of the same account is not, Google revoking with a token the whole grant of its account (`Connection::renew()`).
- A connection is checked with its provider through the client of the account (`CheckMyConnections`, as the page of the connectors opens): a token the provider refuses, or a token granted to another client than that of the account (the administrator changed it), makes it **to reconnect** (`Connection::check()`), once; a new secret of the same client keeps it; without a client of the account it is not checked, kept as last seen.
- Disconnecting revokes the access at the provider, then forgets the connection (`Disconnect`), refused without a connection (`NoConnection`); a revocation failing does not keep it.
- A member reads their own connections only (`ConsultMyConnections`); the address of their external account, personal data kept outside PersonalData, is theirs alone and forgotten when they disconnect (ADR 0120).

## What it publishes

Nothing yet; its domain events (`Connected`, `ConnectionLost`, `Disconnected`, `ClientConfigured`, `ClientRemoved`) stay in the context. It reads the account of a member, and whether they administer it, from Accounts (`Members`, `ConsultMembersService`).

## Features

`connecter-mon-compte-google`, `configurer-le-client-google`.

## In the code

- `src/Connectors/Domain`: `Connection`, `ProviderClient`, `ClientCredentials`, `Member`, `PendingAuthorization`, `Grant`, `RefreshToken`, `Provider`, `ConnectionState`; the repositories `ConnectionRepository`, `AuthorizationRepository`, `ProviderClientRepository`; the ports `ProviderAccess` (exchange a code and tell whether a token still grants, through the credentials of a client; revoke a token) and `Members`, implemented by the API.
- `src/Connectors/Application`: `AskForAuthorization`, `Connect`, `CheckMyConnections`, `Disconnect`, `ConsultMyConnections` (`MyConnection`, the port `MyConnectionsRetriever`), `ConfigureClient`, `RemoveClient`, `ConsultMyAccountClients` (`AccountClient`, the port `AccountClientsRetriever`).
- In the API: the table and its items in `docs/architecture/persistence.md`, the endpoints in `docs/architecture/api.md`, Google and its double in `docs/architecture/local-stack.md` and `infra.md`.
