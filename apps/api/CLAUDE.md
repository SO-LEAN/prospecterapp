# api (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ.

## Role

A JSON API that exposes the use cases of `packages/core`, plus the OAuth 2 authorization server used by the SPA (`apps/front`). No server-rendered pages except the sign-in page (`templates/auth/login.html.twig`). Full contract: `openapi.json` (generated, do not edit) and Swagger UI on `https://api.local.prospecter.io/api/doc`.

## Project overrides

- No migrations: the schema is created from the mapping (`make db`, tests recreate it). Do not add DoctrineMigrationsBundle unless asked.
- No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).
- No API Platform: plain controllers documented with NelmioApiDocBundle attributes.
- `Request::get()` no longer exists: use `$request->attributes`, `$request->query` or `$request->request`.

## Writing an endpoint

- One business intent per endpoint (ADR 0025), one invokable controller per endpoint, in the folder of its bounded context: `src/<Context>/Controller/<Intent>.php` (`Prospecting/Controller/Relocate`); `src/Dashboard` composes both contexts. Extend `AbstractController`. A change dispatches its command on `Shared\CommandBus` (synchronous, refusals of the core thrown back as is), then answers with the fresh resource read by a query (`Prospecting\OrganizationReader`, `Accounts\AccountReader`): commands return nothing (ADR 0029). A creation asks the repository for `nextIdentity()` and puts it in the command.
- URLs are resource-oriented. `POST` creates a resource or adds an entity to an aggregate (`POST /api/organizations`, `POST .../logo`), `PATCH` changes one part of a resource exposed as a sub-resource (`PATCH /api/organizations/{id}/address`), `DELETE` removes one. No `PUT`. Ids are UUIDs: routes declare `requirements: ['id' => Requirement::UUID]`.
- Input: `#[MapRequestPayload]` / `#[MapQueryString]` with a `final readonly` DTO in `src/Api/Input` (constraints as attributes, `#[Assert\Valid]` on nested DTOs), shaped like the matching part of the resource (`AddressInput` like `AddressResource`) and converting itself to the core value object (`toAddress()`). Unknown fields are rejected with a 422 violation each (`serializer.default_context` in `config/packages/framework.yaml`), never ignored.
- Files: `#[MapUploadedFile]` then `new UploadedFileInfo($file)` (an `SplFileInfo` whose extension is guessed from the content), always on `POST` (PHP does not parse multipart bodies on `PATCH`).
- Output: every intent returns the fresh resource (`OrganizationResource`, `AccountResource`), a `final readonly` class of `src/<Context>/Resource` built by a presenter of `src/<Context>/Presenter` from the response of a query (images via `Shared\ImageResourceFactory`, which adds a thumbnail URL). Inputs live in `src/<Context>/Input`; what both contexts use in `src/Shared/{Input,Resource}`.
- Document every endpoint: `#[OA\Tag]`, `#[OA\Get|Post|Patch|Delete]`(summary), one `#[OA\Response]` per status with `new Model(type: ...)`. Then run `make api-doc` to regenerate `openapi.json` and the front types; `tests/Api/OpenApiContractTest` fails `make test` while the committed `openapi.json` differs from the code.
- Roles: one `#[IsGranted('ROLE_PROSPECTOR')]` or `#[IsGranted('ROLE_USER')]` per controller (`ROLE_ADMIN` inherits both); the core does not check roles.
- Links: a resource carries `_links` (HAL, plus `method`), built by its presenter from the possible intents of the core response (`Shared\Links`): the key of a link is absent when the intention is not possible now (`SKIP_NULL_VALUES` on `_links`, optional in the contract), and the call then fails. Document a new intention as a new link.
- Errors: refusals of the core become RFC 9457 `application/problem+json` in `Shared\UseCaseExceptionListener`: `NotFound` 404, `NotPossibleInItsState` 409 (the link is missing), other `BusinessRuleViolation` 422 with `violations`; missing tokens 401 (`Security\ApiAuthenticationEntryPoint`), missing role 403 (`Security\ApiAccessDeniedHandler`).
- CORS (`config/packages/nelmio_cors.yaml`) lists the allowed methods: a new verb must be added there, or the browser's preflight fails.

## Authentication

See `docs/auth.md` at the repository root.

