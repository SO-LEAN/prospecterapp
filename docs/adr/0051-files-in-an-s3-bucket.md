# 0051. The files are in an S3 bucket, served from it; PHP only makes the missing sizes

- Date: 2026-10-16
- Status: Accepted
- Supersedes: the disk of the storage app ([0033](0033-storage-app.md): `public/storage` served by nginx)
- Implemented by: `apps/storage/src/{Bucket,StoredFile,Files,Resizer}.php`, `apps/storage/src/Command/RecreateBucketCommand.php`, `docker/nginx/templates/static.conf.template`, the `ministack` service of `docker-compose.yml`

## Context

The application is to run serverless on AWS (0048): a Lambda has no disk to keep files on, the files go to S3. The storage app wrote the originals and their sizes in its `public/storage` folder, served by nginx, a size missing reaching PHP which wrote it there. The user wants to keep the sizes made on first request (a new format is only a new name), with a cache.

## Decision

- The storage app keeps the files in one S3 bucket (`S3_BUCKET`, `prospecter-dev-files`; `prospecter-test-files` in the tests) through the official AWS SDK, as the API does for DynamoDB. The keys are the former paths: an original `<year>/<month>/<name>/<name>.<extension>`, a size `<size>/<same key>`.
- Every object is put with its media type and `Cache-Control: public, max-age=31536000, immutable`: a key never changes content, a new file gets a new name.
- The public host serves the files from the bucket; a size missing from it reaches the storage app, which reads the original, makes the size once, puts it in the bucket and returns it. Locally nginx plays this part: it proxies `/storage/` to the bucket in MiniStack (by path, resolved on each request) and sends a 403 or 404 to PHP.
- The internal contract `POST /files` and `DELETE /files/<key>` does not change: the API sees nothing of it.
- Locally MiniStack emulates S3, its objects kept in its volume (`S3_PERSIST`, `S3_DATA_DIR`); `app:bucket:recreate` creates the bucket empty (`make db`).

## Consequences

- The tests of the storage need MiniStack running (`make test-storage` starts it) and recreate the test bucket before each test.
- The files of `apps/storage/public/storage` are no longer served; they were attached to the data of the API replaced by 0048 anyway.
- The bucket is public for reading: what is uploaded must be meant to be shown, as before.

## Alternatives considered

- The storage app serving every file, the bucket private: the same in dev and in production, but every image through PHP; the cache of Cloudflare would hide it in production, not locally.

## Revisit when

- Terraform defines production: Cloudflare in front of the bucket, a missing size sent to the storage app on its Lambda, the part nginx plays locally.
