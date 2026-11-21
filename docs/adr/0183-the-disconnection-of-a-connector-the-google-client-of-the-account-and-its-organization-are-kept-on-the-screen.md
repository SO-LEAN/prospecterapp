# 0183. The disconnection of a connector, the client of Google of the account and the organization of the account are kept on the screen

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `NEVER` in `apps/api/tests/Architecture/EveryEndpointHasItsToolTest.php`, `docs/architecture/mcp.md`

## Context

Every endpoint of a member has its tool of the MCP server, but those of the list « jamais », kept on the screen (0163): the forgetting of a contact, the credentials of the sign-in, the connection of a connector and its consent given to Google in a browser, and the form of an upload (0172). Covering the last writes, the tags, the campaigns, the booking page and the settings of the member, left endpoints that list did not name: the disconnection of a connector, the client of Google of the account and its notifications of Gmail, and the identity, the address and the logo of the organization of the account. The maintainer decided where each stands when the ticket covering them was framed.

## Decision

The list « jamais » adds, kept on the screen:

- the disconnection of a connector, Google disconnected or the import of the e-mails of Gmail stopped (`api_me_connections_google_disconnect`, `api_me_connections_google_stop_gmail_import`): an AI could not take it back, connecting again needing the consent of the member given to Google in a browser;
- the client of Google of the account and its notifications of Gmail, configured or removed by its administrator (`api_me_configure_google_client`, `api_me_remove_google_client`, `api_me_configure_gmail_notifications`, `api_me_remove_gmail_notifications`): credentials of the account;
- the identity, the address and the logo of the organization of the account (`api_me_revise_organization_identity`, `api_me_relocate_organization`, `api_me_change_organization_logo`).

Checking the connections of the member (`verifier-mes-connexions`) and catching up the e-mails of Gmail of a contact or an organization (`rattraper-gmail-contact`, `rattraper-gmail-organisation`) have their tools: they change no connection.

## Consequences

- An AI changes nothing of how the member and their account reach Google, nor what the account says of itself.
- The list « jamais » grows with each kind of endpoint kept on the screen; a new endpoint of these kinds is put on it by its author, or the test of coverage fails.

## Alternatives considered

None weighed beyond the decision of the maintainer for each route.

## Revisit when

An AI needs to manage the connectors or the account it serves, or the consent to Google can be given without a browser.
