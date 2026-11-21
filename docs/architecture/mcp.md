# The MCP server

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

An AI (Claude web, Claude Code, any Model Context Protocol client) reaches Prospecter as the member who signed it in, with exactly that member's rights. The API serves a sessionless MCP server on `https://api.<domain>/mcp` (ADR 0156), and the AI signs the member in on Cognito through a stateless facade of the API (ADR 0157).

Status:
- The server runs. Through it, an AI reads everything its member reads on the screens (one tool per read endpoint, below).
- Before creating anything, it identifies whom Prospecter already knows (ADR 0173), then records a contact and their organization in one call (ADR 0177).
- The facade signs the AIs in (every step below). An SPA access token given by hand opens `/mcp` too.
- Writes: an AI logs an exchange, once (ADR 0166). It manages the organizations and the contacts as the screens do, except forgetting them. It plans, handles, corrects and erases the exchanges, and attaches files to them by their web address (ADR 0172). It manages the to-dos, the tags and the campaigns, which it also leads, sending each message itself (ADR 0186). It manages its member's booking page, reminders and profile, checks their connections and catches up their Gmail e-mails.
- What stays on the screen only is listed below.

```
AI (Claude)                         API (api.<domain>)                               Cognito
 1. POST /mcp, no token ──────────▶ 401 WWW-Authenticate: Bearer resource_metadata=…
 2. GET /.well-known/oauth-protected-resource/mcp ─▶ {resource: <api>/mcp, authorization_servers: [<api>]}
 3. GET /.well-known/oauth-authorization-server ───▶ endpoints, S256, CIMD, auth method none
 4. browser: GET /oauth/authorize ─▶ client checked (its metadata document), PKCE of the AI
                                     302 ───────────────────────────────────────────▶ sign-in page (hosted UI)
                                                                                      the member signs in
 5.                                  GET /oauth/callback?code=<Cognito>&state=<seal> ◀── 302
                                     302 to the AI: code=<seal of the request and of the code of Cognito>
 6. POST /oauth/token ─────────────▶ seal opened, verifier of the AI checked ─────────▶ code exchanged (client secret, PKCE of the API)
                                     ◀── the tokens of Cognito, as they are ◀──────────
 7. POST /mcp, Bearer <access token of Cognito> ─▶ firewall mcp: the member, their roles; the tool runs
 8. expired: POST /oauth/token grant_type=refresh_token ─▶ passed to Cognito
```

## The server

- `symfony/mcp-bundle` (Symfony AI) on `mcp/sdk`. Both are experimental: pin them at an exact version, upgrade them by hand, and read their changelogs. `session.store: none` arrived after the bundle's last tag (`v1.0.0-BETA1`), on its `main` with `mcp/sdk` 0.10.0. So the bundle is pinned at that commit (`dev-main#790442a8…` in `apps/api/composer.json`) and `mcp/sdk` at `0.10.0`, until a tag includes it.
- One server, `prospecter` (`config/packages/mcp.yaml`), over streamable HTTP (« streamable ») on `/mcp` of the API's host, with `allowed_hosts` set to that host. It runs in the API's Lambda behind its HTTP API (`$default` on AWS, nginx locally), so it has no infrastructure of its own.
- Sessionless: both protocol eras share the same endpoint, and the SDK routes each request by its `params._meta`. Revision `2026-07-28` has no handshake and no session. Clients of the `initialize` handshake (`2025-11-25` and earlier) get answers without `Mcp-Session-Id`: each request runs in its own session, thrown away after it, and `GET` and `DELETE` answer 405. There are no server requests (elicitation, sampling), subscriptions or logging; no tool needs them.
- Its rate, like that of the facade's public routes, is limited like the API's other public routes: per address by the installation's edge, and by the HTTP API's throttling on routes of their own, added with them. The stage throttles nothing today, and throttling `$default` would cap the whole API.
- No CORS: servers (Claude web) and native clients (Claude Code) call `/mcp`, never a page.

## Who calls

