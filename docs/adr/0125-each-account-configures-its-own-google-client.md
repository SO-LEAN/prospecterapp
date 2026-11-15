# 0125. Each account configures its own Google client, its secret a parameter of SSM written by the api

- Date: 2026-11-15
- Type: architecture
- Status: Accepted
- Supersedes: for the OAuth client of an instance and its secrets, [0120](0120-the-connectors-context-connects-a-member-to-google-through-the-spa.md); [0124](0124-the-api-starts-without-the-google-client-of-its-instance.md) entirely
- Implemented by: `packages/core/src/Connectors`, `packages/core/features/configurer-le-client-google.feature`, `apps/api/src/Connectors`, `apps/api/infra/lambda.tf`, `apps/front/src/features/connectors`

## Context

ADR 0120 gave each instance one Google client, an *internal* app of the Workspace of the publisher, set by hand in SSM and read by the function at its start (ADR 0124): only the Google accounts of that Workspace may connect, and a client set or changed needs an apply. An organization using Prospecter has its own Workspace, and wants its members to connect through a client of its own Google Cloud, without the publisher. The missing client of an instance once took the api of dev down (ADR 0124). Michel decided on 2026-11-15 that the administrator of each account configures its client in the Configuration, that an account without a client has no synchronization with Google (no client of the instance by default), and that its secret is a `SecureString` of SSM written by the api, apart from the refresh tokens, under the key `aws/ssm` managed by AWS.

## Decision

- **The client is data of the account, in Connectors**: the aggregate `ProviderClient` (the account, the provider, the credentials: the id and the secret of the client at the provider), configured, replaced and removed by an administrator of the account only (`AdministratorsOnly`, 403), the role read in Accounts through its published service `ConsultMembersService` (`MemberResponse::$administers`), as Connectors reads the account of a member already. The api requires `ROLE_ADMIN` on its endpoints too (`/api/me/organization/clients/google`: `GET`, `PUT`, `DELETE`), and shows the link to the client only to an administrator.
- **`ProviderAccess` receives the credentials of the client of the account** (`ClientCredentials`) for an exchange and a refresh, and no longer knows a client of its own; a revocation needs none (Google revokes a token without its client). A provider is offered to a member while a client serves their account; the address of Google is built with the id of that client, read with the providers offered (`ConsultMyConnections`).
- **A connection remembers the client that granted its token** (its id): a token is valid only with its client, so a connection of another client than that of its account is « to reconnect » at its check, as a token Google refuses. The connections kept before this record were granted by the client of the instance, which no account has: to reconnect.
- **The secret**: a `SecureString` `/prospecter/<instance>/api/accounts/<account>/google-client-secret`, encrypted by the key `aws/ssm` (free), put, read and deleted by the api (`SsmClientSecrets`, the role of the function allowed under `/accounts/*`), never answered by the api once written, only said set. The item of the client in the table of Connectors (`ACCOUNT#<account>` / `CLIENT#google`) keeps its id and the version of its secret SSM answered the put: SSM is out of the unit of work (ADR 0095), so a commit failing after the put leaves the item naming the former version, still read as it was. A removal deletes the parameter at once and the item with the unit: a client whose parameter is missing is no client.
- **No client of the instance**: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` and the reading of SSM by `bref-ssm:` at the start of the function are removed (ADR 0124); the redirect URI stays one per instance, the path of its SPA, shown to the administrator with the client to declare it at Google.
- **Locally and in the tests**: the SSM of MiniStack, of the stack or of the tests (`SSM_ENDPOINT`); the account SOLEAN IT of the demonstration is served by the client of the double of Google; recreating the tables deletes the secrets under the path of the instance too.

## Consequences

- A customer connects their members through their own Google Cloud, internal to their Workspace: Google verifies nothing, the publisher does nothing, and no apply follows.
- Whoever reads the table and SSM together can still push into the « Prospecter » calendars of the members of an account until they disconnect, as with ADR 0120; the secrets of the accounts are now written by the function, whose role may put and delete them.
- Changing the client of an account makes its members reconnect, at the next opening of the page of the connectors; the tokens of the former client are not revoked, its secret being gone.
- A read of a connection or of the page of the connectors costs a read of SSM for the secret when the client is needed (a check, an exchange), not for the list of the connections.
- The connections of dev made before are to reconnect once its administrator configured the client.
- A removal whose commit failed leaves the item without its parameter: read as no client, as asked.

## Alternatives considered

- Encrypting the secret and the tokens by a key of KMS of the instance, reconsidered by the ticket now that both would be data of the application: refused by Michel, the `SecureString` of SSM being apart from the table and free.
- A client of the instance by default for the accounts without one: refused by Michel, an account without a client has no synchronization.

## Revisit when

A second provider arrives (Outlook), an account wants several clients, or the secrets need a rotation without reconnecting.
