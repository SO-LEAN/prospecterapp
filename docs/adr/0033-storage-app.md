# 0033. A storage app keeps the files and serves the images in named sizes

- Date: 2026-10-15
- Status: Accepted, the disk superseded by [0051](0051-files-in-an-s3-bucket.md), the bearer token by [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md)
- Implemented by: `apps/storage`, `apps/api/src/Shared/{StorageClient,ImageResourceFactory}.php`, `docker/nginx/templates/static.conf.template`

## Context

The API wrote the uploaded files on its own disk, and served and resized them on a static host of the same Symfony app: Imagick, an image library, an encryption of the resize operation in the URL (AES with `APP_SECRET`) and a controller that had nothing to do with the business. The user found that the API did too many things that were not its own, and asked for the storage to become an app beside it, Symfony like the others.

## Decision

- `apps/storage`, a Symfony 8.1 app without database, keeps the files: `POST /files` and `DELETE /files/<path>` on an internal server (`http://nginx:8080`, Docker network only), restricted to the services of the platform by a bearer token (`STORAGE_TOKEN`); the files are public on `static.local.prospecter.io`, served by nginx.
- The API reaches it through its port `Storage`, implemented by `StorageClient` (HTTP); its tests use an in-memory double.
- Images are served in a closed list of named sizes, `<public url>/<size>/<path>` (`small` 96 px, `medium` 256 px wide), made on first request and never enlarged. A closed list keeps what the encrypted operation protected (nobody makes the storage compute sizes of their own) without a secret shared to sign URLs; the API names the sizes by this convention.

## Consequences

- The API loses Imagick, the image services, the static host and its storage folder; it only knows the URLs.
- Uploading a file is a network call from the API to the storage: a storage down makes uploads fail with an error of the API.
- A new size means a new case of `ImageSize` in the storage and its name in `ImageResourceFactory` of the API.
- Files and their sizes are data of the storage app (`apps/storage/public/storage`), backed up apart from the database.

## Alternatives considered

- A lighter stack for the storage than Symfony: offered, the user chose Symfony for every app.

## Revisit when

The files move to an object storage (S3 compatible) or a CDN: the storage app then signs the uploads or proxies them, and the sizes become a feature of the CDN.
