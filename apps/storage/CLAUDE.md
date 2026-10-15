# storage (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ. No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).

## Role

Keeps the files uploaded through the API (logos, pictures) and serves them, the images in named sizes made on first request (ADR 0033). No business rule, no database: the API keeps the URL of each file.

- Public host `static.local.prospecter.io` (`docker/nginx/templates/static.conf.template`): nginx serves `public/storage` as is; a size asked for the first time reaches PHP (`Controller\ShowImageInSize`), which writes it next to the originals.
- Internal server `http://nginx:8080`, on the Docker network only: the services of the platform store (`POST /files`, multipart field `file`) and remove (`DELETE /files/<path>`) files with the bearer token `STORAGE_TOKEN` (`InternalCaller`).

## Layout

- An original: `public/storage/<year>/<month>/<name>/<name>.<extension>`, URL `STORAGE_PUBLIC_URL/<same path>`.
- A size: `public/storage/<size>/<same path>`, from the closed list of `ImageSize` (`small` 96 px, `medium` 256 px wide), never enlarged (`Resizer`, Imagick). A new size is a new case of `ImageSize`, and its name in `App\Shared\ImageResourceFactory` of the API.
- `public/storage` is data, ignored by git.

## Commands

From the repository root: `make test-storage`; `make lint` and `make fix` cover it. One command: `docker compose run --rm --no-deps -w /var/www/src/apps/storage php <cmd>`.

## Tests

`tests/StorageTest`: the storage as the API and the browsers use it, files written under `var/test-storage` (removed after each test).
