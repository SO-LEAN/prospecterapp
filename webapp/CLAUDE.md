# webapp (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ.

## Project overrides

- No migrations: the schema is created from the mapping (`make db`, tests recreate it). Do not add DoctrineMigrationsBundle unless asked.
- No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).
- Web controllers do NOT extend `AbstractController` and do not use the container. Their collaborators are injected by setters declared in `config/services.yaml` (`App\Controller\Web\` block) and used through `App\Traits\ControllerTrait` / `HelperTrait`. Only `SecurityController` extends `AbstractController`.
- The current user is received as `#[CurrentUser] User $user` (`App\Entity\User`, built from the `RefreshUser` use case response). It is passed to use cases as the `UseCaseConsumer`.
- `Request::get()` no longer exists: use `$request->attributes`, `$request->query` or `$request->request`.

## Architecture

The business logic lives in `packages/cleanprospecter` (entities, use cases, gateway interfaces). The webapp only holds adapters:

| Library port (`Solean\CleanProspecter\Gateway\...`) | Adapter (`src/Gateway/...`) |
|---|---|
| `Entity\UserGateway`, `Entity\OrganizationGateway` | `Entity\UserRepositoryAdapter`, `Entity\OrganizationRepositoryAdapter` (Doctrine) |
| `Entity\Transaction` | `Entity\TransactionEntityManagerAdapter` |
| `Storage` | `StorageLocalAdapter` (files under `public/static/storage`) |
| `UserNotifier` | `UserNotifierSymfonyAdapter` (flash messages) |
| `GeoLocation` | `GeoLocatorAdapter` → `Service\GoogleMapGeoLocator` |

Ports are aliased to adapters in `config/services.yaml`. Every `*Impl` use case of the library is registered automatically (lazy, tagged `use_case`) and added to `UseCasesFacade` by `DependencyInjection\UseCasePass`; call them as `$useCases->createOrganization($request, $presenter, $user)`.

Doctrine mapping of the library entities is in `config/doctrine/*.orm.xml` (XML because the entities are not ours and ORM 3 dropped YAML). `Person` is single table inheritance with an explicit discriminator map (`organization`, `prospect`, `user`); `roles` and `stats` are `json`.

## Form handling convention

Create/update pages are driven by the route name `<subject>_<action>` through `Service\RequestHandler` and the `form_cmd` tagged commands. For `organization_create`:

- use case `createOrganization`, request `UseCase\CreateOrganization\CreateOrganizationRequest` built from the form data by constructor parameter names
- uploaded files reach the use case as `Service\UploadedFileInfo` (an `SplFileInfo` whose extension is guessed from the content, since PHP temporary upload files have none)
- form `App\Form\UseCaseType\CreateOrganizationType`, presenter `App\Presenter\CreateOrganizationPresenterImpl`
- template `page/organization/add.html.twig`; on success redirect to `organization_view` if that route exists

For `*_update` the form is prefilled with the `get<Subject>` use case and `Get<Subject>ForUpdatePresenter`, and the template is `page/<subject-with-dashes>/update.html.twig` (`my_account_information_update` → `page/my-account-information/update.html.twig`). Adding a page means following these names exactly; nothing is wired by hand.

## Hosts and assets

- Two hosts: the app (`%env(APP_PROJECT)%.%env(APP_APPLICATION_HOST)%`, routes in `src/Controller/Web`) and the static host (`APP_STATIC_HOST`, `src/Controller/Statics`, no security). Route loading by host is in `config/routes.yaml`.
- Asset URLs point to the static host (`framework.assets.base_urls`); in dev AssetMapper serves them through Symfony.
- Images: `image_operation` Twig filter builds URLs like `<file>&<encrypted operation>.<ext>`; `StorageController` decodes the operation (AES with `APP_SECRET`), resizes with Imagick and stores the result next to the original.
- JS is ES modules (`assets/app.js`, `assets/js/`), vendors in `importmap.php`. SCSS entry `assets/styles/app.scss`; Bootstrap/Font Awesome load paths are in `config/packages/symfonycasts_sass.yaml`. `$fa-font-path` must point to the real vendor folder because AssetMapper resolves `url()` relative to the source file.

## Tests

- `tests/Base/TestCase` instantiates the class under test from the test class name and offers `prophesy()` (Prophecy via `phpspec/prophecy-phpunit`).
- Web tests extend `Base/ControllerTestCase`: schema and fixtures are recreated for each test, `login()` uses `loginUser()`, host `test.prospecter.io`. Test environment variables are in `.env.test`.
- PHPUnit 13: attributes only (`#[DataProvider]`, `#[Group]`), static data providers, abstract base classes named `*TestCase`.
