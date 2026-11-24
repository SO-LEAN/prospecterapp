# The MCP server

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

An AI (Claude web, Claude Code, any Model Context Protocol client) reaches Prospecter as the member who signed it in, with exactly that member's rights. The API serves a sessionless MCP server on `https://api.<domain>/mcp` (ADR 0156). The AI signs the member in on Cognito through a stateless facade of the API (ADR 0157).

Status:
- The server runs. Through it, an AI reads everything its member reads on the screens (one tool per read endpoint, below).
- Before creating anything, it identifies whom Prospecter already knows (ADR 0173). Then it records a contact and their organization in one call (ADR 0177).
- The facade signs the AIs in (every step below). An SPA access token given by hand opens `/mcp` too.
- Writes:
  - An AI logs an exchange, once (ADR 0166).
  - It manages the organizations and the contacts as the screens do, except forgetting them.
  - It plans, handles, corrects and erases the exchanges. It attaches files to them by their web address (ADR 0172).
  - It manages the to-dos, the tags and the campaigns. It also leads the campaigns, and sends each message itself (ADR 0186).
  - It manages its member's booking page, reminders and profile. It checks their connections and catches up their Gmail e-mails.
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

- The server uses `symfony/mcp-bundle` (Symfony AI) on `mcp/sdk`. Both are experimental: pin them at an exact version, upgrade them by hand, and read their changelogs. `session.store: none` arrived after the bundle's last tag (`v1.0.0-BETA1`), on its `main` with `mcp/sdk` 0.10.0. So the bundle is pinned at that commit (`dev-main#790442a8…` in `apps/api/composer.json`), and `mcp/sdk` at `0.10.0`, until a tag includes it.
- There is one server, `prospecter` (`config/packages/mcp.yaml`). It speaks streamable HTTP (« streamable ») on `/mcp` of the API's host, with `allowed_hosts` set to that host. It runs in the API's Lambda behind its HTTP API (`$default` on AWS, nginx locally), so it has no infrastructure of its own.
- The server is sessionless.
  - Both protocol eras share the same endpoint, and the SDK routes each request by its `params._meta`.
  - Revision `2026-07-28` has no handshake and no session.
  - Clients of the `initialize` handshake (`2025-11-25` and earlier) get answers without `Mcp-Session-Id`. Each request runs in its own session, which is thrown away after it. `GET` and `DELETE` answer 405.
  - There are no server requests (elicitation, sampling), subscriptions or logging. No tool needs them.
- The server's rate is limited like the API's other public routes, and so is the rate of the facade's public routes. The installation's edge limits it per address. The HTTP API's throttling limits it on routes of their own, added with them. The stage throttles nothing today, and throttling `$default` would cap the whole API.
- No CORS: servers (Claude web) and native clients (Claude Code) call `/mcp`, never a page.

## Who calls

- The `mcp` firewall (`^/mcp$`, stateless) checks Cognito access tokens the way the `api` firewall does (`Security\AccessTokenHandler`, `auth.md`).
  - It accepts the tokens of the `prospecter-mcp` client besides the SPA's (the `app.mcp_token_handler` service, `UserPool::CLIENT_NAMES_OF_THE_MCP_SERVER`).
  - It loads the same `App\Security\User`. So an AI does what its member may do, and reads the personal data in clear or masked as the member does.
  - An AI has no scope of its own.
