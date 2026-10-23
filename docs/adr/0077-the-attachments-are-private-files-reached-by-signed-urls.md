# 0077. The attachments of an exchange are private files, sent and read by short-lived signed URLs

- Date: 2026-10-22
- Status: Accepted, the attachments of a forgotten contact superseded by [0079](0079-the-exchanges-of-a-forgotten-contact-stay-to-make-conform.md)
- Supersedes: for the files joined to an exchange, the single place of the personal data of [0031](0031-personal-data-context.md)
- Implemented by: `packages/core/src/Prospecting/Domain/{Exchange,Attachment}.php`, `packages/core/src/Prospecting/Application/{AttachFile,RemoveAttachment,DownloadAttachment,AttachedFiles}`, `packages/core/src/SharedKernel/Application/Port/PrivateStorage.php`, `apps/storage/src/{PrivateFiles,SignedRequests}.php`, `apps/storage/src/Controller`, `apps/storage/infra/private-bucket.tf`, `apps/api/src/Shared/{PrivateStorageClient,StorageEntry}.php`, `apps/api/src/Exchanges`, `docker/nginx/templates/static.conf.template`

## Context

A prospector joins to an exchange the files a prospect sent: a CV, a signed order, a specification, often personal data. The files of the storage are public: anybody knowing the address of a logo or of a picture reads it, from the bucket through CloudFront on AWS, through nginx locally (0051, 0065). An attachment must be read only by a prospector of its account with the right to manage personal data, and is up to 10 MB, more than the 6 MB a request or a response of a Lambda carries (0065, 0068).

## Decision

- The storage keeps the attachments in a bucket of their own, private: no CloudFront in front of it, no address serving it without a signature. Locally, nginx passes to MiniStack only the requests to this bucket carrying a signature.
- A file goes from the browser to the bucket directly: the API asks the storage for an upload (`POST /api/uploads`), the storage answers a form signed for 15 minutes, for one key under `uploads/` and at most 10 MB; the browser sends it to S3. An upload never kept is deleted by the bucket after a day.
- Attaching it (`AttachFile`), the core asks the port `PrivateStorage` the size and the media type of the upload, the exchange decides (10 MB, 10 files at most), then the storage keeps it under `kept/`: an upload is attached once.
- Reading it (`DownloadAttachment`), the core checks the right, then the storage signs a download of the kept file for one minute, named after the attachment (`Content-Disposition: attachment`). The SPA links to the API (`GET /api/exchanges/{id}/attachments/{attachmentId}`), which needs the token, and opens the signed URL it answers.
- An attachment removed, an exchange erased, an exchange no longer kept for any organization remove their files from the bucket, which keeps no former version of them.

## Consequences

- Prospecting keeps the name and the file of attachments that may hold personal data, as it keeps the text of the exchanges (0074): a forgotten contact leaves them while an organization of the exchange is prospected.
- The bucket allows the origin of the SPA in its CORS, for the uploads.
- A signed URL is a bearer link during its minute: whoever gets it reads the file. The address the SPA shows is the API's, which a signed-out browser does not read.
- Locally, MiniStack does not check the signature of an upload form; it checks those of the downloads.
- The size of an attachment is checked twice: by the signed form, for the transport, and by the exchange, for the business.

## Alternatives considered

- A prefix of the bucket of the files, kept out of CloudFront by its policy: one bucket, but its readers are granted the whole of it, the function serving the missing sizes included; a mistake in a policy would publish personal data.
- The API reading the file and sending it back itself: no signed URL, but limited to the 6 MB of a Lambda response.

## Revisit when

- The files outgrow 10 MB (a multipart upload), or a preview in the application needs to read them inline.
