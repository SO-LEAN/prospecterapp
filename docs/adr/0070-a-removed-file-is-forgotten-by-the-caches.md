# 0070. A removed file is forgotten by the caches in front of the bucket

- Date: 2026-10-18
- Status: Accepted
- Implemented by: `apps/storage/src/{PublicCache,NoPublicCache,CloudFrontPublicCache,Files}.php`, `apps/storage/config/services.yaml`, `apps/storage/infra/{cloudfront,lambda,parameters}.tf`

## Context

A key of the storage never changes content, so CloudFront and the edge in front of it keep each file for a year (0051, 0065, 0067). A removed file, the picture of a forgotten contact for one, kept being served from their copies.

## Decision

- The storage invalidates in CloudFront the paths of a file it removes, its sizes included (`PublicCache`, CloudFront in the `prod` environment, nothing locally).
- CloudFront tells the edge in front of it to keep no copy (`CDN-Cache-Control: no-store`, RFC 9213): CloudFront stays the only cache of the files.
- The function reads its distribution in SSM at its start, the distribution calling it.

## Consequences

- A removed file stops being served within minutes, the time of an invalidation; only the browsers that had it keep their copy.
- Each file served goes through CloudFront, the edge no longer answering from its cache.
- The permissions boundary of the roles allows `cloudfront:CreateInvalidation`; the first thousand paths a month are free, then a few cents each thousand.

## Alternatives considered

None.

## Revisit when

Files are removed by the thousands a month, or the edge purges its cache by itself.
