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

- One business intent per endpoint (ADR 0025), one invokable controller per endpoint, grouped by resource: `src/Controller/Api/<Resource>/<Intent>.php` (`Organization/Relocate`). Extend `AbstractController`, inject `UseCasesFacade` and the presenter by constructor.
- URLs are resource-oriented. `POST` creates a resource or adds an entity to an aggregate (`POST /api/organizations`, `POST .../logo`), `PATCH` changes one part of a resource exposed as a sub-resource (`PATCH /api/organizations/{id}/address`), `DELETE` removes one. No `PUT`. Ids are UUIDs: routes declare `requirements: ['id' => Requirement::UUID]`.
- Input: `#[MapRequestPayload]` / `#[MapQueryString]` with a `final readonly` DTO in `src/Api/Input` (constraints as attributes, `#[Assert\Valid]` on nested DTOs), shaped like the matching part of the resource (`AddressInput` like `AddressResource`) and converting itself to the core value object (`toAddress()`). Unknown fields are rejected with a 422 violation each (`serializer.default_context` in `config/packages/framework.yaml`), never ignored.
- Files: `#[MapUploadedFile]` then `new UploadedFileInfo($file)` (an `SplFileInfo` whose extension is guessed from the content), always on `POST` (PHP does not parse multipart bodies on `PATCH`).
- Output: every intent returns the fresh resource (`OrganizationResource`, `AccountResource`), a `final readonly` class of `src/Api/Resource` built by a presenter of `src/Presenter` implementing the presenter of the core (images via `Api\ImageResourceFactory`, which adds a thumbnail URL).
- Document every endpoint: `#[OA\Tag]`, `#[OA\Get|Post|Patch|Delete]`(summary), one `#[OA\Response]` per status with `new Model(type: ...)`. Then run `make api-doc` to regenerate `openapi.json` and the front types; `tests/Api/OpenApiContractTest` fails `make test` while the committed `openapi.json` differs from the code.
- Errors: refusals of the core (`UseCaseException` and subclasses, `BusinessRuleViolation` of an aggregate) become RFC 9457 `application/problem+json` in `EventListener\UseCaseExceptionListener` (422 with `violations`, 404, 403); missing tokens get the same format (`Security\ApiAuthenticationEntryPoint`).
- CORS (`config/packages/nelmio_cors.yaml`) lists the allowed methods: a new verb must be added there, or the browser's preflight fails.

## Authentication

See `docs/auth.md` at the repository root.

- `league/oauth2-server-bundle`: `/authorize` + `/token`, authorization code with PKCE for the public client `prospecter-front` (created by `DataFixtures\OAuthClientFixtures`), JWT access tokens 15 min, rotated refresh tokens 30 days. Keys in `config/jwt` (`make keys`, not committed).
- Firewalls (`config/packages/security.yaml`): `api` (`^/api/`, stateless, `oauth2: true`), `main` (session, `/login` with `Security\ProspecterAuthenticator`, which checks the password with `Auth\Passwords`), `/token` handled by the bundle. Consent is auto-approved (`EventListener\AuthorizationRequestListener`), logout redirects to `FRONT_URL` (`EventListener\LogoutListener`).
- The token subject is the user name: `Security\ProspecterUserProvider` loads the core user through `UserRepository::ofUserName()` and builds `App\Entity\User`, received as `#[CurrentUser] User $user` and passed to use cases as the `UseCaseConsumer`.
- Authentication is not a use case. `src/Auth` keeps the passwords apart from the core (`Credentials`, table `credentials`, one row per user UUID; `Passwords` hashes with the `auto` hasher and upgrades old hashes at sign-in) so that it can move to a dedicated authorization server. `PATCH /api/me/password` (`Controller/Api/Account/ChangeMyPassword`) sets a new password through it; `bin/console app:user:set-password <userName>` does it by hand.

## Architecture

| Port of the core | Adapter |
|---|---|
| `Domain\Organization\OrganizationRepository`, `Domain\User\UserRepository` | `Repository\DoctrineOrganizationRepository`, `Repository\DoctrineUserRepository` (`nextIdentity()` in `Uuid::v7()`, unique email among organizations and users through `Repository\UniqueEmails`) |
| `Application\Port\Storage` | `Gateway\LocalStorage` (files under `public/static/storage`) |
| `Application\Port\GeoLocation` | `Gateway\GeoplateformeGeoLocation` (French State geocoder, IGN Géoplateforme: no key, France only, `APP_GEOCODER_URL`; tests get no answer from the mocked `http_client`) |

Ports are aliased to adapters in `config/services.yaml`. Every class extending `Prospecter\Core\Application\UseCase` is registered automatically (lazy, tagged `use_case`) and added to `UseCasesFacade` by `DependencyInjection\UseCasePass`.

Doctrine mapping of the aggregates is in `config/doctrine/<Aggregate>/*.orm.xml` (tables `organizations` and `users`, `guid` ids assigned by the domain). Value objects are not embeddables (Doctrine cannot make them null): their fields are plain columns of the aggregate (`address_city`, `logo_url`...); references to other aggregates are id columns (`owner_id`, `holding_id`, `organization_id`). The `credentials` table belongs to `src/Auth` (attribute mapping), the OAuth tables to the bundle.

## Hosts

- API and authorization server: `APP_API_HOST` (`api.local.prospecter.io`), routes in `src/Controller/Api` and `src/Controller/Auth`.
- Static host: `APP_STATIC_HOST`, `src/Controller/Statics/StorageController` resizes images on demand: the URL `<file>&<encrypted operation>.<ext>` carries the operation (AES with `APP_SECRET`).
- `FRONT_URL` is the SPA origin (CORS, OAuth redirect URI, logout target).

## Tests

- `tests/Api`: functional tests through `Base/ApiTestCase`, whose `login()` runs the real PKCE flow and sends the bearer token. Schema and fixtures are recreated for each test; host `api.test.prospecter.io`; variables in `.env.test`.
- `tests/Base/TestCase` instantiates the class under test from the test class name and offers `prophesy()` (Prophecy).
- PHPUnit 13: attributes only (`#[DataProvider]`, `#[Group]`), static data providers, abstract base classes named `*TestCase`.
