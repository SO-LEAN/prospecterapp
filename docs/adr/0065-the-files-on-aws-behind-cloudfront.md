# 0065. On AWS, CloudFront serves the files from the bucket and fails over to the storage for a missing size

- Date: 2026-10-17
- Status: Accepted, the HTTP API of the storage superseded by [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md), the order of the applies by [0069](0069-a-new-instance-converges-in-two-applies.md)
- Implemented by: `apps/storage/infra/{bucket,cloudfront,lambda,http-api,parameters}.tf`, `apps/storage/src/Kernel.php`, `apps/storage/config/services.yaml`, `tools/package/lambda`, the `package-storage` target of the `Makefile`

## Context

Locally, nginx serves the files from the bucket and sends a size missing from it to the storage in its Lambda, which makes it once (0051, 0054, 0055); the api stores and removes the files on the internal server with a token. On AWS, nothing plays nginx yet, and the domain of the platform is not there.

## Decision

- A private bucket per instance, read by CloudFront only (origin access control), versioned, a former version kept 30 days.
- A CloudFront distribution on its AWS address: an origin group answers from the bucket, and on a 403 or a 404 fails over to the HTTP API of the storage, the path prefixed with `/storage`.
- The storage runs packaged in its Lambda (0063), with the layers of PHP and imagick of Bref; its HTTP API is also the internal entry of the api, which gets the token and the addresses from SSM (`/prospecter/<instance>/storage/{token,public-url,internal-url}`).

## Consequences

- A file is served from the edge, the function called once per size.
- The internal entry is public, guarded by the token only, and an upload is limited to the 6 MB of a Lambda request.
- The api must be applied after the storage, whose parameters it reads.

## Alternatives considered

- The function serving every file from the bucket, without CloudFront: an invocation at each display, no cache at the edge.

## Revisit when

The domain of the platform comes (the address of the distribution changes), or the files outgrow what a Lambda request carries.
