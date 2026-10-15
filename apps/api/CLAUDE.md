# api (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ.

## Role

A JSON API that exposes the use cases of `packages/core`, a resource server for the SPA (`apps/front`): the tokens come from `apps/auth`. No server-rendered page. Full contract: `openapi.json` (generated, do not edit) and Swagger UI on `https://api.local.prospecter.io/api/doc`.

## Project overrides

- No migrations: the schema is created from the mapping (`make db`, tests recreate it). Do not add DoctrineMigrationsBundle unless asked.
- No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).
- No API Platform: plain controllers documented with NelmioApiDocBundle attributes.
- `Request::get()` no longer exists: use `$request->attributes`, `$request->query` or `$request->request`.

## Writing an endpoint

- One business intent per endpoint (ADR 0025), one invokable controller per endpoint, in the folder of its bounded context: `src/<Context>/Controller/<Intent>.php` (`Prospecting/Controller/Relocate`); `src/Me` (the `/api/me` endpoints: membership, profile), `src/Dashboard` and `src/OrganizationContacts` (`GET /api/organizations/{id}/contacts`: the contact ids of Prospecting read in a batch from PersonalData, an erased person left out) compose contexts. `src/PersonalData/Controller` serves `/api/contacts` (ADR 0041). Extend `AbstractController`. A change dispatches its command on `Shared\CommandBus` (synchronous, refusals of the core thrown back as is), then answers with the fresh resource read by a query (`Prospecting\OrganizationReader`, `Me\MyAccountReader`, which composes Accounts and PersonalData): commands return nothing (ADR 0029). A command is flat (ADR 0035): the controller passes the fields of the input, the id of the new resource from `Shared\Identities` (UUID v7) and `executedAt` from the Symfony clock (ADR 0036).
- URLs are resource-oriented. `POST` creates a resource or adds an entity to an aggregate (`POST /api/organizations`, `POST .../logo`), `PATCH` changes one part of a resource exposed as a sub-resource (`PATCH /api/organizations/{id}/address`), `DELETE` removes one. No `PUT`. Ids are UUIDs: routes declare `requirements: ['id' => Requirement::UUID]`.
- Input: `#[MapRequestPayload]` / `#[MapQueryString]` with a `final readonly` DTO in `src/<Context>/Input` (constraints as attributes, `#[Assert\Valid]` on nested DTOs), shaped like the matching part of the resource (`AddressInput` like `AddressResource`); the controller passes its fields to the flat command (ADR 0035). Unknown fields are rejected with a 422 violation each (`serializer.default_context` in `config/packages/framework.yaml`), never ignored.
- Files: `#[MapUploadedFile]` then `new UploadedFileInfo($file)` (an `SplFileInfo` whose extension is guessed from the content), always on `POST` (PHP does not parse multipart bodies on `PATCH`).
- Output: every intent returns the fresh resource (`OrganizationResource`, `AccountResource`), a `final readonly` class of `src/<Context>/Resource` built by a presenter of `src/<Context>/Presenter` (`src/Me/AccountResourcePresenter` for my account; the dashboard reuses `OrganizationPagePresenter`) from the response of a query (images via `Shared\ImageResourceFactory`: `url`, `smallUrl` 96 px and `mediumUrl` 256 px wide, made on first request by the storage app, never enlarged; the front picks the size it displays). Inputs live in `src/<Context>/Input`; what both contexts use in `src/Shared/{Input,Resource}`.
- Document every endpoint: `#[OA\Tag]`, `#[OA\Get|Post|Patch|Delete]`(summary), one `#[OA\Response]` per status with `new Model(type: ...)`. Then run `make api-doc` to regenerate `openapi.json` and the front types; `tests/Api/OpenApiContractTest` fails `make test` while the committed `openapi.json` differs from the code.
- Roles: one `#[IsGranted('ROLE_PROSPECTOR')]` or `#[IsGranted('ROLE_USER')]` per controller (`ROLE_ADMIN` inherits both); the core does not check roles.
- Links: a resource carries `_links` (HAL, plus `method`), built by its presenter from the possible intents of the core response (`Shared\Links`): the key of a link is absent when the intention is not possible now (`SKIP_NULL_VALUES` on `_links`, optional in the contract), and the call then fails. Document a new intention as a new link.
- Errors: a use case refuses with `UseCaseRefused`, which `Shared\UseCaseExceptionListener` turns into RFC 9457 `application/problem+json` by its `ErrorType`: `NotFound` 404, `NotPossibleInItsState` 409 (the link is missing), `NotAllowed` 403 (no right: the core refuses the profile of a colleague, no endpoint reaches it yet), `BusinessRuleViolated` 422 with `violations`; any other exception is a failure (500); missing tokens 401 (`Security\ApiAuthenticationEntryPoint`), missing role 403 (`Security\ApiAccessDeniedHandler`).
- CORS (`config/packages/nelmio_cors.yaml`) lists the allowed methods: a new verb must be added there, or the browser's preflight fails.

