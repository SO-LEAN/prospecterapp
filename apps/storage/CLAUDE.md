# storage (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ. No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).

## Role

Keeps the files uploaded through the API (logos, pictures) in an S3 bucket and serves them, the images in named sizes made on first request (ADR 0033, 0051); keeps the private files (the attachments of the exchanges) in a bucket nothing serves, sent and read by the browsers with requests it signs (ADR 0077). No business rule, no database: the API keeps the URL of each file.

- Bucket `S3_BUCKET` (`App\Bucket`): MiniStack locally (`S3_ENDPOINT`, by path, dev credentials in `.env`), every object put with its media type and `Cache-Control: public, max-age=31536000, immutable` (a key never changes content).
- Public host `static.local.prospecter.io` (`docker/nginx/templates/static.conf.template`): nginx proxies `/storage/` to the bucket; a size missing from it (403, 404) reaches the app (`Controller\ShowImageInSizeController`), which makes it from the original and puts it in the bucket.
- Internal server `http://nginx:8080`, on the Docker network only: the services of the platform store (`POST /files`, multipart field `file`) and remove (`DELETE /files/<path>`) files with the token `STORAGE_TOKEN` in the header `X-Storage-Token` (`InternalCaller`).
- Private files (`PrivateFiles`, bucket `S3_PRIVATE_BUCKET`): the services ask the form of an upload (`POST /uploads`: a key `uploads/<name>`, 10 MB at most, 15 minutes), what the bucket measured of it (`GET /uploads/<name>`), keep it under `kept/<name>` (`POST /private-files/<name>`), a link reading it for a minute under the name of the attachment (`GET /private-files/<name>/download-link?name=`) and remove it (`DELETE /private-files/<name>`); `SignedRequests` signs for the browsers on `S3_BROWSER_ENDPOINT` (the static host locally, whose nginx passes to MiniStack only the signed requests to that bucket; S3 on AWS, `infra/private-bucket.tf`: an upload never kept goes after a day).
- Both entries reach the app in its Lambda `prospecter-dev-storage` (ADR 0054, 0055: the mounted repository, the layers of PHP and imagick of Bref, `BREF_BINARY_RESPONSES` for the images), through `docker/nginx/storage-lambda.conf`.
- On AWS (`infra/`, ADR 0065): CloudFront plays the static host, serving the bucket and failing over to the function URL (origin path `/storage`, signed by its origin access control of type `lambda`) for a missing size; the internal server of the api is a function URL answering only signed requests (the api signs with its role, CloudFront with its origin access control, ADR 0068), the token and the addresses in SSM `/prospecter/<instance>/storage/*`. The function runs `build/storage.zip` (`make package-storage`, ADR 0063), read-only: `Kernel` extends `BrefKernel`. A removed file is invalidated in CloudFront (`PublicCache`, ADR 0070), the edge in front told to keep no copy.

## Layout

- An original: key `<year>/<month>/<name>/<name>.<extension>`, URL `STORAGE_PUBLIC_URL/<same key>`.
- A size: key `<size>/<same key>`, from the closed list of `ImageSize` (`small` 96 px, `medium` 256 px wide), never enlarged (`Resizer`, Imagick). A new size is a new case of `ImageSize`, and its name in `App\Shared\ImageResourceFactory` of the API.
- `bin/console app:bucket:recreate` creates both buckets empty (`make db`).

## Commands

From the repository root: `make test-storage` (starts MiniStack); `make lint` and `make fix` cover it. One command: `docker compose run --rm --no-deps -w /var/www/src/apps/storage php <cmd>`.

## Tests

`tests/StorageTest`: the storage as the API and the browsers use it, on the bucket `prospecter-test-files` of MiniStack, recreated before each test; `tests/PrivateFilesTest` the private files, on `prospecter-test-private-files`, the test sending and reading as a browser.
