# The files

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

The storage app lives in `apps/storage` (ADR 0033, 0051, 0077).

The storage keeps the files uploaded through the API (logos, pictures) in an S3 bucket and serves them. It generates images in named sizes on first request (ADR 0033, 0051). It keeps the private files (the exchanges' attachments) in a bucket that nothing serves. Browsers upload and read them with requests that the storage signs (ADR 0077). The storage has no business rules and no database: the API stores each file's URL.

- Bucket `S3_BUCKET` (`App\Bucket`): locally, it lives in MiniStack (`S3_ENDPOINT`, path-style, dev credentials in `.env`). Every object is put with its media type and `Cache-Control: public, max-age=31536000, immutable` (a key never changes content).
- Public host `static.prospecter.localhost` (`docker/nginx/templates/static.conf.template`): nginx proxies `/storage/` to the bucket. A request for a size that the bucket lacks (403, 404) reaches the app (`Controller\ShowImageInSizeController`). The app generates the size from the original and puts it in the bucket.
- Internal server `http://nginx:8080`, on the Docker network only. The platform's services store files there (`POST /files`, multipart field `file`) and remove them (`DELETE /files/<path>`). They send the token `STORAGE_TOKEN` in the `X-Storage-Token` header (`InternalCaller`).
- Private files (`PrivateFiles`, bucket `S3_PRIVATE_BUCKET`): the services
  - request an upload form (`POST /uploads`: a key `uploads/<name>`, 10 MB at most, valid 15 minutes);
  - read the size the bucket measured for it (`GET /uploads/<name>`);
  - keep it under `kept/<name>` (`POST /private-files/<name>`);
  - get a link that reads it for one minute under the attachment's name (`GET /private-files/<name>/download-link?name=`);
  - remove it (`DELETE /private-files/<name>`).

  `SignedRequests` signs for the browsers on `S3_BROWSER_ENDPOINT`. The API also sends these forms for the files that the AIs attach and for the attachments of e-mails imported from Gmail (ADR 0172, 0182). It sends them to the API's `PRIVATE_UPLOADS_ENDPOINT`: MiniStack locally, the form's address on AWS. Locally, `S3_BROWSER_ENDPOINT` is the static host. Its nginx passes to MiniStack only the signed requests to that bucket. On AWS, `S3_BROWSER_ENDPOINT` is S3 (`infra/private-bucket.tf`), where an upload that is never kept is deleted after a day.

## Bucket layout

- An original: key `<year>/<month>/<name>/<name>.<extension>`, URL `STORAGE_PUBLIC_URL/<same key>`.
- A size: key `<size>/<same key>`, from the closed list in `ImageSize` (`small` 96 px, `medium` 256 px wide), never enlarged (`Resizer`, Imagick). A new size is a new case of `ImageSize`, plus its name in the API's `App\Shared\ImageResourceFactory`.
- `bin/console app:bucket:recreate` creates both buckets empty (`make db`).
