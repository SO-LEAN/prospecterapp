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

- One invokable controller per endpoint, grouped by resource: `src/Controller/Api/<Resource>/<Action>.php` (e.g. `Organization/UploadLogo`). Extend `AbstractController`, inject `UseCasesFacade` and the presenter by constructor.
- URLs are resource-oriented (`/api/organizations/{id}`), never named after use cases: the use cases will be renamed to business intents later and only the controllers should change.
- Input: `#[MapRequestPayload]` / `#[MapQueryString]` with a `final readonly` DTO in `src/Api/Input` (constraints as attributes, `#[Assert\Valid]` on nested DTOs). An input has the shape of its resource minus the read-only fields (`OrganizationInput.address` like `OrganizationResource.address`): a client can send back what it read. Unknown fields are rejected with a 422 violation each (`serializer.default_context` in `config/packages/framework.yaml`), never ignored: on a `PUT` an ignored field would silently clear the value.
- Files: `#[MapUploadedFile]` then `new UploadedFileInfo($file)` (an `SplFileInfo` whose extension is guessed from the content).
- Output: a `final readonly` resource in `src/Api/Resource`, built by a presenter in `src/Presenter` that implements the use case presenter interface (images via `Api\ImageResourceFactory`, which adds a thumbnail URL). Mutations return the fresh resource by calling the `get*` use case afterwards.
- `PUT` replaces the whole resource (the update use cases overwrite every field); files are separate `POST .../logo` multipart sub-resources that send back the current values with the file.
- Document every endpoint: `#[OA\Tag]`, `#[OA\Get|Post|...]`(summary), one `#[OA\Response]` per status with `new Model(type: ...)`. Then run `make api-doc` to regenerate `openapi.json` and the front types.
- Errors: business exceptions (`UseCaseException` and subclasses) become RFC 9457 `application/problem+json` in `EventListener\UseCaseExceptionListener` (422 with `violations`, 404, 403); missing tokens get the same format (`Security\ApiAuthenticationEntryPoint`).

## Authentication

See `docs/auth.md` at the repository root.

- `league/oauth2-server-bundle`: `/authorize` + `/token`, authorization code with PKCE for the public client `prospecter-front` (created by `DataFixtures\OAuthClientFixtures`), JWT access tokens 15 min, rotated refresh tokens 30 days. Keys in `config/jwt` (`make keys`, not committed).
- Firewalls (`config/packages/security.yaml`): `api` (`^/api/`, stateless, `oauth2: true`), `main` (session, `/login` with `Security\ProspecterAuthenticator`, which checks credentials with the `login` use case), `/token` handled by the bundle. Consent is auto-approved (`EventListener\AuthorizationRequestListener`), logout redirects to `FRONT_URL` (`EventListener\LogoutListener`).
- The token subject is the user name: `Security\ProspecterUserProvider` rebuilds `App\Entity\User` with the `refreshUser` use case, received as `#[CurrentUser] User $user` and passed to use cases as the `UseCaseConsumer`.

## Architecture

| Library port (`Prospecter\Core\Gateway\...`) | Adapter (`src/Gateway/...`) |
|---|---|
| `Entity\UserGateway`, `Entity\OrganizationGateway` | `Entity\UserRepositoryAdapter`, `Entity\OrganizationRepositoryAdapter` (Doctrine) |
| `Entity\Transaction` | `Entity\TransactionEntityManagerAdapter` |
| `Storage` | `StorageLocalAdapter` (files under `public/static/storage`) |
| `UserNotifier` | `UserNotifierSymfonyAdapter` (flash messages, unused by the API) |
| `GeoLocation` | `GeoLocatorAdapter` → `Service\GoogleMapGeoLocator` (`APP_GOOGLE_API_KEY` in `.env.local`) |

Ports are aliased to adapters in `config/services.yaml`. Every `*Impl` use case of the library is registered automatically (lazy, tagged `use_case`) and added to `UseCasesFacade` by `DependencyInjection\UseCasePass`.

Doctrine mapping of the library entities is in `config/doctrine/*.orm.xml`. `Person` is single table inheritance with an explicit discriminator map (`organization`, `prospect`, `user`); `roles` and `stats` are `json`. The OAuth tables come from the bundle mapping.

## Hosts

- API and authorization server: `APP_API_HOST` (`api.local.prospecter.io`), routes in `src/Controller/Api` and `src/Controller/Auth`.
- Static host: `APP_STATIC_HOST`, `src/Controller/Statics/StorageController` resizes images on demand: the URL `<file>&<encrypted operation>.<ext>` carries the operation (AES with `APP_SECRET`).
- `FRONT_URL` is the SPA origin (CORS, OAuth redirect URI, logout target).

## Tests

- `tests/Api`: functional tests through `Base/ApiTestCase`, whose `login()` runs the real PKCE flow and sends the bearer token. Schema and fixtures are recreated for each test; host `api.test.prospecter.io`; variables in `.env.test`.
- `tests/Base/TestCase` instantiates the class under test from the test class name and offers `prophesy()` (Prophecy).
- PHPUnit 13: attributes only (`#[DataProvider]`, `#[Group]`), static data providers, abstract base classes named `*TestCase`.