## Authentication

See `docs/auth.md` at the repository root. The API is a resource server only: signing in, the OAuth 2 tokens and the credentials belong to `apps/auth` (ADR 0034).

- Firewall `api` (`^/api/`, stateless, `access_token` with `Security\AccessTokenHandler`): the JWT is checked with the public key of the authorization server (`AUTH_PUBLIC_KEY`), its expiry and its client (`AUTH_CLIENT_ID`); its subject is the member id, loaded by `Security\MemberProvider` into `App\Security\User`, received as `#[CurrentUser] User $user`; its ids go into every command and query, and the aggregates decide ownership (ADR 0026).
- The prospector of the fixtures has the member id `AppFixtures::PROSPECTOR`, the one the auth fixtures give its credentials.

## Architecture

| Port of the core | Adapter |
|---|---|
| `Accounts\Domain\AccountRepository`, `Accounts\Domain\MemberRepository` | `Accounts\Persistence\DoctrineAccountRepository`, `DoctrineMemberRepository` |
| `Prospecting\Domain\OrganizationRepository` | `Prospecting\Persistence\DoctrineOrganizationRepository` |
| `PersonalData\Domain\PersonRepository` | `PersonalData\Persistence\DoctrinePersonRepository` |
| `PersonalData\Domain\Readers` | `PersonalData\AccountsReaders`: anticorruption layer from the members of Accounts |
| `Prospecting\Domain\Prospectors` | `Prospecting\AccountsProspectors`: anticorruption layer from the members of Accounts (with their right to manage personal data) |
| `Prospecting\Domain\KnownContacts` | `Prospecting\PersonalDataKnownContacts`: anticorruption layer from the persons of PersonalData |
| `Prospecting\Application\Port\GeoLocation` | `Prospecting\GeoplateformeGeoLocation` (French State geocoder, IGN Géoplateforme: no key, France only, `APP_GEOCODER_URL`; tests get no answer from the mocked `http_client`) |
| `SharedKernel\Application\Port\Storage` | `Shared\StorageClient`: sends the files to the storage app on its internal server (`STORAGE_INTERNAL_URL`, token `STORAGE_TOKEN`) and keeps their public URL; `Tests\App\Double\InMemoryStorage` in the tests |

The delivery (controllers, inputs, resources, presenters, readers, security) uses the requests and responses of the application only; only the adapters of the ports use the domain (`tests/Architecture/DomainBehindTheApplicationTest`, ADR 0035). Security reads who calls with the query `ConsultMyAccount` (identities and roles, ADR 0040). Repositories answer the questions of the domain (`isEmailUsed()`) and decide nothing; a unique index (`account_id, purpose, email` for the persons, `account_id, email` for the organizations) is the safety net of `EmailUniqueness` when two requests take the same email at once, its violation translated into `EmailAlreadyUsed` by `save()` (ADR 0039). `tests/Architecture/BoundedContextsTest` fails when a context folder uses another context, the anticorruption adapters aside.

