# 0172. An AI joins a file by its address on the web, which the API downloads and sends with the form of an upload

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Exchanges/Mcp/AttachFileTool.php`, `apps/api/src/Shared/Mcp/FilesOnTheWeb.php`, `apps/api/src/Shared/UploadsSentByTheApi.php`, `PRIVATE_UPLOADS_ENDPOINT` in `apps/api/.env` and `apps/api/infra/lambda.tf`

## Context

Every endpoint of a member has its tool (0163), joining a file to an exchange among them. On the screen, the browser asks the form of an upload (`POST /api/uploads`), sends the file to the private bucket with it, itself (0077), then joins the upload (`POST /api/exchanges/{id}/attachments`): the API never holds the file nor touches the bucket. A tool of the Model Context Protocol takes JSON arguments only, and an image is given by its address on the web, which the API downloads (0168). The file downloaded has still to reach the bucket as an upload: the storage receives none from a service, and a request to the storage passes its HTTP API and its Lambda, whose payload is bounded to 6 MB, the base64 of a file of 10 MB beyond it.

## Decision

- The tool `joindre-piece-jointe` takes the address of the file on the web and its name, downloads it as an image is (`FilesOnTheWeb`: never on a private network, 5 seconds, no redirect followed), no heavier than the form allows (10 MB), once the member checked.
- The API then sends the file with the form of an upload the storage signs (`prepareUpload()`), as the browser does, to S3 itself, and joins the upload with the command of the endpoint: the core stays `uploaded` then `keep`, the storage does not change.
- The address the API reaches the bucket at is a configuration of its own, `PRIVATE_UPLOADS_ENDPOINT`, put in place of the origin of the form (the signature of a form covers its policy, not its host): MiniStack locally (`http://ministack:4566`), empty on AWS, the form sent to its own address, S3.
- A file behind a sign-in (an e-mail of Gmail, a private Drive) is not covered: the refusal of the tool says so to the AI, a violation of `url`.
- `api_uploads_prepare` has no tool (`NEVER`): the tool joining a file prepares its own upload, and the form, an address to send to, serves no AI.
- The tool says `openWorldHint`: it reaches outside Prospecter.

## Consequences

- An AI joins a file as heavy as the screen does, which the web serves to anyone: a document of the site of a prospect, a file it published.
- The API sends files to the private bucket, with the rights of the form only (one key, 10 MB, 15 minutes): it holds no right on the bucket.
- One more value for the API: `PRIVATE_UPLOADS_ENDPOINT`, set locally, empty on AWS (`apps/api/infra/lambda.tf`); an installation whose API cannot reach S3 at the address of the form sets it.
- A form prepared for a download that fails is never used: the upload never kept goes after a day, as one a browser left.

## Alternatives considered

- A route of the storage receiving the content of an upload from the API, weighed by the maintainer: the storage changed, and its Lambda bounding a file to some 4 MB, under the 10 MB of the screen.
- No tool joining a file, the gesture kept on the screen: against the decision of the ticket, which opens the attachments to the AIs.

## Revisit when

- A client of the protocol sends files the server can read, or the AIs need to join the files they reach behind a sign-in.