- The `mcp` firewall (`^/mcp$`, stateless) checks Cognito access tokens the way the `api` firewall does (`Security\AccessTokenHandler`, `auth.md`). It accepts the tokens of the `prospecter-mcp` client besides the SPA's (the `app.mcp_token_handler` service, `UserPool::CLIENT_NAMES_OF_THE_MCP_SERVER`), and loads the same `App\Security\User`. So an AI does what its member may do, and reads the personal data in clear or masked as the member does. It has no scope of its own.
- Without a token, the answer is 401 `WWW-Authenticate: Bearer resource_metadata="<api>/.well-known/oauth-protected-resource/mcp"` (the entry point). A refused token (expired, from another pool or client, an ID token) adds `error="invalid_token"` (RFC 6750) (the authenticator's failure handler). Both come from `Security\McpAuthenticationEntryPoint`, which names the metadata through `SignIn\OAuth\ProtectedResource`. Claude starts its sign-in only on a 401, never when a tool answers an error.

## Every endpoint has its tool

- Every endpoint a member calls, that is every route whose controller declares `#[IsGranted]`, has its tool (ADR 0163). An AI reads and does what its member reads and does on the screens, and the server never lags behind the application. The public routes (the facade below, the public booking page, the Gmail push, the user pool, the documentation) are not member endpoints.
- Except for two lists, in `tests/Architecture/EveryEndpointHasItsToolTest`. The test fails and names each member endpoint that has no tool and is on neither list.
  - `NEVER`, what stays on the screen:
    - forgetting a contact (GDPR, `api_contacts_forget`);
    - the sign-in credentials (`api_me_sign_in`, `api_me_change_login_email`, `api_me_change_password`);
    - connecting a connector, whose consent to Google is given in a browser (`api_me_connections_google_authorize`, `api_me_connections_google_connect`, `api_me_connections_google_import_gmail`), and disconnecting it, which an AI could not undo without that consent (`api_me_connections_google_disconnect`, `api_me_connections_google_stop_gmail_import`);
    - the account's Google client and its Gmail notifications, which are credentials (`api_me_configure_google_client`, `api_me_remove_google_client`, `api_me_configure_gmail_notifications`, `api_me_remove_gmail_notifications`);
    - the identity, address and logo of the account's organization (`api_me_revise_organization_identity`, `api_me_relocate_organization`, `api_me_change_organization_logo`) (those three kinds, ADR 0183);
    - the upload form (`api_uploads_prepare`: the tool attaching a file prepares its own, ADR 0172);
    - the members and their roles, once they have endpoints.
  - `NEVER_TAKEN_BACK`, the writes nothing undoes (below).

  The same test checks that:
  - a tool adapting a `GET` carries `readOnlyHint`;
  - a write tool says whether its AI asks its member first (a `DELETE`, or a write in `NEVER_TAKEN_BACK`: cancelling an exchange, stopping a campaign, dismissing a recipient) and whether it reaches outside Prospecter (below);
  - no tool adapts an endpoint in `NEVER`;
  - every route named by `#[Adapts]` or a list exists.

## Writing a tool

- One tool per endpoint, `src/<Context>/Mcp/<Intent>Tool.php`, next to its controller (`Prospecting\Mcp\SearchOrganizationsTool` next to `Prospecting\Controller\SearchOrganizationsController`). It is a `final readonly` service carrying `#[Adapts('<the name of the route>')]` (`Shared\Mcp\Adapts`) and `#[McpTool]` on `__invoke`:
  - the name in French kebab-case, and a description for the AI in English;
  - for a read, `annotations: new ToolAnnotations(readOnlyHint: true)`;
  - for a write, `readOnlyHint: false` and `destructiveHint`. `destructiveHint` is `true` for a tool adapting a `DELETE`, which removes or erases, or for a write nothing undoes (`annuler-echange`, listed in `NEVER_TAKEN_BACK`): its AI asks its member before calling it. It is `false` for any other write, since the protocol treats a write as destructive unless told otherwise;
  - `openWorldHint`, `true` only for a tool reaching outside Prospecter (`changer-logo-organisation`, `changer-ma-photo`, `joindre-piece-jointe`, `relancer-synchronisation-google`, `relancer-synchronisations-google-echouees`, `verifier-mes-connexions`, `rattraper-gmail-contact`, `rattraper-gmail-organisation`; planning an exchange is not, even though a reaction pushes it to Google afterwards), `false` for the others, since the protocol treats a tool as reaching outside unless told otherwise. The read tools do not set it yet;
  - its arguments are those of the endpoint's route and query (`id`, `q`, `after`, `from`, `to`, `timeZone`…, each described by its `@param`);
  - its namespace is listed in the server's `registry` (`config/packages/mcp.yaml`).
- A tool does what its controller does, in the same order:
  1. It reads the route's resource through its resolver (`OrganizationResolver::read()`, `ExchangeResolver`, `CampaignResolver`, `ToDoResolver`); the core refuses an unknown one.
  2. It checks the controller's `#[IsGranted]` attributes through `Security\ToolAccess::denyUnlessGranted()` (the role, the resource's voter, `User::PERSONAL_DATA`).
  3. It builds the endpoint's input from its arguments and validates it (`Shared\Mcp\ToolInputs`: `valid()`, or `mapped()`, which denormalizes like the query string, dates included).
  4. It calls the same core reader, query or command as the controller. What a controller composed inline moves to a reader both share (`Contacts\ContactSearchReader`, `Dashboard\DashboardReader`, `Exchanges\AttachmentDownloads`…), never composed twice. When an endpoint composes its command from a large input, that input builds it for both (`StartProspectingInput::commandOf()`, `CollectContactInput::commandOf()`).

  The tools writing an organization share what each does like its controller, in `Prospecting\Mcp\OrganizationWrites::write()`: read the organization, check the access (the role, the voter, the other given attributes), validate the input, dispatch the command the tool built, and answer the organization as `lire-organisation` reads it. Likewise, the tools writing an exchange share `Exchanges\Mcp\ExchangeWrites` (`write()`; `dispatched()` for those that answer otherwise; `answer()` with the time it was moved from, for `deplacer-echange`). The to-do tools share `ToDos\Mcp\ToDoWrites` (which always checks the right to manage personal data). The campaign tools share `Campaigning\Mcp\CampaignWrites` (the campaign read by `CampaignResolver`, its voter and the right to manage personal data checked; `dispatched()` for those that answer nothing). The member's booking page has `Booking\Mcp\BookingPageWrites`, and their profile `Me\Mcp\MyProfileWrites` (any member, their own; `writable()` checks them before a picture is downloaded). `CampaignInput` builds the command preparing or revising a campaign for both its endpoint and its tool (`preparationOf()`, `revisionOf()`).