- `league/oauth2-server-bundle`: `/authorize` + `/token`, authorization code with PKCE for the public client `prospecter-front` (created by `DataFixtures\OAuthClientFixtures`), JWT access tokens 15 min, rotated refresh tokens 30 days. Keys in `config/jwt` (`make keys`, not committed).
- Firewalls (`config/packages/security.yaml`): `api` (`^/api/`, stateless, `oauth2: true`), `main` (session, `/login` with `Security\ProspecterAuthenticator`, which checks the password with `Auth\Passwords`), `/token` handled by the bundle. Consent is auto-approved (`EventListener\AuthorizationRequestListener`), logout redirects to `FRONT_URL` (`EventListener\LogoutListener`).
- The token subject is the user name: `Security\ProspecterUserProvider` loads the member through `MemberRepository::ofUserName()` and builds `App\Entity\User`, received as `#[CurrentUser] User $user`; its id goes into every command and query (`$user->memberId()`), and the aggregates decide ownership (ADR 0026).
- Authentication is not a use case. `src/Auth` keeps the passwords apart from the core (`Credentials`, table `credentials`, one row per user UUID; `Passwords` hashes with the `auto` hasher and upgrades old hashes at sign-in) so that it can move to a dedicated authorization server. `PATCH /api/me/password` (`Accounts/Controller/ChangeMyPassword`) sets a new password through it; `bin/console app:user:set-password <userName>` does it by hand.

## Architecture

| Port of the core | Adapter |
|---|---|
| `Accounts\Domain\AccountRepository`, `Accounts\Domain\MemberRepository` | `Accounts\Persistence\DoctrineAccountRepository`, `DoctrineMemberRepository` |
| `Prospecting\Domain\OrganizationRepository` | `Prospecting\Persistence\DoctrineOrganizationRepository` |
| `Prospecting\Domain\Prospectors` | `Prospecting\AccountsProspectors`: the anticorruption layer, the only class of a context folder that uses the other context |
| `Prospecting\Application\Port\GeoLocation` | `Prospecting\GeoplateformeGeoLocation` (French State geocoder, IGN Géoplateforme: no key, France only, `APP_GEOCODER_URL`; tests get no answer from the mocked `http_client`) |
| `SharedKernel\Application\Port\Storage` | `Shared\LocalStorage` (files under `public/static/storage`) |

Repositories give identities with `Uuid::v7()`; the unique email among accounts, members and organizations is checked by `Shared\Persistence\UniqueEmails` (a rule across contexts, tolerated until the personal data context). `tests/Architecture/BoundedContextsTest` fails when a context folder uses the other context.

Ports are aliased to adapters in `config/services.yaml`. Every `*Handler` of `{Accounts,Prospecting}/Application` is a service; those implementing `Prospecter\Core\SharedKernel\Application\CommandHandler` are tagged `messenger.message_handler` on `command.bus` (`config/packages/messenger.yaml`: no transport, handled in the request). `bin/console debug:messenger` lists the commands and their handlers.

Doctrine mapping of the aggregates is in `config/doctrine/<Context>/*.orm.xml` (`Accounts`, `Prospecting`, `SharedKernel`; tables `accounts`, `members`, `organizations`). The aggregates keep their value objects and the mapping adapts to them (ADR 0028): the value objects always present are embeddables without column prefix (`LegalIdentity`, `ContactDetails`, `PersonName`: columns `corporate_name`, `email`...), the others are custom types of `src/<Context>/Persistence` and `src/Shared/Persistence` registered in `doctrine.yaml` (identities as `guid`, `Email` as a string, `Address`, `GeoPoint`, `File` and the roles as JSON, since Doctrine cannot make an embeddable null). DQL reaches embedded fields by path (`o.contactDetails.email`) and passes identities with their type (`setParameter('owner', $id, 'account_id')`).

Doctrine refuses to initialize a lazy proxy of an aggregate: hydration would replace its readonly identity with an equal but new value object. The code never creates one (no association, no `getReference()`); tests load a fixture user again instead of using its reference (`ControllerTestCase::loadedUser()`). The `credentials` table belongs to `src/Auth` (attribute mapping), the OAuth tables to the bundle.

## Hosts

- API and authorization server: `APP_API_HOST` (`api.local.prospecter.io`), routes in `src/{Accounts,Prospecting}/Controller`, `src/Dashboard` and `src/Controller/Auth` (`config/routes.yaml`).
- Static host: `APP_STATIC_HOST`, `src/Controller/Statics/StorageController` resizes images on demand: the URL `<file>&<encrypted operation>.<ext>` carries the operation (AES with `APP_SECRET`).
- `FRONT_URL` is the SPA origin (CORS, OAuth redirect URI, logout target).

## Tests

- `tests/Api`: functional tests through `Base/ApiTestCase`, whose `login()` runs the real PKCE flow and sends the bearer token. Schema and fixtures are recreated for each test; host `api.test.prospecter.io`; variables in `.env.test`.
- `tests/Base/TestCase` instantiates the class under test from the test class name and offers `prophesy()` (Prophecy).
- PHPUnit 13: attributes only (`#[DataProvider]`, `#[Group]`), static data providers, abstract base classes named `*TestCase`.
