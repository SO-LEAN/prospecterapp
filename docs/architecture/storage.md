# The files

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

`apps/storage` (ADR 0033, 0051, 0077).

The storage keeps the files uploaded through the API (logos, pictures) in an S3 bucket and serves them, with images in named sizes generated on first request (ADR 0033, 0051). It keeps the private files (the exchanges' attachments) in a bucket that nothing serves: browsers upload and read them with requests the storage signs (ADR 0077). No business rules, no database: the API stores each file's URL.

- Bucket `S3_BUCKET` (`App\Bucket`): MiniStack locally (`S3_ENDPOINT`, path-style, dev credentials in `.env`). Every object is put with its media type and `Cache-Control: public, max-age=31536000, immutable` (a key never changes content).
- Public host `static.prospecter.localhost` (`docker/nginx/templates/static.conf.template`): nginx proxies `/storage/` to the bucket. A size the bucket lacks (403, 404) reaches the app (`Controller\ShowImageInSizeController`), which generates it from the original and puts it in the bucket.
- Internal server `http://nginx:8080`, on the Docker network only: the platform's services store files (`POST /files`, multipart field `file`) and remove them (`DELETE /files/<path>`) with the token `STORAGE_TOKEN` in the `X-Storage-Token` header (`InternalCaller`).
- Private files (`PrivateFiles`, bucket `S3_PRIVATE_BUCKET`): the services
  - request an upload form (`POST /uploads`: a key `uploads/<name>`, 10 MB at most, valid 15 minutes);
  - read the size the bucket measured for it (`GET /uploads/<name>`);
  - keep it under `kept/<name>` (`POST /private-files/<name>`);
  - get a link that reads it for one minute under the attachment's name (`GET /private-files/<name>/download-link?name=`);
  - remove it (`DELETE /private-files/<name>`).

  `SignedRequests` signs for the browsers on `S3_BROWSER_ENDPOINT`. The API also sends these forms for the files the AIs attach and for the attachments of e-mails imported from Gmail (ADR 0172, 0182, at the API's `PRIVATE_UPLOADS_ENDPOINT`: MiniStack locally, the form's address on AWS). `S3_BROWSER_ENDPOINT` is the static host locally, whose nginx passes to MiniStack only the signed requests to that bucket, and S3 on AWS (`infra/private-bucket.tf`: an upload that is never kept is deleted after a day).

## Bucket layout

- An original: key `<year>/<month>/<name>/<name>.<extension>`, URL `STORAGE_PUBLIC_URL/<same key>`.
- A size: key `<size>/<same key>`, from the closed list in `ImageSize` (`small` 96 px, `medium` 256 px wide), never enlarged (`Resizer`, Imagick). A new size is a new case of `ImageSize`, plus its name in the API's `App\Shared\ImageResourceFactory`.
- `bin/console app:bucket:recreate` creates both buckets empty (`make db`).
