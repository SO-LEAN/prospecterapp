# storage (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ. No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).

## Role

Keeps the files uploaded through the API (logos, pictures) in an S3 bucket and serves them, the images in named sizes made on first request (ADR 0033, 0051). No business rule, no database: the API keeps the URL of each file.

- Bucket `S3_BUCKET` (`App\Bucket`): MiniStack locally (`S3_ENDPOINT`, by path, dev credentials in `.env`), every object put with its media type and `Cache-Control: public, max-age=31536000, immutable` (a key never changes content).
- Public host `static.local.prospecter.io` (`docker/nginx/templates/static.conf.template`): nginx proxies `/storage/` to the bucket; a size missing from it (403, 404) reaches PHP (`Controller\ShowImageInSize`), which makes it from the original and puts it in the bucket.
- Internal server `http://nginx:8080`, on the Docker network only: the services of the platform store (`POST /files`, multipart field `file`) and remove (`DELETE /files/<path>`) files with the bearer token `STORAGE_TOKEN` (`InternalCaller`).
- Both entries reach the app on PHP-FPM, or with `make lambda` in its Lambda `prospecter-dev-storage` (ADR 0054: the mounted repository, the layers of PHP and imagick of Bref, `BREF_BINARY_RESPONSES` for the images), through `docker/nginx/runtime/<runtime>/storage.conf`.

## Layout

- An original: key `<year>/<month>/<name>/<name>.<extension>`, URL `STORAGE_PUBLIC_URL/<same key>`.
- A size: key `<size>/<same key>`, from the closed list of `ImageSize` (`small` 96 px, `medium` 256 px wide), never enlarged (`Resizer`, Imagick). A new size is a new case of `ImageSize`, and its name in `App\Shared\ImageResourceFactory` of the API.
- `bin/console app:bucket:recreate` creates the bucket empty (`make db`).

## Commands

From the repository root: `make test-storage` (starts MiniStack); `make lint` and `make fix` cover it. One command: `docker compose run --rm --no-deps -w /var/www/src/apps/storage php <cmd>`.

## Tests

`tests/StorageTest`: the storage as the API and the browsers use it, on the bucket `prospecter-test-files` of MiniStack, recreated before each test.