- Without a token, the answer is 401 `WWW-Authenticate: Bearer resource_metadata="<api>/.well-known/oauth-protected-resource/mcp"` (the entry point). A refused token (expired, from another pool or client, an ID token) adds `error="invalid_token"` (RFC 6750) (the authenticator's failure handler). Both come from `Security\McpAuthenticationEntryPoint`, which names the metadata through `SignIn\OAuth\ProtectedResource`. Claude starts its sign-in only on a 401, never when a tool answers an error.

## Every endpoint has its tool

- Every endpoint a member calls has its tool (ADR 0163). A member's endpoint is a route whose controller declares `#[IsGranted]`. An AI reads and does what its member reads and does on the screens, and the server never lags behind the application. The public routes are not member endpoints: the facade below, the public booking page, the Gmail push, the user pool and the documentation.
- Two lists in `tests/Architecture/EveryEndpointHasItsToolTest` hold the exceptions. The test fails and names each member endpoint that has no tool and is on neither list.
  - `NEVER`, what stays on the screen:
    - forgetting a contact (GDPR, `api_contacts_forget`);
    - the sign-in credentials (`api_me_sign_in`, `api_me_change_login_email`, `api_me_change_password`);
    - connecting a connector, because the consent to Google is given in a browser (`api_me_connections_google_authorize`, `api_me_connections_google_connect`, `api_me_connections_google_import_gmail`). Disconnecting it too, because an AI could not undo it without that consent (`api_me_connections_google_disconnect`, `api_me_connections_google_stop_gmail_import`);
    - the account's Google client and its Gmail notifications, which are credentials (`api_me_configure_google_client`, `api_me_remove_google_client`, `api_me_configure_gmail_notifications`, `api_me_remove_gmail_notifications`);
    - the identity, address and logo of the account's organization (`api_me_revise_organization_identity`, `api_me_relocate_organization`, `api_me_change_organization_logo`) (those three kinds, ADR 0183);
    - the upload form (`api_uploads_prepare`): the tool that attaches a file prepares its own form (ADR 0172);
    - the members and their roles, once they have endpoints.
  - `NEVER_TAKEN_BACK`, the writes that nothing undoes (below).

  The same test checks that:
  - a tool adapting a `GET` carries `readOnlyHint`;
  - a write tool says whether its AI asks its member first, and whether it reaches outside Prospecter (below). The AI asks first for a `DELETE`, or for a write in `NEVER_TAKEN_BACK`: cancelling an exchange, stopping a campaign, dismissing a recipient;
  - no tool adapts an endpoint in `NEVER`;
  - every route named by `#[Adapts]` or a list exists.

## Writing a tool

- Naming (ADR 0189): each tool and prompt name is English kebab-case, an imperative verb then its object. The name says the business intent. It does not translate a French word (`list-exchanges-pending-redaction`). Each intent has one verb across the server:
  - to read: `get` one item or one view (`get-calendar`), `list` a collection, `search` by criteria. A tool that adapts a `GET` uses only these three;
  - to change: `set` replaces one attribute (`set-contact-title`), `update` revises several fields (`update-campaign`);
  - to erase: `delete`;
  - the other gestures keep their business verb (`add`/`remove` for a membership, `create`, `log` for a past exchange or a sent message, `schedule`, `cancel`, `tag`/`untag`, `start`/`stop`, `dismiss`…). `tests/Architecture/McpNamesTest` holds the closed list of verbs and fails on any name outside it. A new verb joins the list on purpose. There is no exception.
- Each endpoint has one tool, `src/<Context>/Mcp/<Intent>Tool.php`, next to its controller (`Prospecting\Mcp\SearchOrganizationsTool` next to `Prospecting\Controller\SearchOrganizationsController`). A tool is a `final readonly` service. It carries `#[Adapts('<the name of the route>')]` (`Shared\Mcp\Adapts`) and `#[McpTool]` on `__invoke`:
  - its name follows the point above. Its description for the AI is in English;
  - for a read, `annotations: new ToolAnnotations(readOnlyHint: true)`;
  - for a write, `readOnlyHint: false` and `destructiveHint`. `destructiveHint` is `true` for a tool that adapts a `DELETE`, which removes or erases. It is also `true` for a write that nothing undoes (`cancel-exchange`, listed in `NEVER_TAKEN_BACK`). The AI asks its member before it calls such a tool. `destructiveHint` is `false` for any other write, because the protocol treats a write as destructive unless told otherwise;
  - `openWorldHint` is `true` only for a tool that reaches outside Prospecter: `set-organization-logo`, `set-my-picture`, `attach-file`, `retry-google-sync`, `retry-failed-google-syncs`, `check-my-connections`, `catch-up-contact-emails`, `catch-up-organization-emails`. Planning an exchange does not reach outside, even though a reaction pushes the exchange to Google afterwards. `openWorldHint` is `false` for the other tools, because the protocol treats a tool as reaching outside unless told otherwise. The read tools do not set it yet;
  - its arguments are those of the endpoint's route and query (`id`, `q`, `after`, `from`, `to`, `timeZone`…). Its `@param` describes each one;
  - its namespace is listed in the server's `registry` (`config/packages/mcp.yaml`).
- A tool does what its controller does, in the same order:
  1. It reads the route's resource through its resolver (`OrganizationResolver::read()`, `ExchangeResolver`, `CampaignResolver`, `ToDoResolver`). The core refuses an unknown one.
  2. It checks the controller's `#[IsGranted]` attributes through `Security\ToolAccess::denyUnlessGranted()`: the role, the resource's voter, `User::PERSONAL_DATA`.
  3. It builds the endpoint's input from its arguments and validates it (`Shared\Mcp\ToolInputs`). `valid()` validates it. `mapped()` denormalizes it like the query string, dates included.
  4. It calls the same core reader, query or command as the controller. When a controller composed something inline, that code moves to a reader that both share (`Contacts\ContactSearchReader`, `Dashboard\DashboardReader`, `Exchanges\AttachmentDownloads`…). It is never composed twice. When an endpoint composes its command from a large input, that input builds the command for both (`StartProspectingInput::commandOf()`, `CollectContactInput::commandOf()`).

  The tools that write an organization share these steps, done like the controller's, in `Prospecting\Mcp\OrganizationWrites::write()`. It reads the organization and checks the access (the role, the voter, the other given attributes). It validates the input and dispatches the command that the tool built. It answers the organization as `get-organization` reads it. `change()` does the same for a tool without an input. `writeOnRevision()` does the same, with the change held to the organization's `revision` that the AI gives (ADR 0187).
  - Likewise, the tools that write an exchange share `Exchanges\Mcp\ExchangeWrites`. `write()` and `writeOnRevision()` work the same way, the second held to the revision that the AI gives. `dispatched()` serves the tools that answer otherwise. `changed()` serves those among them that take no input. `answer()` gives the time the exchange was moved from, for `reschedule-exchange`.
  - The to-do tools share `ToDos\Mcp\ToDoWrites`, which always checks the right to manage personal data.
  - The campaign tools share `Campaigning\Mcp\CampaignWrites`. It reads the campaign with `CampaignResolver`, and checks its voter and the right to manage personal data. `dispatched()` serves the tools that answer nothing.
  - The member's booking page has `Booking\Mcp\BookingPageWrites`. Their profile has `Me\Mcp\MyProfileWrites`: any member, their own profile. `writable()` checks them before a picture is downloaded.
  - `CampaignInput` builds the command that prepares or revises a campaign, for both its endpoint and its tool (`preparationOf()`, `revisionOf()`).
- The eleven tools that overwrite a sheet's fields take an optional `revision` argument: the record's `revision` as the AI read it (ADR 0187). They are `update-organization-identity`, `update-organization-details`, `set-organization-address`, `set-organization-notes`, `set-contact-name`, `set-contact-title`, `set-contact-job-title`, `update-contact-details`, `update-exchange`, `set-exchange-text` and `set-exchange-venue`. When the argument is given and the record changed since, the change is refused with `changedMeanwhile`, and the AI reads the record again. Each argument's description tells the AI so. The contact tools pass the revision to the command bus themselves, through `PersonRevisions`.
- A tool that creates an organization, a contact or an exchange says that an AI creates it (ADR 0185).
  - Some tools set `byAnAI: true` on their command: `log-contact-exchange`, `log-organization-exchange`, `log-sent-message`, `schedule-contact-exchange`, `schedule-organization-exchange`.
  - Others use the input's command built for an AI (`StartProspectingInput::commandOfAnAI()`, `CollectContactInput::commandOfAnAI()`): `start-prospecting`, `create-contact`, `record-contact`.
  - Controllers never do. What the tool creates reads `createdByAnAI` in its resource and shows « MCP » on the screens. A new tool that creates something does the same.
- A file (an organization's logo, the member's picture, a file attached to an exchange) is given by its web address, and the API downloads it (ADR 0168, 0172). It goes through `Shared\Mcp\WebAddressInput` (`url`, http or https) and `Shared\Mcp\FilesOnTheWeb::whileDownloaded()`. The API downloads it:
  - only once the member has been checked;
  - never from a private network (`app.files_on_the_web_http`, a `NoPrivateNetworkHttpClient`);
  - within 5 seconds, following no redirect, and no heavier than the screen accepts.

  An unreachable address is a violation of `url`. The violation says so when the file is behind a sign-in (401, 403) or when its address redirects (to a sign-in page, perhaps). Prospecter signs in nowhere on its member's behalf. The file is removed once the command has used it, within the call. Under FPM each request has its own kernel, so no service reset can be relied on.

  - An image is then checked like the screen's image (`Shared\Mcp\ImagesOnTheWeb`, `Shared\Images`: 2 MB, JPEG, PNG or WebP). Its violations answer 422.
  - A file attached to an exchange (`attach-file`) is downloaded no heavier than the storage's upload form allows (10 MB).
    - The API sends the file with that form to the private bucket, as a browser does (`Shared\UploadSender`, `Shared\UploadsSentByTheApi`). It sends it to `PRIVATE_UPLOADS_ENDPOINT` instead of the form's origin. This variable points at MiniStack locally. It is empty on AWS, which means the form's address.
    - The API sends the form whole, with its length: S3 refuses a chunked upload, which MiniStack accepts.
    - The endpoint's command then attaches the file.
    - When the bucket fails (5xx), limits the requests (429) or is out of reach, the private files are unavailable for now (`privateFilesUnavailable`, 503). Any other refusal of the form is a server failure.
    - Such a tool sets `openWorldHint`.
- A tool answers the endpoint's resource through `Shared\Mcp\ToolResults::of()`. The resource is serialized as the controller serializes it, links (`_links`) included. So the AI can follow the links from one resource to the next, with the tool of the endpoint that each link names. A tool that logs an exchange is the exception (ADR 0166). `Exchanges\NotedOnceAnswers` answers it with `{status, exchange, erasedBy, erasedAt}` (`Exchanges\Resource\NotingResource`):
  - `noted`, with the exchange as `get-exchange` reads it. For `log-sent-message`, the exchange is the one the sending was logged as, read by the query `SendingNoted`. It may be the one that the Gmail import logged first, which then joins the campaign (`NotedOnceAnswers::ofNoted()`, ADR 0195);
  - `alreadyNoted`, with the exchange logged first. The exchange is left out when both were logged at once (`ExchangeNotedMeanwhile`);
  - `erasedBefore`, with the member who erased it and when. The member is named like an exchange's authors.

  The tool logs with `noteOnce`. So the core refuses what was logged before, by an AI, by the Gmail import or by a member.
- A refusal is the tool's error (`isError`), with the endpoint's problem+json as its text. A tool that logs an exchange answers « Already noted », « noted meanwhile » and « erased before » as its result instead (above, ADR 0166). `Shared\Mcp\RefusingTools` runs the tools and translates the refusals. `Shared\DependencyInjection\RefusingToolsPass` sets it on the server's builder. It translates:
  - a core refusal (`Shared\RefusalProblems`, like `UseCaseExceptionListener`). `tests/Shared/Mcp/RefusingToolsTest` proves this once for every tool, so a tool's tests do not re-test a refusal the core proves (ADR 0208);
  - an access denial (`Security\AccessProblems`, like `ApiAccessDeniedHandler`: `roleNotAllowed` 403, `unknown<Resource>` 404 for another account);
  - an unreachable service (`Shared\UnavailabilityProblems`, like `ServiceUnavailableListener`: `directoryUnreachable`, `googleUnreachable`, `secretsUnavailable`, `privateFilesUnavailable` 503). Its cause is logged at the error level, as Symfony logs it for the SPA;
  - the arguments' violations (422).

  Any other exception stays a server failure. A refusal reaches the AI as the SPA gets it: its `reason`, its `context` and its `detail` in English (ADR 0073). The AI words it for its member, in their language. The pass reads the tool locator that the bundle gives the builder (`setContainer`), and sets its runner (`setReferenceHandler`). A bundle app (`#[AsMcpApp]`) has a pass that sets a runner of its own, so it would need the two runners composed.
- The tools:
  - `get-me` (`Me\Mcp\WhoAmITool`, no endpoint) returns the member served, as `/api/me` reads them, and their account. It also says whether they read the personal data in clear (`readsPersonalData`, the `ROLE_PERSONAL_DATA` role).
  - `identify` (`Identification\Mcp\IdentifyTool`, no endpoint, ADR 0173) is called before an AI creates anything.
    - It says which persons and organizations some clues designate, in the order given, and writes nothing. Each one is known, new, or to confirm among candidates (id and name).
    - Prospecting identifies the organizations first, then PersonalData the persons. A person names the organization they work for by its position in `organizations`.
    - Only prospectors call it. Without the right to manage personal data, a person is identified by their names only.
    - It takes a hundred of each at most, and its schema refuses more (`#[Schema]` on its arguments). That error is the protocol's `-32602`, which the SDK raises before the tool runs, never `RefusingTools`. With no endpoint, the tool has no endpoint input to validate its arguments with.
  - `record-contact` (`Identification\Mcp\RecordContactTool`, no endpoint, `idempotentHint`, ADR 0177) records a person and the organization they work for, in one call. `Identification\ContactRecorder` composes it.
    - Both are identified first, by their keys. Or the member picks them among those to confirm (`organizationId`, through `OrganizationResolver` and the voter; `personId`). Or the member declares another one (`newOrganization`, `newPerson`).
    - When one is left to confirm, the tool writes nothing and answers its candidates.
    - Otherwise the organization is started (its SIRET noted after) or completed. Then, last, the contact is written. A known contact is completed, and attached (`AddContact`) unless the organization already names them. A new contact is collected for the organization.
    - Each one is answered:
      - `created`;
      - `known`, with the names of the fields completed and of those given differently from what was noted (`CompareWithOrganization`, `CompareWithContact`, read before the commands);
      - `new`;
      - or `toConfirm`.
    - When a step fails after the organization was created, the organization stops being prospected (`StopProspecting`) before the failure is answered.
    - Only prospectors with the right to manage personal data call it, as for `create-contact`.
  - One per read endpoint: `search-organizations`, `get-organization`, `search-company-directory`, `list-contact-exchanges`, `list-contact-upcoming-exchanges`, `get-meeting-place`, `search-contacts`, `search-contacts-by-tags`, `get-contact`, `list-contact-organizations`, `get-proposed-organization`, `list-organization-contacts`, `list-organization-exchanges`, `list-organization-upcoming-exchanges`, `list-exchanges-pending-redaction`, `get-caller`, `get-calendar`, `get-my-day`, `list-exchanges-to-handle`, `list-overlapping-exchanges`, `get-exchange`, `get-attachment-link`, `get-exchange-google-sync`, `list-my-connections`, `get-google-client`, `list-my-todos`, `get-todo`, `list-contact-todos`, `list-organization-todos`, `list-contact-tags`, `list-organization-tags`, `list-suggested-tags`, `list-campaigns`, `get-campaign`, `list-campaign-recipients`, `get-campaign-message`, `get-next-recipient`, `get-dashboard`, `get-my-account`, `get-my-reminders`, `get-my-booking-page`.
    - `get-next-recipient` (`Campaigning\Mcp\NextRecipientTool`, no endpoint, ADR 0186) returns the next recipient of a campaign being led.
      - It returns how PersonalData says the recipient is reached, the message rendered for them, and its mode. It returns its `instruction`, which `Campaigning\NextRecipientReader` words for the AI (send as is or adapt). It also returns how many recipients are left.
      - Its access is that of `get-campaign-message`.
      - It passes over the recipients that an exchange of the campaign was already logged with, before the worker processes them (ADR 0195).
      - It takes the member's `timeZone` (UTC otherwise). It answers the campaign's `dailyCap`: its `sends`, the recipients `processedToday` on the member's day, and whether the cap is `reached`. Once it is reached, it still answers the recipient, with a `warning` that tells the AI to stop and ask its member.
      - Once the campaign is no longer being led, or nobody else is left, it is refused with `campaignNotRunning`.
  - The writes:
    - `log-contact-exchange`, `log-organization-exchange` and `log-sent-message` (`idempotentHint`), with the `Message-ID` of the e-mail or the LinkedIn conversation they were read in. The last one is for a campaign recipient, and takes the text that the AI actually sent (ADR 0186);
    - on the organizations: `start-prospecting`, `update-organization-identity`, `set-organization-logo` (below), `update-organization-details`, `set-organization-address`, `set-organization-notes`, `attach-to-holding`, `add-organization-contact` (one of the organization's contacts to attach), and, destructive, `stop-prospecting` (answering nothing, like its endpoint), `detach-from-holding`, `delete-organization-logo`, `remove-organization-contact`, `dismiss-possible-duplicate-organization`;
    - on the contacts: `create-contact`, `set-contact-name`, `set-contact-title`, `set-contact-job-title`, `update-contact-details` (their LinkedIn profile among their links);
    - on the exchanges: `schedule-contact-exchange`, `schedule-organization-exchange` (pushed to the member's Google calendar, as when planned on the screen), `mark-exchange-done` (with its report), `mark-exchange-missed`, `reschedule-exchange`, `update-exchange`, `set-exchange-text`, `set-exchange-venue`, `retry-google-sync`, `retry-failed-google-syncs` (every exchange of the member that Google refused), `attach-file` (above), and, destructive, `cancel-exchange`, `delete-exchange` (answering nothing), `delete-attachment`;
    - on the to-dos: `create-contact-todo`, `create-organization-todo`, `update-todo`, `complete-todo`, and, destructive, `reopen-todo`, `delete-todo` (answering nothing);
    - on the tags: `tag-contact`, `tag-organization`, and, destructive, `untag-contact`, `untag-organization`;
    - on the campaigns: `create-campaign`, `update-campaign`, `freeze-campaign`, `add-campaign-recipient`, `add-recipients-by-tags`, `start-campaign`, `set-campaign-daily-cap`, and, destructive, `stop-campaign`, `dismiss-campaign-recipient`, `remove-campaign-recipient`, `delete-campaign` (the last three answering nothing);
    - on the member's booking page: `set-booking-slots`, `set-booking-channels`, `open-booking`, `set-booking-link`, `add-inactive-period`, and, destructive, `close-booking`, `remove-inactive-period`;
    - on their settings: `update-my-reminders`, `set-my-name`, `update-my-contact-details`, `set-my-picture` (above), `check-my-connections`, `catch-up-contact-emails` and `catch-up-organization-emails` (answering nothing; the e-mails arrive afterwards);
    - and, destructive, `dismiss-possible-duplicate-contact`.

  Each tool's `#[Adapts]` names the route it adapts.
- Their tests:
  - `tests/Api/ReadingToolsApiTest` calls each tool and its endpoint as five members: a prospector, an assistant without the personal data right, an administrator, a member who is not a prospector and a member of another account. It asserts the same answer, the resource or its problem. A test that keeps its kernel for a double (`keepTheKernel()`) sets the tokens' frozen clock a minute later.
  - `tests/Api/NotingToolsApiTest` logs an exchange with each tool, logs it again, and logs it once erased. It compares each tool's refusals with its endpoint's for an assistant, a member who is not a prospector and a member of another account.
  - `tests/Api/OrganizationAndContactToolsApiTest` calls each tool that writes an organization or a contact as each of those five members. A refusal is compared with its endpoint's. A write done by a member that its endpoint allows is answered as the screen then reads the resource (its `self` link). The test lists the destructive tools as an AI does.
  - `tests/Api/ExchangeAndToDoToolsApiTest` does the same for each tool that writes an exchange or a to-do (only the holder of a planned exchange handles it). It also attaches a file from the web (`Tests\App\Double\TheWeb`), and a file behind a sign-in, which is refused. Both go through the checks of `Tests\App\Base\ActsAsItsEndpoint`. The following tests do the same:
    - `tests/Api/TagToolsApiTest` for the tags;
    - `tests/Api/CampaignToolsApiTest` for the campaigns: a draft prepared, frozen, started then stopped; the next recipient as is and adapted; the sending logged, then logged again; and the `run-campaign` prompt;
    - `tests/Api/BookingPageToolsApiTest` for the booking page;
    - `tests/Api/MySettingsToolsApiTest` for the reminders and the profile (a picture from the web);
    - `tests/Api/ConnectorToolsApiTest` for the connections and the Gmail catch-ups (the Google double).
  - `tests/Shared/UploadsSentByTheApiTest`: the form that the API sends.
  - `tests/Api/IdentifyToolApiTest` identifies persons and organizations together, as an assistant. It refuses a member who is not a prospector, and more than a hundred.
  - `tests/Api/RecordContactToolApiTest` records a contact and their organization, as each of the five members: new, found, completed, twice, to confirm then picked or declared another one. It makes each step fail in turn, with the kernel kept (`Tests\App\Double\FailingCommands`, a middleware of the tests' command bus that the test arms). It checks that nothing the call created remains.
  - `tests/Api/SignInOfTheAIsApiTest` signs an AI in through the facade on MiniStack's Cognito. It posts the sign-in page as a browser does, then calls `get-me`. It also covers every refusal of the facade, the refresh and the sign-outs. `Tests\App\Double\ClientsOfTheAIs` doubles Claude's metadata documents and also resolves their hosts.
  - `tests/Api/McpApiTest` calls the tools in each era. It sends a `tools/call` that carries the revision in its `_meta`. It also sends `initialize` then `tools/call`, with no `Mcp-Session-Id`. It calls them with a member of each right, and with no token or a refused one.
  - `tests/Api/ProtectedResourceApiTest`: the metadata.

## The prompts

- A protocol prompt is a walkthrough that the AI offers its member (a Claude Code command, a Claude web entry).
  - It chains the tools so that one sentence from the member ("sync my relevant LinkedIn contacts") is enough, with nothing created or logged twice.
  - It reads and writes nothing itself. Its text is a message from the member to their AI, in English, naming each tool as it is served.
  - The tools check the access and refuse as they always do.
  - Prospecter reads neither LinkedIn nor the mailbox for the AI. The AI reads them with its own tools (Claude's Chrome extension, Claude's Gmail connector).
- A prompt is `src/<Context>/Mcp/<Intent>Prompt.php` (`Identification`, `Campaigning`), a `final readonly` service with `#[McpPrompt]` on `__invoke`.
  - Its name follows the tools' naming rule (above, ADR 0189). Its title and description are in English, for the member.
  - Its arguments are strings, and its `@param` describes each one in English.
  - A client may split the arguments at the spaces (Claude Code does). So the description of an argument that holds free text (a name, a moment) asks for it in quotes. The steps have the AI ask its member when the value it got does not clearly say what it names.
  - The SDK does not accept a typed object as a prompt argument, and leaves it out of the list. So an argument with a few values is a string read into an enum. Any other value is the protocol error `-32602` (`RegistryException::invalidParams()`).
  - Its namespace is listed in the `prompts` of the server's `registry` (`config/packages/mcp.yaml`).
  - It answers one message with the `user` role: its steps.
- `sync-linkedin` ("Sync LinkedIn", `SynchronizeLinkedInPrompt`; `what`, `contacts` or `messages`; `since`, optional):
  1. read LinkedIn back to then, each person with their profile, names, job title, company and company page;
     - The profile is its public address (`linkedin.com/in/<name>/`). A conversation names its participants by their member ID (`linkedin.com/in/ACoAA…`). When the AI only has that ID, it opens the profile and reads the public address there.
     - Prospecter accepts a member ID like any other link. Only the prompt keeps it out, so that `identify` and the duplicate checks compare the addresses a member types.
  2. `identify` them in batches of a hundred. Ask the member about each `toConfirm`, and let them choose the `new` ones that interest them;
  3. `record-contact`;
  4. for the messages, `log-contact-exchange` (`log-organization-exchange` for a company page), with `linkedInConversation` and `occurredAt` to the minute (the fingerprint of ADR 0166).
- `sync-mailbox` ("Sync my mailbox", `SynchronizeMailboxPrompt`; `since`, optional):
  1. read the mailbox back to then;
  2. `identify` the correspondents and keep only the `known` ones, except those the member asks to create, recorded by `record-contact`;
  3. `log-contact-exchange` (or `log-organization-exchange`) with each e-mail's `messageId`. An e-mail sent to two contacts is logged with each.
- Both prompts:
  - take `since` in quotes, and have the AI ask for it first when it is not given or not a clear moment (`SynchronizedSince`);
  - end with a report (created, completed, already logged, ignored). `alreadyNoted` and `erasedBefore` count as already logged, so a sync run again creates nothing;
  - restate the rules. Nothing `toConfirm` is created without the member's answer. Nothing is logged without its reference when the AI reads it. A refusal from Prospecter stops the AI, which asks its member.
- `run-campaign` ("Run a campaign", `Campaigning\Mcp\LeadCampaignPrompt`, ADR 0186; `campaign`, its ID or its name in quotes, since a client may split the arguments at the spaces):
  1. find the campaign with `list-campaigns`. Ask the member which one to run when the argument is not the ID or the exact name of a single started campaign;
  2. take `get-next-recipient`, with the member's time zone;
  3. send the message with the AI's own tools, as is or adapted as its instruction says;
  4. log it with `log-sent-message`, with the text actually sent and its reference. If the mailbox import logged it first, it joins the campaign (ADR 0195). Go on when it is already logged;
  5. dismiss whoever the AI cannot reach with `dismiss-campaign-recipient`, once the member has confirmed;
  6. never send a second message to a recipient given again, but stop and say who;
  7. when `get-next-recipient` warns that today's cap is reached, stop and ask the member whether to go beyond it;
  8. repeat until the campaign is no longer running, then report.
- Their tests are `tests/Api/SynchronizeLinkedInPromptApiTest`, `tests/Api/SynchronizeMailboxPromptApiTest` and `tests/Api/CampaignToolsApiTest`. They list each prompt with its title and arguments, and get its steps for each argument. They refuse a `what` value outside its list. They check that every tool its text names is served: `identify`, and those in kebab-case between backquotes. `tests/Architecture/McpNamesTest` checks each tool and prompt name against the verb list.

## The AIs' sign-in

Cognito is the authority, but it cannot be, on its own, the authorization server that Claude expects. It has no Client ID Metadata Document and no dynamic registration. Its discovery has no `code_challenge_methods_supported`, and its authorization code has no `resource` (RFC 8707). The API's facade fills those gaps and keeps nothing (`src/SignIn/OAuth`):

- `GET /.well-known/oauth-protected-resource/mcp`, and the same path without `/mcp` (RFC 9728; `SignIn\Controller\ShowProtectedResourceController`, public, outside the API's contract). Its `resource` is `<api>/mcp`, exactly as the member types it in Claude. Its `authorization_servers` is the API alone, because Claude reads only the first one.
- `GET /.well-known/oauth-authorization-server` (RFC 8414, `SignIn\Controller\ShowAuthorizationServerController`, `SignIn\OAuth\AuthorizationServer`). It gives its endpoints, `response_types_supported: [code]`, `grant_types_supported: [authorization_code, refresh_token]`, `code_challenge_methods_supported: [S256]`, `token_endpoint_auth_methods_supported: [none]`, and `client_id_metadata_document_supported: true` (with `none`, Claude's condition for using it). It gives no `registration_endpoint`.
- `GET /oauth/authorize` (`AuthorizeController`):
  - The client is its Client ID Metadata Document (`OAuth\ClientMetadataDocuments`). The document is an HTTPS URL, fetched only when its host is on no private network (Symfony's `NoPrivateNetworkHttpClient` refuses it otherwise). It is fetched within 5 s and 5 kB, following no redirect. Its `client_id` must equal its URL, and the `redirect_uri` must be among its `redirect_uris` (`OAuth\ClientMetadata`). A loopback address matches without its port (Claude Code declares `http://localhost/callback` and `http://127.0.0.1/callback`).
  - An invalid client or redirect answers 400 `application/problem+json` with the `unknownClient` `reason` (`OAuth\UnknownClientListener`). It never redirects to an unchecked address.
  - Once the redirect is checked, the facade sends the AI back with its error when it got something wrong: a `response_type` other than `code` (`unsupported_response_type`), a missing S256 `code_challenge` (`invalid_request`), or a `resource` other than `<api>/mcp` when given (`invalid_target`).
  - Otherwise, it redirects to Cognito's sign-in page (`OAuth\CognitoSignIn`, `COGNITO_SIGN_IN_URL`). It passes the `prospecter-mcp` client, a PKCE of the API (`OAuth\Pkce`), and, as `state`, the request sealed for 15 minutes.
- `GET /oauth/callback` (`ComeBackFromCognitoController`): Cognito comes back with its code. The facade redirects to the AI with its `state`. As `code`, it gives the request and Cognito's code sealed together (5 minutes). It passes Cognito's `error` when the member did not sign in. A `state` that the facade did not seal gets a 400 that leads nowhere.
- `POST /oauth/token` (`IssueTokensController`, `application/x-www-form-urlencoded`):
  - `authorization_code`: the seal is opened, and the AI's verifier is checked against its challenge, with the same client and redirect. Then Cognito's code is exchanged at its token endpoint (`OAuth\CognitoTokens`, `COGNITO_OAUTH_URL`). The exchange uses the `prospecter-mcp` secret (read from Cognito by `UserPool::mcpClientSecret()`, cached) and the API's verifier. Cognito's tokens are answered as they are.
  - `refresh_token`: passed to Cognito.
  - Every refusal is `invalid_grant`. Another grant gets `unsupported_grant_type` (RFC 6749, `OAuth\GrantRefusedListener`).

  The protocol forces two exceptions to one intent per endpoint (ADR 0025). `/oauth/token` carries two intents, by its `grant_type`. `/oauth/authorize` and `/oauth/callback` only redirect.
- Nothing is kept. The request travels sealed, first in the `state` given to Cognito, then in the code given to the AI. The seal is `OAuth\Seal`: `sodium_crypto_secretbox`, keyed by `OAUTH_SEAL_KEY`, an API secret. Each seal serves one purpose for a limited time (`OAuth\SealPurpose`). Cognito refuses its code a second time, so the facade's code works once. There is no table, no item, and nothing in the core.
- Claude web identifies itself with the document that Anthropic publishes. Its redirect is `https://claude.ai/api/mcp/auth_callback`. Claude Code identifies itself with its own document (`https://claude.ai/oauth/claude-code-client-metadata`), on a loopback port of the session. Neither needs any configuration.
- A global sign-out revokes the refresh tokens of all the member's clients, their AIs included. So the SPA signs out by revoking only its own refresh token (`RevokeToken`). It keeps a global sign-out for « Se déconnecter partout » in the account menu. An access token stays valid until it expires (1 h), as for the SPA (`auth.md`).

## Trying it

- Spike #202 proved the server on a throwaway branch, before it was built, in the Lambda on MiniStack. It tried:
  - a `tools/call` of revision `2026-07-28`, its `_meta` carrying `io.modelcontextprotocol/protocolVersion` and `io.modelcontextprotocol/clientCapabilities`, with the `MCP-Protocol-Version`, `Mcp-Method` and `Mcp-Name` headers;
  - then `initialize` and `tools/call` of `2025-11-25`, answered without `Mcp-Session-Id`;
  - `GET` 405, a refused token 401, and the MCP Inspector.
- The metadata name the API by `APP_API_HOST`, without the port that a worktree serves its stack on. So a client that follows them locally needs the main checkout's stack, on 443.
- Locally, try the facade on the main checkout's stack:
  1. open `GET https://api.prospecter.localhost/oauth/authorize?response_type=code&client_id=<a metadata document reachable in https>&redirect_uri=<one it declares>&code_challenge=<S256>&code_challenge_method=S256&state=…` in a browser;
  2. sign in on MiniStack's sign-in page (a demonstration member);
  3. exchange the code with `curl -d grant_type=authorization_code -d code=… -d client_id=… -d redirect_uri=… -d code_verifier=… https://api.prospecter.localhost/oauth/token`.
- Locally, try the server with `curl` and a demonstration member's token (`Authorization: Bearer`). The token is the access token that the SPA sends, read in the browser's network panel. Call it in each era: a `tools/call` that carries revision `2026-07-28` in `params._meta`, or `initialize` then `tools/call`. Also use the MCP Inspector (`npx @modelcontextprotocol/inspector --cli <url> --transport http --header "Authorization: Bearer …" --method tools/list`).
- Claude Code and Claude web need an instance that Anthropic can reach:
  - Claude Code: `claude mcp add --transport http prospecter https://api.<domain>/mcp`, then `/mcp` to sign in.
  - Claude web: Customize › Connectors › Add custom connector, with the URL `https://api.<domain>/mcp` and nothing else. It needs an instance that Anthropic's egress (`160.79.104.0/21`) can reach, which the local stack is not.