- A tool creating an organization, a contact or an exchange says that an AI creates it (ADR 0185): `byAnAI: true` on its command (`noter-un-echange`, `noter-un-echange-organisation`, `noter-l-envoi`, `planifier-echange`, `planifier-echange-organisation`), or the input's command built for an AI (`StartProspectingInput::commandOfAnAI()`, `CollectContactInput::commandOfAnAI()`: `prospecter-organisation`, `collecter-contact`, `enregistrer-un-contact`). Controllers never do. What it creates reads `createdByAnAI` in its resource and shows « MCP » on the screens. A new tool that creates something does the same.
- A file (an organization's logo, the member's picture, a file attached to an exchange) is given by its web address, and the API downloads it (ADR 0168, 0172), through `Shared\Mcp\WebAddressInput` (`url`, http or https) and `Shared\Mcp\FilesOnTheWeb::whileDownloaded()`:
  - only once the member has been checked;
  - never from a private network (`app.files_on_the_web_http`, a `NoPrivateNetworkHttpClient`);
  - within 5 seconds, following no redirect, and no heavier than the screen accepts;
  - an unreachable address is a violation of `url`, which says so when the file is behind a sign-in (401, 403) or when its address redirects (to a sign-in page, perhaps): Prospecter signs in nowhere on its member's behalf;
  - the file is removed once the command has used it, within the call (one kernel per request under FPM: no service reset to rely on).

  An image is then checked like the screen's image (`Shared\Mcp\ImagesOnTheWeb`, `Shared\Images`: 2 MB, JPEG, PNG or WebP); its violations answer 422. A file attached to an exchange (`joindre-piece-jointe`) is downloaded no heavier than the upload form the storage prepares allows (10 MB). The API sends it with that form to the private bucket, as a browser does (`Shared\UploadSender`, `Shared\UploadsSentByTheApi`), at `PRIVATE_UPLOADS_ENDPOINT` instead of the form's origin (MiniStack locally; empty on AWS, meaning the form's address). It sends the form whole, with its length: S3 refuses a chunked upload, which MiniStack accepts. The endpoint's command then attaches the file. When the bucket fails (5xx), limits the requests (429) or is out of reach, the private files are unavailable for now (`privateFilesUnavailable`, 503); any other refusal of the form is a server failure. Such a tool sets `openWorldHint`.
- A tool answers the endpoint's resource through `Shared\Mcp\ToolResults::of()`, serialized as the controller serializes it, links (`_links`) included, so the AI can follow them from one resource to the next with the tool of the endpoint each link names. Except a tool logging an exchange (ADR 0166): `Exchanges\NotedOnceAnswers` answers `{status, exchange, erasedBy, erasedAt}` (`Exchanges\Resource\NotingResource`):
  - `noted`, with the exchange as `lire-echange` reads it;
  - `alreadyNoted`, with the exchange logged first (without it when both were logged at once, `ExchangeNotedMeanwhile`);
  - `erasedBefore`, with the member who erased it, named like an exchange's authors, and when.

  It logs with `noteOnce`, so the core refuses what was logged before, by an AI, by the Gmail import or by a member.
- A refusal is the tool's error (`isError`), with the endpoint's problem+json as its text. « Already noted », « noted meanwhile » and « erased before » from a tool logging an exchange are its result instead (above, ADR 0166). `Shared\Mcp\RefusingTools` runs the tools (set on the server's builder by `Shared\DependencyInjection\RefusingToolsPass`) and translates:
  - a core refusal (`Shared\RefusalProblems`, like `UseCaseExceptionListener`);
  - an access denial (`Security\AccessProblems`, like `ApiAccessDeniedHandler`: `roleNotAllowed` 403, `unknown<Resource>` 404 for another account);
  - an unreachable service (`Shared\UnavailabilityProblems`, like `ServiceUnavailableListener`: `directoryUnreachable`, `googleUnreachable`, `secretsUnavailable`, `privateFilesUnavailable` 503), its cause logged at the error level, as Symfony logs it for the SPA;
  - the arguments' violations (422).

  Any other exception stays a server failure. A refusal reaches the AI as the SPA gets it: its `reason`, its `context` and its `detail` in English (ADR 0073). The AI words it for its member, in their language. The pass reads the tool locator the bundle gives the builder (`setContainer`) and sets its runner (`setReferenceHandler`). A bundle app (`#[AsMcpApp]`), whose pass sets a runner of its own, would need the two composed.
- The tools:
  - `qui-suis-je` (`Me\Mcp\WhoAmITool`, no endpoint): the member served, as `/api/me` reads them, their account, and whether they read the personal data in clear (`readsPersonalData`, the `ROLE_PERSONAL_DATA` role).
  - `identifier` (`Identification\Mcp\IdentifyTool`, no endpoint, ADR 0173), called before an AI creates anything. It says which persons and organizations some clues designate, each known, new, or to confirm among candidates (id and name), in the order given, writing nothing. Prospecting identifies the organizations first, then PersonalData the persons; a person names the organization they work for by its position in `organizations`. Prospectors only; without the right to manage personal data, a person is identified by their names only. A hundred of each at most: its schema refuses more (`#[Schema]` on its arguments). That error is the protocol's `-32602`, raised by the SDK before the tool runs, never by `RefusingTools`: with no endpoint, there is no endpoint input to validate its arguments with.
  - `enregistrer-un-contact` (`Identification\Mcp\RecordContactTool`, no endpoint, `idempotentHint`, ADR 0177): a person and the organization they work for, in one call, composed by `Identification\ContactRecorder`.
    - Both are identified first, by their keys, or picked by the member among those to confirm (`organizationId`, through `OrganizationResolver` and the voter; `personId`), or declared another one (`newOrganization`, `newPerson`).
    - One left to confirm writes nothing and answers its candidates.
    - Otherwise the organization is started (its SIRET noted after) or completed, then, last, the contact is completed and attached (`AddContact`), unless the organization already names them, or collected for it.
    - Each is answered `created`; `known`, with the names of the fields completed and of those given differently from what was noted (`CompareWithOrganization`, `CompareWithContact`, read before the commands); `new`; or `toConfirm`.
    - When a step fails after the organization was created, the organization stops being prospected (`StopProspecting`) before the failure is answered.
    - Prospectors with the right to manage personal data only, as for `collecter-contact`.
  - One per read endpoint: `rechercher-organisations`, `lire-organisation`, `chercher-etablissements`, `lire-echanges-contact`, `lire-echanges-a-venir-contact`, `proposer-lieu-de-rendez-vous`, `rechercher-contacts`, `rechercher-contacts-par-tags`, `lire-contact`, `lire-organisations-du-contact`, `lire-organisation-proposee`, `lire-contacts-organisation`, `lire-echanges-organisation`, `lire-echanges-a-venir-organisation`, `lire-echanges-a-mettre-en-conformite`, `reconnaitre-appelant`, `lire-agenda`, `lire-ma-journee`, `lire-echanges-a-traiter`, `lire-echanges-qui-chevauchent`, `lire-echange`, `telecharger-piece-jointe`, `lire-synchronisation-google`, `lire-mes-connexions`, `lire-client-google`, `lire-mes-a-faire`, `lire-a-faire`, `lire-a-faire-contact`, `lire-a-faire-organisation`, `lire-tags-contact`, `lire-tags-organisation`, `lire-tags-proposes`, `lister-campagnes`, `lire-campagne`, `lire-destinataires-campagne`, `lire-message-campagne`, `destinataire-suivant`, `lire-tableau-de-bord`, `lire-mon-compte`, `lire-mes-rappels`, `lire-ma-page-de-reservation`.
    - `destinataire-suivant` (`Campaigning\Mcp\NextRecipientTool`, no endpoint, ADR 0186): the next recipient of a campaign being led, how PersonalData says they are reached, the message rendered for them, its mode, and its `instruction` worded for the AI by `Campaigning\NextRecipientReader` (send as is or adapt), and how many are left. Its access is that of `lire-message-campagne`; once the campaign is no longer being led, it is refused with `campaignNotRunning`.
  - The writes:
    - `noter-un-echange`, `noter-un-echange-organisation` and `noter-l-envoi` (`idempotentHint`; the last one, for a campaign recipient, takes the text the AI actually sent, ADR 0186), with the `Message-ID` of the e-mail or the LinkedIn conversation they were read in;
    - on the organizations: `prospecter-organisation`, `reviser-identite-organisation`, `changer-logo-organisation` (below), `changer-coordonnees-organisation`, `demenager-organisation`, `ecrire-observations-organisation`, `rattacher-a-holding`, `ajouter-contact-organisation` (one of the organization's contacts to attach), and, destructive, `arreter-prospection` (answering nothing, like its endpoint), `detacher-de-holding`, `retirer-logo-organisation`, `retirer-contact-organisation`, `ecarter-doublon-possible`;
    - on the contacts: `collecter-contact`, `renommer-contact`, `changer-civilite-contact`, `changer-fonction-contact`, `changer-coordonnees-contact` (their LinkedIn profile among their links);
    - on the exchanges: `planifier-echange`, `planifier-echange-organisation` (pushed to the member's Google calendar, as when planned on the screen), `marquer-echange-fait` (with its report), `marquer-echange-manque`, `deplacer-echange`, `corriger-echange`, `corriger-texte-echange`, `corriger-lieu-echange`, `relancer-synchronisation-google`, `relancer-synchronisations-google-echouees` (every exchange of the member that Google refused), `joindre-piece-jointe` (above), and, destructive, `annuler-echange`, `effacer-echange` (answering nothing), `retirer-piece-jointe`;
    - on the to-dos: `noter-a-faire`, `noter-a-faire-organisation`, `reviser-a-faire`, `cocher-a-faire`, and, destructive, `decocher-a-faire`, `effacer-a-faire` (answering nothing);
    - on the tags: `taguer-contact`, `taguer-organisation`, and, destructive, `retirer-tag-contact`, `retirer-tag-organisation`;
    - on the campaigns: `preparer-campagne`, `reviser-campagne`, `figer-campagne`, `ajouter-destinataire-campagne`, `ajouter-destinataires-par-tags`, `demarrer-campagne`, and, destructive, `arreter-campagne`, `ecarter-destinataire-campagne`, `retirer-destinataire-campagne`, `abandonner-campagne` (the last three answering nothing);
    - on the member's booking page: `regler-creneaux-reservation`, `proposer-canaux-reservation`, `ouvrir-reservation`, `changer-lien-reservation`, `ajouter-periode-inactive`, and, destructive, `fermer-reservation`, `retirer-periode-inactive`;
    - on their settings: `changer-mes-rappels`, `changer-mon-nom`, `changer-mes-coordonnees`, `changer-ma-photo` (above), `verifier-mes-connexions`, `rattraper-gmail-contact` and `rattraper-gmail-organisation` (answering nothing; the e-mails arrive afterwards);
    - and, destructive, `ecarter-doublon-possible-contact`.

  Each tool's `#[Adapts]` names the route it adapts.
- Their tests:
  - `tests/Api/ReadingToolsApiTest` calls each tool and its endpoint as a prospector, as an assistant without the personal data right, as an administrator, as a member who is not a prospector and as a member of another account, and asserts the same answer, the resource or its problem. A test keeping its kernel for a double (`keepTheKernel()`) sets the tokens' frozen clock a minute later.
  - `tests/Api/NotingToolsApiTest` logs an exchange with each tool, again, and once erased, and compares each tool's refusals with its endpoint's for an assistant, a member who is not a prospector and a member of another account.
  - `tests/Api/OrganizationAndContactToolsApiTest` calls each tool writing an organization or a contact as each of those five members: a refusal is compared with its endpoint's; a write done by a member its endpoint allows is answered as the screen then reads the resource (its `self` link). It lists the destructive tools as an AI does.
  - `tests/Api/ExchangeAndToDoToolsApiTest` does the same for each tool writing an exchange or a to-do (only the holder of a planned exchange handles it), plus a file attached from the web (`Tests\App\Double\TheWeb`) and one behind a sign-in, refused, both through the checks of `Tests\App\Base\ActsAsItsEndpoint`. So do `tests/Api/TagToolsApiTest` for the tags, `tests/Api/CampaignToolsApiTest` for the campaigns (a draft prepared, frozen, started then stopped; the next recipient as is and adapted, the sending logged, again, and the `mener-une-campagne` prompt), `tests/Api/BookingPageToolsApiTest` for the booking page, `tests/Api/MySettingsToolsApiTest` for the reminders and the profile (a picture from the web) and `tests/Api/ConnectorToolsApiTest` for the connections and the Gmail catch-ups (the Google double).
  - `tests/Shared/UploadsSentByTheApiTest`: the form the API sends.
  - `tests/Api/IdentifyToolApiTest` identifies persons and organizations together, as an assistant, and refuses a member who is not a prospector and more than a hundred.
  - `tests/Api/RecordContactToolApiTest` records a contact and their organization: new, found, completed, twice, to confirm then picked or declared another one, as each of the five members. It makes each step fail in turn (`Tests\App\Double\FailingCommands`, a middleware of the tests' command bus armed by the test, the kernel kept), and checks that nothing the call created remains.
  - `tests/Api/SignInOfTheAIsApiTest` signs an AI in through the facade on MiniStack's Cognito, posting its sign-in page as a browser does, then calls `qui-suis-je`. It also covers every refusal of the facade, the refresh and the sign-outs. Claude's metadata documents are doubled by `Tests\App\Double\ClientsOfTheAIs`, which also resolves their hosts.
  - `tests/Api/McpApiTest` calls the tools in each era (a `tools/call` carrying the revision in its `_meta`; `initialize` then `tools/call`, with no `Mcp-Session-Id`), with a member of each right, and with no token or a refused one.
  - `tests/Api/ProtectedResourceApiTest`: the metadata.

## The prompts

- A protocol prompt is a walkthrough the AI offers its member (a Claude Code command, a Claude web entry). It chains the tools so that one sentence from the member, « synchronise mes contacts LinkedIn intéressants », is enough, with nothing created or logged twice. It reads and writes nothing itself: its text is a message from the member to their AI, in French, naming each tool as it is served. The tools check the access and refuse as they always do. Prospecter reads neither LinkedIn nor the mailbox for the AI: the AI reads them with its own tools (Claude's Chrome extension, Claude's Gmail connector).
- `src/<Context>/Mcp/<Intent>Prompt.php` (`Identification`, `Campaigning`) is a `final readonly` service with `#[McpPrompt]` on `__invoke`: the name in French kebab-case, a title and a description in French for the member. Its arguments are strings described by their `@param` in French. The SDK does not accept a typed object as a prompt argument and leaves it out of the list, so an argument with a few values is a string read into an enum, and any other value is the protocol error `-32602` (`RegistryException::invalidParams()`). Its namespace is listed in the `prompts` of the server's `registry` (`config/packages/mcp.yaml`). It answers one message with the `user` role: its steps.
- `synchroniser-linkedin` (« Synchroniser LinkedIn », `SynchronizeLinkedInPrompt`; `what`, `contacts` or `messages`; `since`, optional): read LinkedIn since then, each person with their profile, names, job title, company and company page; `identifier` in batches of a hundred, asking the member about each `toConfirm` and letting them choose the `new` ones that interest them; `enregistrer-un-contact`; for the messages, `noter-un-echange` (`noter-un-echange-organisation` for a company page) with `linkedInConversation` and `occurredAt` to the minute (the fingerprint of ADR 0166).
- `synchroniser-boite-e-mail` (« Synchroniser ma boîte e-mail », `SynchronizeMailboxPrompt`; `since`, optional): read the mailbox since then; `identifier` the correspondents, keeping only the `known` ones, except those the member asks to create, recorded by `enregistrer-un-contact`; `noter-un-echange` (or `noter-un-echange-organisation`) with each e-mail's `messageId`; an e-mail sent to two contacts is logged with each.
- Both prompts:
  - have the AI ask for `since` first when it is not given;
  - end with a report (created, completed, already logged, ignored). `alreadyNoted` and `erasedBefore` count as already logged, so a synchronization run again counts nothing created;
  - restate the rules: nothing `toConfirm` is created without the member's answer, nothing is logged without its reference when the AI reads it, and a refusal from Prospecter stops the AI, which asks its member.
- `mener-une-campagne` (« Mener une campagne », `Campaigning\Mcp\LeadCampaignPrompt`, ADR 0186; `campagne`, its name or its id): find it with `lister-campagnes`; take `destinataire-suivant`; send the message with the AI's own tools, as is or adapted as its instruction says; log it with `noter-l-envoi`, with the text actually sent and its reference, and go on when it is already logged; dismiss whoever it cannot reach with `ecarter-destinataire-campagne`, once the member has confirmed; never send a second message to a recipient given again, but stop and say who (the mailbox import may log the sending first, ADR 0186); repeat until the campaign is no longer being led, then report.
- Their tests: `tests/Api/SynchronizeLinkedInPromptApiTest` and `tests/Api/SynchronizeMailboxPromptApiTest` list each prompt with its arguments, get its steps for each argument, refuse a `what` value outside its list, and check that every tool its text names (`identifier`, and those in kebab-case between backquotes) is served.

## The AIs' sign-in

Cognito is the authority, but it cannot be, on its own, the authorization server Claude expects. It has no Client ID Metadata Document and no dynamic registration, no `code_challenge_methods_supported` in its discovery, and no `resource` (RFC 8707) in its authorization code. The API's facade fills those gaps and keeps nothing (`src/SignIn/OAuth`):

- `GET /.well-known/oauth-protected-resource/mcp` (and without `/mcp`, RFC 9728; `SignIn\Controller\ShowProtectedResourceController`, public, outside the API's contract): `resource` is `<api>/mcp`, exactly as the member types it in Claude, and `authorization_servers` is the API alone (Claude reads only the first one).
- `GET /.well-known/oauth-authorization-server` (RFC 8414, `SignIn\Controller\ShowAuthorizationServerController`, `SignIn\OAuth\AuthorizationServer`): its endpoints, `response_types_supported: [code]`, `grant_types_supported: [authorization_code, refresh_token]`, `code_challenge_methods_supported: [S256]`, `token_endpoint_auth_methods_supported: [none]`, `client_id_metadata_document_supported: true` (with `none`, Claude's condition for using it), and no `registration_endpoint`.
- `GET /oauth/authorize` (`AuthorizeController`):
  - The client is its Client ID Metadata Document (`OAuth\ClientMetadataDocuments`): an HTTPS URL fetched with its host on no private network (Symfony's `NoPrivateNetworkHttpClient` refuses it otherwise), within 5 s and 5 kB, following no redirect. Its `client_id` must equal its URL, and the `redirect_uri` must be among its `redirect_uris` (`OAuth\ClientMetadata`); a loopback address matches without its port (Claude Code declares `http://localhost/callback` and `http://127.0.0.1/callback`).
  - An invalid client or redirect answers 400 `application/problem+json` with the `unknownClient` `reason` (`OAuth\UnknownClientListener`), never a redirect to an unchecked address.
  - Once the redirect is checked, what the AI got wrong sends it back with its error: a `response_type` other than `code` (`unsupported_response_type`), a missing S256 `code_challenge` (`invalid_request`), a `resource` other than `<api>/mcp` when given (`invalid_target`).
  - Otherwise, a redirect to Cognito's sign-in page (`OAuth\CognitoSignIn`, `COGNITO_SIGN_IN_URL`) with the `prospecter-mcp` client, a PKCE of the API (`OAuth\Pkce`) and, as `state`, the request sealed for 15 minutes.
- `GET /oauth/callback` (`ComeBackFromCognitoController`): Cognito comes back with its code. The facade redirects to the AI with its `state` and, as `code`, the request and Cognito's code sealed together (5 minutes); with Cognito's `error` when the member did not sign in. A `state` the facade did not seal gets a 400 that leads nowhere.
- `POST /oauth/token` (`IssueTokensController`, `application/x-www-form-urlencoded`):
  - `authorization_code`: the seal is opened, the AI's verifier is checked against its challenge, with the same client and redirect. Then Cognito's code is exchanged at its token endpoint (`OAuth\CognitoTokens`, `COGNITO_OAUTH_URL`) with the `prospecter-mcp` secret (read from Cognito by `UserPool::mcpClientSecret()`, cached) and the API's verifier, and Cognito's tokens are answered as they are.
  - `refresh_token`: passed to Cognito.
  - Every refusal is `invalid_grant`, another grant `unsupported_grant_type` (RFC 6749, `OAuth\GrantRefusedListener`).

  The protocol forces two exceptions to one intent per endpoint (ADR 0025): `/oauth/token` carries two, by its `grant_type`, and `/oauth/authorize` and `/oauth/callback` only redirect.
- Nothing is kept. The request travels sealed (`OAuth\Seal`, `sodium_crypto_secretbox`, keyed by `OAUTH_SEAL_KEY`, an API secret, each seal for one purpose and for a limited time, `OAuth\SealPurpose`), first in the `state` given to Cognito, then in the code given to the AI. Since Cognito refuses its code a second time, the facade's code works once. No table, no item, nothing in the core.
- Claude web identifies itself with the document Anthropic publishes, its redirect being `https://claude.ai/api/mcp/auth_callback`; Claude Code with its own (`https://claude.ai/oauth/claude-code-client-metadata`), on a loopback port of the session. Neither needs any configuration.
- A global sign-out revokes the refresh tokens of all the member's clients, their AIs included. So the SPA signs out by revoking only its own refresh token (`RevokeToken`), and keeps a global sign-out for « Se déconnecter partout » in the account menu. An access token stays valid until it expires (1 h), as for the SPA (`auth.md`).

## Trying it

- Spike #202 proved the server on a throwaway branch, before it was built, in the Lambda on MiniStack: a `tools/call` of revision `2026-07-28` (its `_meta` carrying `io.modelcontextprotocol/protocolVersion` and `io.modelcontextprotocol/clientCapabilities`, with the `MCP-Protocol-Version`, `Mcp-Method` and `Mcp-Name` headers), then `initialize` and `tools/call` of `2025-11-25` answered without `Mcp-Session-Id`, `GET` 405, a refused token 401, and the MCP Inspector.
- The metadata name the API by `APP_API_HOST`, without the port a worktree serves its stack on: a client following them locally needs the main checkout's stack, on 443.
- Locally, the facade: on the main checkout's stack, open `GET https://api.prospecter.localhost/oauth/authorize?response_type=code&client_id=<a metadata document reachable in https>&redirect_uri=<one it declares>&code_challenge=<S256>&code_challenge_method=S256&state=…` in a browser, sign in on MiniStack's sign-in page (a demonstration member), then exchange the code with `curl -d grant_type=authorization_code -d code=… -d client_id=… -d redirect_uri=… -d code_verifier=… https://api.prospecter.localhost/oauth/token`.
- Locally, the server: `curl` with a demonstration member's token (`Authorization: Bearer`, the access token the SPA sends, read in the browser's network panel), in each era (a `tools/call` carrying revision `2026-07-28` in `params._meta`; `initialize` then `tools/call`), and the MCP Inspector (`npx @modelcontextprotocol/inspector --cli <url> --transport http --header "Authorization: Bearer …" --method tools/list`).
- Claude Code and Claude web need an instance reachable from Anthropic:
- Claude Code: `claude mcp add --transport http prospecter https://api.<domain>/mcp`, then `/mcp` to sign in.
- Claude web: Customize › Connectors › Add custom connector, with the URL `https://api.<domain>/mcp` and nothing else. It needs an instance reachable from Anthropic's egress (`160.79.104.0/21`), which the local stack is not.