Ports are aliased to adapters in `config/services.yaml`. Every `*Handler` of the core is a service; those implementing `Prospecter\Core\SharedKernel\Application\CommandHandler` are registered on `command.bus` through `RefusalTranslatingHandler` by `Shared\DependencyInjection\RefusalTranslatingCommandHandlersPass`, and a class calling a query handler wraps it in `RefusalTranslatingHandler` in its constructor (ADR 0035) (`config/packages/messenger.yaml`: no transport, handled in the request). `bin/console debug:messenger` lists the commands and their handlers.

Each bounded context has its own database (ADR 0038): a connection and an entity manager per context (`accounts`, `personal_data`, `prospecting`, SQLite files `var/<context>_<env>.db`, URLs `<CONTEXT>_DATABASE_URL`), injected by name in its repositories only (`EntityManagerInterface $prospectingEntityManager`); `tests/Architecture/DatabasePerContextTest` fails otherwise. Nothing joins or spans two contexts. `make db` and `ApiTestCase` recreate every database. Doctrine mapping of the aggregates is in `config/doctrine/<Context>/*.orm.xml`, each read by the entity manager of its context, with the embeddables of `SharedKernel` (tables `accounts` and `members` in Accounts, `persons` in PersonalData, `organizations` in Prospecting). The aggregates keep their value objects and the mapping adapts to them (ADR 0028): the value objects always present are embeddables without column prefix (`LegalIdentity`, `ContactDetails`, `PersonName`: columns `corporate_name`, `email`...), the others are custom types of `src/<Context>/Persistence` and `src/Shared/Persistence` registered in `doctrine.yaml` (identities as `guid`, `Email` as a string, `Address`, `GeoPoint`, `File` and the roles as JSON, since Doctrine cannot make an embeddable null; `moment` for `createdAt` and `modifiedAt`, written and read in UTC since SQLite keeps no time zone). DQL reaches embedded fields by path (`o.contactDetails.email`) and passes identities with their type (`setParameter('account', $id, 'account_id')`).

Doctrine refuses to initialize a lazy proxy of an aggregate: hydration would replace its readonly identity with an equal but new value object. The code never creates one (no association, no `getReference()`); `ApiTestCase` empties the entity managers after loading the fixtures and after reading a reference (`login()`, `accountOfTheFixtures()`), so that the requests load the aggregates again.

## Hosts

- `APP_API_HOST` (`api.local.prospecter.io`), routes in `src/{Me,Prospecting}/Controller` and `src/Dashboard` (`config/routes.yaml`); nginx gives the sign-in paths of the same host to `apps/auth`.
- Files are not the API's business: the storage app (`apps/storage`, `static.local.prospecter.io`) keeps and serves them, and `Shared\ImageResourceFactory` names their sizes by its convention (`<public url>/<size>/<path>`, ADR 0033).

## Tests

- `tests/Api`: functional tests through `Base/ApiTestCase`, whose `login()` signs an access token for a fixture member with the test key pair (`tests/bootstrap.php`, in `var/test-jwt`, never committed). Schema and fixtures are recreated for each test; host `api.test.prospecter.io`; variables in `.env.test`.
- `tests/<Context>`: the adapters on their real database or service (`DoctrinePersonRepositoryTest`: the unique index refuses an email taken meanwhile; `GeoplateformeGeoLocationTest`); `tests/Architecture`: the boundaries (contexts, domain behind the application, one database per context).
- Unit tests extend PHPUnit's `TestCase` and use hand-written doubles (`tests/Double`), no mock library.
- PHPUnit 13: attributes only (`#[DataProvider]`, `#[Group]`), static data providers, abstract base classes named `*TestCase`.
