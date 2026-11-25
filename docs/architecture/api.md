# The API

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

`apps/api` is a JSON API that exposes the core's use cases and acts as a resource server for the SPA (ADR 0006, 0052). Its persistence is described in `persistence.md`, its authentication in `auth.md`, and how it runs in `local-stack.md` and `infra.md`.

## Writing an endpoint

- Routes, fields, links and enum values are English words, never calques of French (ADR 0190): a contact's `title`, the exchanges `toRedact`, a Gmail `reimport`, `POST /api/campaigns/{id}/start`. Fields and enum values are lower camelCase (`videoCall`). A refusal's `reason` is its exception's short name (`nothingToRedact`).
- Each endpoint has one business intent (ADR 0025) and one invokable controller. The controller lives in its bounded context's folder: `src/<Context>/Controller/<Intent>Controller.php` (`Prospecting/Controller/RelocateController`, ADR 0087). `src/Me`, `src/Connectors`, `src/Dashboard` and `src/Contacts` compose contexts:
  - `src/Me`: the `/api/me` endpoints: membership, profile, reminders (`/api/me/reminders`).
  - `src/Connectors`: the member's connections (ADR 0120).
    - `GET /api/me/connections` returns them in their last known state. `POST /api/me/connections/checks` asks their providers.
    - `POST /api/me/connections/google/authorizations` returns the Google address for the Google client of the member's account. It also returns the `state` and the verifier that `Connectors\Google\GoogleAuthorization` generates. It is refused while the account has no client (`providerUnavailable`, 409). The `connectGoogle` and `reconnect` links are then missing. `POST /api/me/connections/google` passes it the code that Google sent the SPA back with. `DELETE /api/me/connections/google` disconnects.
    - Each connection counts the member's upcoming exchanges that are `synced` and `failed` in their calendar. `POST /api/me/connections/google/retries` pushes all the failed ones again at once (link `retryFailed`, while one failed).
    - `GET /api/exchanges/{id}/google-sync` says where the push of an exchange into the member's calendar stands. It returns the `sync` (`pending`, `synced` or `failed`), its moment `at` and its `reason` (`calendarRefusedThePush` or `toReconnect`). It returns none for another member's exchange, without a connection, or for a past exchange never pushed. The exchange's holder pushes it again with `POST /api/exchanges/{id}/google-sync/retries` (link `retry`, once Google refused it; `notTheHolder` otherwise), ADR 0133.
    - The Google client of an administrator's account (ADR 0125): `GET`, `PATCH` and `DELETE /api/me/organization/clients/google`, `ROLE_ADMIN`. Its secret is never returned. The connections link it as `googleClient`.
    - Gmail e-mail import (ADR 0154):
      - The client returns `gmailNotificationEndpoint`, where the account's subscription pushes. Once the administrator has set them, it also returns `gmailNotifications` (`topic`, `pushAccount`). `PATCH` and `DELETE /api/me/organization/clients/google/gmail-notifications` change them (links `configureGmailNotifications`, while the account has a client, and `removeGmailNotifications`; `invalidTopic` 422).
      - Each connection returns `gmailImport` (`off`, `active`, `toReconnect`) and `lastGmailNotificationAt`.
      - `POST /api/me/connections/google/gmail-import/authorizations` asks for the import. It needs `ROLE_PERSONAL_DATA`. Its link is `importGmail`, while the account has a topic and the import is not active. Without a topic, it is refused with `mailImportUnavailable`, 409. It returns the Google address that asks for `gmail.readonly` along with the calendar, by incremental consent. Its code goes to `POST /api/me/connections/google`, as for a connection. `DELETE /api/me/connections/google/gmail-import` stops the import (link `stopGmailImport`, while it is not off).
      - `POST /api/contacts/{id}/gmail-reimports` reimports the e-mails already exchanged with a contact. `POST /api/organizations/{id}/gmail-reimports` does the same for an organization itself, and goes through the organizations' voter. Both need `ROLE_PERSONAL_DATA` and return 202 without a body. The import records the e-mails a moment later. The `reimportGmail` link appears on the contact and on the organization's timeline while Connectors offers it (`ConsultMailCatchUpIntents`). Refusals: `mailImportNotActive` and `noAddressToReimport`, 409; `unknownContact` and `unknownOrganization`, 404 (ADR 0176).
      - Pub/Sub pushes its notifications to `POST /api/accounts/{account}/gmail-notifications` (204), behind its own firewall (`auth.md`).
      - `app:gmail:renew-watches` renews the watch of each active import. It is one of the tasks that an instance's daily schedule runs. `app:scheduled-tasks:run` runs them all (ADR 0161).
  - `src/Dashboard`.
  - `src/Contacts`:
    - a contact, `GET /api/contacts/{id}` (`ContactReader`). It comes with the intents it offers, the link to its timeline when Prospecting offers it, and the Gmail reimport when Connectors offers it;
    - `GET /api/contacts`, with each contact's organizations and every tag it carries (`TagsOfContacts`, ADR 0141). It links `searchByTags` for every prospector. `GET /api/tagged-contacts?tags=ESN,EMAIL` returns the contacts that carry the 1 to 5 chosen tags (the tags' names without their mark). Campaigns finds and counts them (`found`). PersonalData reads them in clear or masked, with their organizations and tags, newest first, after a cursor. An invalid selection is refused with `invalidTagSelection` or `invalidTag` (ADR 0143);
    - `GET /api/phone-numbers/{phoneNumber}`: who is calling (ADR 0072);
    - `GET /api/contacts/{id}/organizations` and `GET /api/organizations/{id}/contacts`;
    - an organization's timeline, `/api/organizations/{id}/exchanges` (`OrganizationTimelineReader`, ADR 0076: one batch call per context). PersonalData names its persons. The Gmail reimport is linked when Connectors offers it. `/api/organizations/{id}/exchanges-to-redact` narrows the timeline to the exchanges to redact (ADR 0079);
    - the upcoming exchanges with an organization (`/api/organizations/{id}/upcoming-exchanges`);
    - the exchanges in the member's agenda that a slot overlaps (`GET /api/me/planned-exchanges`, `AgendaReader`);
    - the member's calendar (`GET /api/me/calendar?from=&to=`, `to` excluded, ADR 0115, 0119). It lists the exchanges that the member holds over a period of six weeks at most, whatever their status. Each exchange that the member may move at the time of reading carries its `move` link. The `searchContacts` link lets the member plan in a free slot. Every prospector plans (ADR 0129);
    - the member's day on the dashboard (`GET /api/me/day?from=&to=`). It holds what the member still has planned on the day the browser gives, then the next five exchanges. Each exchange comes with its contact's organization, read in one batch. The exchanges whose time has come carry the `markDone` and `markMissed` links. `searchContacts` works as in the calendar;
    - the exchanges the member has to process (`GET /api/me/exchanges-to-process`): planned, ended and left unhandled, oldest first, whatever their day. Each one comes with its contact's organization and with the `markDone`, `markMissed`, `move` and `cancel` links that its state offers.

    These agenda readings leave out an exchange held with an organization alone in one case: stopping prospecting erased that organization before the exchange, and the reaction that erases the exchange has not run yet (`InterlocutorResource::known()`).
  - `src/PersonalData/Controller`: forgetting a contact.
  - `src/Prospecting/Controller`:
    - the exchanges with a contact (`/api/contacts/{id}/exchanges`, ADR 0074);
    - the upcoming ones (`/api/contacts/{id}/upcoming-exchanges`, where they are planned, ADR 0111);
    - the place proposed to meet the contact (`/api/contacts/{id}/meeting-place`).
  - `src/ToDos`: the to-dos (ADR 0113).
    - A contact's to-dos (`/api/contacts/{id}/to-dos`) and the to-dos of an organization and its contacts (`/api/organizations/{id}/to-dos`). To-dos are created there.
    - The to-dos the member still has to do (`/api/me/to-dos`).
    - One to-do (`/api/to-dos/{id}`), revised and erased there. `POST` and `DELETE` on `/api/to-dos/{id}/done` tick it off and untick it.

    Every gesture needs the right to manage personal data. `ToDoReader` names the to-dos' holders and their contacts through PersonalData. `ToDoResolver` and `ToDoVoter` reach a to-do.
  - `src/Tags`: the tags that Campaigns keeps (ADR 0138):
    - an organization's tags, `GET` and `POST /api/organizations/{id}/tags`, through its voter (`TagsReader`);
    - a contact's tags, `/api/contacts/{id}/tags` (ADR 0141, `ContactTagsReader`). The core checks that the contact belongs to the account. The answer holds the contact's own tags, the tags it inherits with the organizations they come from, and the automatic ones;
    - `DELETE .../tags/{tag}` removes an own tag (its name without its mark). Every prospector can tag;
    - the tags proposed to the account, `GET /api/tags`: the predefined ones by family, then the account's own. The automatic ones come separately (`automatic`), to search contacts by. The cards link them as `tags`.
  - `src/Campaigning`: the team's campaigns, which Campaigns keeps. Accounts and PersonalData name their authors (ADR 0144).
    - `GET` and `POST /api/campaigns`: the whole list. Each campaign comes with its target counted and its `progress`, that is its recipients `processed` and `dismissed` (none before it starts).
    - `GET`, `PATCH` and `DELETE /api/campaigns/{id}`: a draft is read, revised as a whole, or discarded. Its `target` link is the search by its targeted tags, `/api/tagged-contacts`. `POST /api/campaigns/{id}/freeze` freezes it (link `freeze`).
    - Each campaign has its `dailyCap`, set as it is prepared (`dailyCap` in its input) and changed by `PATCH /api/campaigns/{id}/daily-cap` (link `setDailyCap`) until it is stopped or ended; `null` takes it off. A cap below 1 is refused with `invalidDailyCap`, 422. `GET /api/campaigns/{id}` takes the reader's `timeZone` (UTC otherwise, `Shared\Input\MyClockQuery`) and answers its `processedToday`, the recipients processed on the reader's day; a list answers none.
    - Once a campaign is frozen, its name and its message can still be revised.
      - Its `recipients` link leads to `GET /api/campaigns/{id}/recipients`. This endpoint returns every recipient, read in full the way the tag search shows its contacts (`RecipientsReader`).
      - Each recipient has its `status` (`pending`, `processed`, `dismissed`, `erased`), whether its call was `missed`, its dismissal `reason`, and its `remove` link, `DELETE /api/campaigns/{id}/recipients/{contactId}`.
      - `POST /api/campaigns/{id}/recipients` adds a chosen contact (link `addRecipient`). `POST /api/campaigns/{id}/recipients/by-tags` adds the contacts of a search (link `addRecipientsByTags`). Each returns the campaign, with its recipients counted as `targeted`.
    - `POST /api/campaigns/{id}/start` starts a campaign (link `start`, once it is frozen and while it keeps a recipient). A frozen campaign that its contacts' erasure emptied is refused with `campaignWithoutRecipient`, 422. `POST /api/campaigns/{id}/stop` stops it (link `stop`). Each returns the campaign with its `startedAt` and its `stoppedAt`.
      - A started campaign refuses any other gesture (`startedCampaign`, `startedCampaignNotDiscarded`, 409).
      - It shows a recipient that PersonalData erased without its `contact`, with its status. The status is `erased` when the recipient was still to handle. Otherwise the campaign keeps its `processed`, `missed` or `dismissed`, without its `reason`.
    - A recipient still to handle in a started campaign links `noteExchange`, `POST /api/campaigns/{id}/recipients/{contactId}/exchanges`.
      - That route is Prospecting's controller, `src/Prospecting/Controller/NoteCampaignExchangeController`, without the campaigns' voter. The core refuses another account's campaign (`unknownCampaign`). The controller returns 201, the contact's timeline and the exchange's `Location`.
      - The recipient also links `dismiss`, `POST /api/campaigns/{id}/recipients/{contactId}/dismissal` (204).
      - Both are refused once the campaign no longer runs (`campaignNotRunning`) or once the recipient is handled (`recipientAlreadyProcessed`, 409), ADR 0145.
    - A processed recipient links the exchange logged for them, `exchange` (`GET /api/exchanges/{id}`), ADR 0152.
    - Once a campaign has ended (`endedAt`) or stopped, it links only itself and its recipients.
    - An exchange in a timeline and an exchange's detail name the exchange's `campaign` (`id`, `name`). They name none once the campaign is erased, and none for an exchange outside any campaign. The detail links it as `campaign` for a member with the right to manage personal data, while the campaign is kept (ADR 0152).
    - An exchange in a timeline and an exchange's detail name the provider of the mailbox that the exchange was imported from, `importedFrom` (`google`). They name none for an exchange logged by hand. The detail links the exchange's Gmail message, `openInMailbox` (`https://mail.google.com/mail/#all/<id>`, opened in a new tab). It does so for a member with the right to manage personal data, once the message's mailbox id is kept (ADR 0174).
    - Only operations erase a campaign, whatever its status: `bin/console app:campaign:erase <id>`.
    - `GET /api/campaigns/{id}/messages/{contactId}`: the campaign's message, rendered by the core for a contact.

    Every gesture needs the right to manage personal data. A campaign is reached through `CampaignResolver` and `CampaignVoter`. My account links the campaigns as `campaigns`.
  - `src/Booking`: the member's booking page (ADR 0148, 0151):
    - The member manages the page through these endpoints. Each one returns the page:
      - `GET /api/me/booking-page?timeZone=`. It leaves out the page's inactive periods that ended before the reader's day.
      - `PATCH .../windows`, all at once, with the browser's time zone.
      - `POST .../inactive-periods` (201) and `DELETE .../inactive-periods/{id}` (each period's `remove` link).
      - `PATCH .../channels`.
      - `POST` and `DELETE .../opening` (links `open` and `close`, as the page's state offers them). Opening is refused without a connected calendar (`noConnectedCalendar`) or without a channel (`noChannelOffered`), 422.
      - `POST .../link` (link `changeLink`, once the page has one).

      Every member manages their own page (`ROLE_USER`). `Booking\BookingCodes` generates the link's code.
    - `GET /api/booking-pages/{code}` returns the page that a link leads to. It is read without signing in (`auth.md`). It returns who the host is, their channels, their time zone, and the free slots over the 14 days from tomorrow. It gives the same empty answer in three cases (ADR 0153):
      - for a code that no page has now, whatever its shape, a truncated one too;
      - for an oversized code, beyond 64 characters. The limiter does not count it and the table is not searched for it, because the table's keys hold 2 KB at most;
      - for a closed or suspended page.

      The answer is cached for 30 seconds (`Cache-Control: public, max-age=30`).
    - Every public Booking route is rate limited by its link's code. A route says so with its default `_limited_by_its_code` (`Booking\PublicBookingRoutes`). The limiter is `public_booking_link` in `config/packages/rate_limiter.yaml`: 30 a minute, with its counts in Booking's table (`persistence.md`). Beyond the limit, the route answers 429 `tooManyRequests` with `Retry-After`, which nobody caches. The installation's edge rate limits by address, not the app. The app never reads the client's address.
  - `src/Exchanges`: one exchange (`/api/exchanges/{id}`):
    - its detail;
    - the correction of its `facts` or of its `text` alone (ADR 0078), or of its `venue`, a meeting's place or a video call's link (ADR 0114);
    - the number its interlocutor is called at, when read in clear (never without the right to manage personal data, ADR 0129);
    - a planned exchange marked `done` with its report, or `missed`;
    - its `moment` moved, with a new duration if one is given (ADR 0119). The response links `moveBack` while the moment it was moved from is still to come (ADR 0135);
    - its `cancellation` by its holder (ADR 0112);
    - its erasure;
    - its `attachments`: attached, downloaded through a link signed for a short time, and removed. The browser first sends the file with the `POST /api/uploads` form (ADR 0077).

    `ExchangeReader` names the exchange's interlocutor: the contact, through PersonalData, or the organization itself, through Prospecting. Through PersonalData, it also names the members who logged, corrected and redacted the exchange. It names them under the person ids that Accounts' `ConsultMembersService` gives them, asked once for all of them.
  - A contact is collected with its `organizationIds` (ADR 0042).
- A controller is a `final readonly` class that extends nothing (ADR 0198). It receives its dependencies through its constructor (ADR 0044). `tests/Architecture/ControllersExtendNothingTest` fails otherwise.
  - It answers JSON through `Shared\JsonResponses`, which writes the data with the Serializer and the encoding options of a `JsonResponse`. No controller builds a `JsonResponse` itself, which would write it with `json_encode`. The same test fails otherwise.
  - It builds a URL, such as a `Location` header, with `UrlGeneratorInterface`, and a redirection with `new RedirectResponse()`.
  - A right that depends on the resource is checked with `AuthorizationCheckerInterface`, and refused with an `AccessDeniedException` (`Prospecting\Controller\StopProspectingController`). Any other right is an `#[IsGranted]` attribute.
  - A change dispatches its command on `Shared\CommandBus`. The bus is synchronous, and it throws the core's refusals back as they are.
  - Then the change returns the fresh resource, read by a query (`Prospecting\OrganizationReader`, or `Me\MyAccountReader`, which composes Accounts and PersonalData). Commands return nothing (ADR 0029).
  - A command is flat (ADR 0035). The controller passes the input's fields, the new resource's id from `Shared\Identities` (UUID v7), and `executedAt` from the Symfony clock (ADR 0036).
- `src/Identification` also composes Prospecting and PersonalData, without an endpoint.
  - It finds which persons and organizations some clues point to, for the MCP tool `identify` (`IdentificationReader`, ADR 0173).
  - It records a contact and their organization in one call, for the MCP tool `record-contact` (`ContactRecorder`, ADR 0177).
- URLs are resource-oriented. `POST` creates a resource or adds an entity to an aggregate (`POST /api/organizations`, `POST .../logo`). `PATCH` changes one part of a resource, exposed as a sub-resource (`PATCH /api/organizations/{id}/address`). `DELETE` removes one. No endpoint uses `PUT`. Ids are UUIDs: routes declare `requirements: ['id' => Requirement::UUID]`.
- Input: `#[MapRequestPayload]` / `#[MapQueryString]` with a `final readonly` DTO in `src/<Context>/Input`.
  - The DTO declares its constraints as attributes, with `#[Assert\Valid]` on nested DTOs. It is shaped like the matching part of the resource (`AddressInput` like `AddressResource`).
  - The controller passes its fields to the flat command (ADR 0035).
  - Unknown fields are rejected with one 422 violation each (`serializer.default_context` in `config/packages/framework.yaml`), never ignored. One query string is one DTO.
  - Some readings depend on their reader's day or hour (what is late, what is on today). Such a reading takes the `timeZone` query parameter, a time zone database name (`Assert\Timezone`, 422 otherwise). The controller sets the server clock in that zone before it gives the moment to the query. For a period, it uses the offset of `from` without it (`CalendarQuery`). Otherwise it uses UTC (`ReaderClockQuery`). A day without an hour is never converted to an instant (ADR 0134).
- Files: `#[MapUploadedFile]`, then `new UploadedFileInfo($file)`, an `SplFileInfo` whose extension is guessed from the content. Files always go on `POST`, because PHP does not parse multipart bodies on `PATCH`.
- Output: every intent returns the fresh resource (`OrganizationResource`, `AccountResource`). A resource is a `final readonly` class in `src/<Context>/Resource`. A presenter in `src/<Context>/Presenter` builds it from a query's response. `src/Me/AccountResourcePresenter` presents my account.
  - The dashboard, `GET /api/dashboard`, presents Reporting's figures (`ConsultFigures` at the time of the call, with `Dashboard\Presenter\FiguresPresenter`). It reuses `OrganizationPagePresenter` for its markers only: a Prospecting search of 1 000 organizations at most. A figure may lag behind a gesture by the time its fact takes to be delivered, about a second.
  - A presenter is an object of the call, never injected (ADR 0085). It implements its query's output port. The controller or the reader builds it with the services it needs and the answers read before. It passes the presenter to the query, then returns its `resource`. The readers are `OrganizationReader`, `TimelineReader`, `ExchangeReader`, `MyAccountReader`, `ContactReader` and `OrganizationTimelineReader`. Other readers compose several queries and are shared with the MCP server's tools: `OrganizationSearchReader`, `ContactSearchReader`, `TaggedContactsReader`, `OrganizationContactsReader`, `CallerReader`, `DashboardReader`, and `CampaignReader`, whose `CampaignPresenter` and `CampaignListPresenter` read the authors through `CampaignAuthors`.
  - The other answers are read with the core's presenters that keep them (`KeptPersons`).
  - A stateless mapper is a factory (`TimelineResourceFactory`, `ContactInListResourceFactory`, and for images `Shared\ImageResourceFactory`). The image factory gives `url`, `smallUrl` 96 px wide and `mediumUrl` 256 px wide. The storage app makes each size on its first request, and never enlarges an image. The front picks the size it displays.
  - Inputs live in `src/<Context>/Input`. What several contexts use lives in `src/Shared/{Input,Resource}`.
- A member's endpoint comes with its MCP server tool, next to its controller (`src/<Context>/Mcp/<Intent>Tool.php`, with `#[Adapts]` naming its route). Otherwise the endpoint is listed in `tests/Architecture/EveryEndpointHasItsToolTest`: in `NEVER` when it stays on the screen. The test fails otherwise (ADR 0163, `mcp.md`).
- Document every endpoint: `#[OA\Tag]`, `#[OA\Get|Post|Patch|Delete]` (summary), and one `#[OA\Response]` per status with `new Model(type: ...)`. Then run `make api-doc` to regenerate `openapi.json` and the front types. `tests/Api/OpenApiContractTest` fails `make test` while the committed `openapi.json` differs from the code.
- Roles: each controller declares one `#[IsGranted('ROLE_PROSPECTOR')]` or `#[IsGranted('ROLE_USER')]`. `ROLE_ADMIN` inherits both, plus `ROLE_PERSONAL_DATA`: an administrator manages the personal data that the core lets them read (`Member::readsPersonalData()`). The core does not check roles.
- Access to a Prospecting resource (ADR 0081): the controller receives the resource's read model, an `OrganizationSheet` or an `ExchangeDetail`. A resolver finds it from the route's `id` (`Prospecting\OrganizationResolver`, `Exchanges\ExchangeResolver`). The controller declares `#[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]`, or `ExchangeVoter::ACCESS`. `tests/Architecture/ResourcesAreReachedThroughTheirVoterTest` fails otherwise.
  - The voter (`Security\AccountResourceVoter`) only refuses another account's resource. It answers a 404 named like the core's refusal (`unknownOrganization`, through `Security\ApiAccessDeniedHandler`).
  - An intent that needs the right to manage personal data also declares the role, `#[IsGranted(User::PERSONAL_DATA)]` (403). `StopProspecting` needs it only while the organization has contacts, so it asks `AuthorizationCheckerInterface`.
  - The core checks the state (409). It still checks that a contact belongs to the account, and what PersonalData lets its reader do.
  - The `#[IsGranted]` attributes are the table of the rights. `tests/Api/EndpointRightsApiTest` reads them and calls each endpoint of a member with each profile (`docs/architecture/tests.md`, "API tests"). A new endpoint declares its rights there, and needs no test of its own for them (ADR 0212).
- Links: a resource carries `_links` (HAL, plus `method`). Its presenter builds them from the possible intents in the core's response (`Shared\Links`).
  - A links resource declares the route of each intent link it carries: `#[Offers('api_campaigns_revise')]` on its property (`Shared\Resource\Offers`, ADR 0217). A property declares one route, or one route for each presenter that fills a shared links resource with its own route: `#[Offers('api_contacts_note_exchange', 'api_organizations_note_exchange')]` on the timeline's `noteExchange`. An intent link leads to a controller that takes `Shared\CommandBus`. A navigation link, to a query, may declare nothing. Presenters, factories and tables of routes build their links as they want. The event storming reads the declarations (`event-storming.md`, "What a view offers").
  - In the tests, each link that a resource carries is checked as it is serialized (`tests/Base/OffersMatchTheirLinks`). A declared link that leads to another route fails, and so does an intent link without a declaration. The check throws an exception that names the resource, the property and the routes, and the call answers 500.
  - Prospecting's links appear only when the member holds the role that their controller requires. The presenter's table declares that role next to the route (`Security\IntentLinks`, ADR 0081, 0088).
  - A link's key is absent when the intent is not possible right now, and the call would then fail (`SKIP_NULL_VALUES` on `_links`, optional in the contract).
  - Such a link is a nullable parameter of the links resource, written `?LinkResource`. Its `#[OA\Property(description:)]` says what the link does, then when it is offered, in a sentence that starts with "Offered only": `Open the page. Offered only while the page is closed`. The event storming reads that sentence to draw the view's offer (`event-storming.md`). Without it, the wall says "only in some states".
  - Document a new intent as a new link.
- Errors: a use case refuses with `UseCaseRefused`. `Shared\UseCaseExceptionListener` turns it into an RFC 9457 `application/problem+json` according to its `ErrorType`:
  - `NotFound`: 404;
  - `NotPossibleInItsState`: 409 (the link is missing);
  - `NotAllowed`: 403, in these cases (the voters refuse the rest first):
    - in PersonalData, without the right to manage personal data: recognizing a caller by their number, collecting or forgetting a contact, downloading an attachment, a colleague's profile;
    - in Prospecting, a planned exchange handled by a member other than its holder (`notTheHolder`, ADR 0112);
    - in Connectors, a code that the provider refuses (`accessNotGranted`, ADR 0120), and an account's client touched by a member who is not an administrator (`notTheAdministrator`, ADR 0125);
  - `BusinessRuleViolated`: 422 with `violations`.

  A write to the same organization, person or exchange may cancel a command's transaction after the command read it (`ConditionFailed`, ADR 0181). `Shared\CommandBus` then runs the command again from its reads when the core marks it as an addition (`Addition`: `CollectContact`, `AddContact`). `AttachFile` is not an addition. The storage keeps its upload before the commit, and keeps it only once. The bus retries three times at most, after a random wait under 100 ms. Otherwise, or if the command is still cancelled, it is refused with `ChangedMeanwhile` (409 `changedMeanwhile`). The member or the AI then reads the record again.

  A client can send back the revision it read. Then a change made since by someone else is refused rather than erased (ADR 0187).
  - The resources of an organization, a contact and an exchange carry `revision`: the revision of the item they were read from. Their reader watches it (`persistence.md`).
  - The changes that overwrite a sheet's fields take it as the optional query parameter `revision` (`#[MapQueryParameter]`, 0 or more, 422 otherwise; documented by `RevisionDescription`). They never take it in the body, because creations share those inputs. These changes are the organization's `legal-identity`, `contact-details`, `address` and `observations`, the contact's `name`, `title`, `job-title` and `contact-details`, and the exchange's `facts`, `text` and `venue`.
  - The controller passes `readAt(id, $revision)` of its aggregate's revisions to `Shared\CommandBus::dispatch()`. The bus holds the unit to that revision: if the item changed since, it answers 409 `changedMeanwhile`. Without it, the change holds to the revision that its command reads.
  - Additions and the other gestures take no revision.

  The problem and its violation carry the `reason` (the refusal's class, `invalidPhoneNumber`) and its `context`. The SPA words them in French (ADR 0073).

  `tests/Shared/UseCaseExceptionListenerTest` proves each of these translations once for the whole API. An endpoint's tests do not prove them again: they prove its route, its input, its persistence, its response and its links. `EndpointRightsApiTest` proves its rights (ADR 0212). None re-tests a refusal the core proves (ADR 0208, `tests.md`).

  An AWS service out of reach is a `Shared\ServiceUnavailable`. Once Symfony has logged its cause, `Shared\ServiceUnavailableListener` answers it with a 503 and its `reason`:
  - `secretsUnavailable`: SSM refuses or fails to put, read or delete an account's secret. `Shared\Persistence\AccountSecrets` throws it;
  - `googleUnreachable`: Google does not answer a code exchange or a connections check (a timeout, a 5xx or a 429). `Connectors\Google\GoogleProviderAccess` throws it. Nothing gets connected, and the connections stay as last seen. The disconnection goes on without Google;
  - `directoryUnreachable`: the company directory fails, rate limits, answers no JSON, or does not answer a search for an organization's establishments. `Prospecting\Directory\RechercheEntreprisesDirectory` throws it. The member can still type the SIRET by hand (ADR 0158);
  - `privateFilesUnavailable`: the private bucket fails (5xx), rate limits (429) or does not answer a file that the API sends for an AI. Any other refusal of the form is a failure. `Shared\UploadsSentByTheApi` throws it (ADR 0172).

  Any other exception is a failure (500). A missing token gets a 401 (`Security\ApiAuthenticationEntryPoint`). A missing role or right gets a 403 with the `reason` `roleNotAllowed`. Another account's resource gets a 404 (`Security\ApiAccessDeniedHandler`).
- CORS (`config/packages/nelmio_cors.yaml`) lists the allowed methods. A new verb must be added there, or the browser's preflight fails. It must also be added to the HTTP API's CORS in `docker/lambda/deploy.sh`, because MiniStack answers the Lambda mode's preflights from it. In dev only, both also expose `X-Debug-Token` and `X-Debug-Token-Link`, so that the front can read a call's profile. `tests/Local/ProfilerTest` checks that they agree. On AWS, the API's Terraform derives the allowed origin, `CORS_ALLOW_ORIGIN`, from the front's address (ADR 0066, 0067).

## The delivery and the wiring

The delivery layer (controllers, inputs, resources, presenters, readers, security) uses only the application's requests and responses. Only the ports' adapters use the domain (`tests/Architecture/DomainBehindTheApplicationTest`, ADR 0035). An API context uses nothing from another context but its Contract: the core's `<Context>/Contract`, with its published queries and integration events (`tests/Architecture/BoundedContextsTest`, ADR 0045, 0047). Security reads who is calling with the `ConsultMyAccount` query (identities and roles, ADR 0040). Repositories answer the domain's questions (`isEmailUsed()`) and decide nothing. The email reservation is written with the aggregate (below). It is the safety net of `EmailUniqueness` when two requests take the same email at once (ADR 0039, 0048). `tests/Architecture/BoundedContextsTest` fails when a context folder uses anything from another context except its Contract, anticorruption adapters included.

- A context's adapters are grouped by kind. Its repositories are in `src/<Context>/Persistence`. The anticorruption layers of its ports to other contexts are in `src/<Context>/AntiCorruption` (ADR 0086). A console command that uses the adapters of several contexts has its own folder, `src/<Purpose>` (`src/Keys`, `src/Consistency`). It never goes in `src/Shared`, which uses no context (`tests/Architecture/BoundedContextsTest`).
- Ports are aliased to adapters in `config/services.yaml`.
- Every core `*Handler` is a service, tagged by its interface. `Shared\DependencyInjection\RefusalTranslatingHandlersPass` decorates a command or a query handler with the decorators of its kind, from the innermost to the outermost (`COMMAND_DECORATORS`, `QUERY_DECORATORS`, ADR 0094, 0095):
  - handlers implementing `Prospecter\Core\SharedKernel\Application\Handler\CommandHandler` are registered on `command.bus` through `TransactionalCommandHandler`, then `RefusalTranslatingCommandHandler`;
  - handlers implementing `EventHandler` are the translators (`Application/Publish…/Publish…Handler`). They sit on `event.bus` as they are, and run within the command that publishes, inside its decorators (ADR 0203). `Shared\DependencyInjection\SubscriptionsPass` fails the build on an event handler of the core that does not translate;
  - a reaction is a policy, which a subscription of `config/subscriptions.json` names (`policy`, `Shared\Messaging\Subscriptions`, `contexts.md`, ADR 0203). The pass gives it a `Shared\Messaging\PolicyReaction`. It gives the event to the policy and sends each command the policy returns to `command.bus`, so through the command decorators. A failure comes back as the handler threw it, so that the worker sorts it: a conflict is applied again, not turned into a refusal as `Shared\CommandBus` does for a client. A policy is no service: `ReactionsAreSubscribedTest` fails on a policy that no subscription names. Each reaction is reached by its subscription's name and the event's detail-type (`Subscriptions::reactionKey()`);
  - commands and translators are routed by the type of the message that their handler takes;
  - the same pass wraps every `QueryHandler` in a `RefusalTranslatingQueryHandler` and aliases it by its intent's name. So a class that calls one asks for the core's interface, `QueryHandler $consultOrganization` (`ConsultOrganizationHandler`), never for the decorator (ADR 0035, 0044, 0094);
  - `RebuildFiguresHandler` alone is not a bus service. Its steps commit their own units (ADR 0103, 0104). `App\Reporting\RebuildOfTheFigures` calls it and starts it again after a conflict. It is wired twice: `app:reporting:rebuild` waits for the worker at each attempt, and the commands loading the demonstration (`app:demo:restore`, `app:tables:recreate --demo`) wait only on a retry, through `App\Demo\DemoFigures` (`app.reporting.rebuild_of_the_figures_alone`, ADR 0209);
  - the services that the contexts offer each other are aliased by their interface to the handler that implements it. They are requested by their type (`ConsultMembersService $consultMembersService`, ADR 0089).
- Nothing is built in a constructor, and no service is built by code: everything is injected (`make lint` checks it, ADR 0044). The hexagon's services are registered in `config/services.yaml`. `config/packages/messenger.yaml` declares no transport: messages are handled within the request.
- The `Events` port is `Shared\Messaging\RoutingEvents` (ADR 0097). A domain event goes to its context's translators on `event.bus` (synchronous, `allow_no_handlers`). Then, like an integration event, it is encoded (`contexts.md`) and handed to the `Shared\Messaging\Delivery` that `INTEGRATION_EVENTS_DELIVERY` names:
  - `sync`, `SynchronousDelivery`: it calls the reactions of the subscriptions that listen to the event's source and detail-type right away, internal ones included. It uses no inbox and promises no order (ADR 0097);
  - `outbox`, `OutboxDelivery`: it writes the event in the commit's outbox, which is relayed to the bus after the commit. So no reaction runs within the command (ADR 0098, `persistence.md`). The local stack and the instances use this delivery (ADR 0108).
- Reactions take a domain event of their own context or an integration event of another context (ADR 0042, 0046).
  - With the `sync` delivery (the tests, or an instance that names it), they join the unit of work of the command that published. A reaction that is refused or fails makes the command refused or failed, and the command writes nothing (`persistence.md`, ADR 0095).
  - With `outbox`, the worker runs each reaction in its own transaction (`contexts.md`, ADR 0101).
- A gesture runs one use case. The console commands that open an account or register a member dispatch `OpenAccount` or `RegisterAMember` alone, and the member completes their profile in the SPA (ADR 0100). The exception is an AI that records a contact and their organization in one call (`Identification\ContactRecorder`, ADR 0177). Everything is read first. Then each use case runs in its own unit of work, idempotent step by step. When a later step fails, the organization that the call created stops being prospected.
- `bin/console debug:messenger` (`make commands`) lists both buses.

## Checking the consistency

`bin/console app:consistency:check` checks the outcome of the asynchronous reactions rather than watching their failures (ADR 0105). `--account=<id>` checks one account. Run it with `make console CMD="app:consistency:check"`, adding `INSTANCE=` for an instance. It runs on demand and only reads:

- **the figures**: each account's five figures, against Reporting's active version. The figures are `activeOrganizations`, `locatedOrganizations`, `contacts`, `exchanges`, and each month's exchanges that took place, done or missed. The check counts them again from the Prospecting and PersonalData tables. It uses a `Query` counting on `GSI1`, and reads the items of the organizations placed on the map and of the exchanges, with their status and month;
- **the reactions between the contexts**:
  - each account has members;
  - each member has their profile in PersonalData;
  - each collected contact is attached to organizations of its account, and no forgotten contact still is;
  - no exchange is kept for an organization no longer prospected, or for none;
  - no tags are kept for an organization no longer prospected or for a forgotten contact (ADR 0138);
  - a frozen campaign keeps no recipient for a forgotten contact, while a started campaign keeps it as erased (ADR 0144);
- **the tags**: the facts that Campaigns keeps agree with the Prospecting and PersonalData tables (ADR 0141). For each prospected organization, these facts are the contacts it names and where it is (ADR 0142). For each contact, they are how it is reached, read from its contact details and from the LinkedIn profile among its links. No fact is kept for an organization no longer prospected or for a forgotten contact;
- **the lists**: each account has the key that lists it among the accounts (`GSI1`, `ACCOUNTS`). Each exchange has the key that lists it in its account (`GSI2`). `app:reporting:rebuild` counts them through these keys. An item written before these keys existed lacks them, and nothing counts it until `app:keys:rebuild` writes them. The check finds the accounts by their items (a `Scan` of `ACCOUNT#`), never through that list, so that it also checks an account missing from it. It finds the exchanges that lack their key by a `Scan` of `EXCHANGE#`. The figures and the reactions still read an account's exchanges through `GSI2`. When an item is missing from its list, run `app:keys:rebuild` first, then `app:reporting:rebuild`, then check again;
- **the calendars**: each exchange that is still planned and upcoming, held by a member connected to a provider, is synced to their calendar there. Its entry in the Connectors table is `synced` (ADR 0133);
- **the links**: no two contacts of an account record the same link, as their own or as a former one. Two contacts recorded before links were unique are shown by their ids, never by the link. The members then merge them or correct one of them by hand (`Consistency\ReferencesOfTheContacts`, ADR 0159, ADR 0165);
- **the failure queues**: the messages waiting, visible or not, in the worker's queue (`INTEGRATION_FAILURES_QUEUE`) and in the relay's (`RELAY_FAILURES_QUEUE`), whatever the account.

It lists each discrepancy with its account, what it concerns (a figure, an identity, a queue) and the command that corrects it. It fails when there is one:

- a figure: `app:reporting:rebuild`;
- a Campaigns fact missing or out of date: `app:tags:rebuild --account=<id>`. A fact left behind is a missed reaction;
- an item missing from its list: `app:keys:rebuild`;
- a missed reaction: `app:integration:redrive` when the worker's failures keep its event, otherwise `app:outbox:republish --since=<moment of the gesture>`;
- a relay failure: `app:outbox:republish`;
- an exchange missing from its calendar: its holder pushes it again once it failed, reconnects, or changes it;
- two contacts recording the same link: the members merge them or correct one of them.

It corrects nothing. It counts with direct requests on the tables, never through the services that the contexts publish or their retrievers, which would hide their own bugs. `Consistency\ContextTables` is the only code that reads the tables of several contexts, and it only reads them (`tests/Architecture/TablePerContextTest`).

## Hosts

- `APP_API_HOST` (`api.prospecter.localhost`) serves the routes in `src/{Me,Prospecting,PersonalData,SignIn,Dashboard,Contacts,Exchanges,ToDos,Connectors,Tags,Campaigning,Booking}/Controller` (`config/routes.yaml`). It also serves `src/Doc` (`/favicon.ico`, `config/routes/nelmio_api_doc.yaml`). `VersionedNelmioAssets` requests the Swagger UI files from the CDN at the installed NelmioApiDocBundle version. Locally, nginx passes `/cognito/` on the same host to MiniStack's Cognito, for the SPA.
- Outside `/api/` and its contract (ADR 0156, 0157, `mcp.md`):
  - the MCP server on `/mcp` (firewall `mcp`, `config/packages/mcp.yaml`, the route of type `mcp`). Its tools are in `src/<Context>/Mcp/<Intent>Tool.php`, one per intent. Each calls the core the way a controller does (`src/Me/Mcp/WhoAmITool`);
  - the protected resource metadata, `/.well-known/oauth-protected-resource/mcp` (`SignIn\Controller\ShowProtectedResourceController`, public);
  - to come, the facade that signs the AIs in on Cognito, `src/SignIn/OAuth` (`/.well-known/oauth-authorization-server`, `/oauth/authorize`, `/oauth/callback`, `/oauth/token`). There the protocol imposes two exceptions to the one-intent-per-endpoint rule. `/oauth/token` carries two intents through its `grant_type`, and `/oauth/authorize` and `/oauth/callback` only redirect.
- Files are not the API's business. The storage app (`apps/storage`, `static.prospecter.localhost`) keeps and serves them. `Shared\ImageResourceFactory` names their sizes by its convention (`<public url>/<size>/<path>`, ADR 0033).
