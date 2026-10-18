# 0067. Behind an edge, the apps take their domains from SSM and answer only what the edge passes on

- Date: 2026-10-17
- Status: Accepted, the internal entry of the storage superseded by [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md)
- Implemented by: `apps/*/infra/edge.tf`, `apps/front/infra/{cloudfront.tf,functions/release.js.tftpl}`, `apps/storage/infra/{cloudfront.tf,functions/only-through-the-edge.js.tftpl}`, `apps/api/infra/{http-api,lambda,front,storage,parameters}.tf`, `apps/api/src/Shared/OnlyThroughTheEdge.php`, the `workflow_dispatch` of the workflows `terraform-apply-*.yml`

## Context

The apps answer on their AWS addresses (CloudFront, the HTTP APIs, 0063, 0065, 0066). An installation puts them on its domains behind a proxy, its edge (such as Cloudflare): their AWS addresses must then answer nothing, or the edge would be bypassed. How an installation runs its edge is not the business of this repository (0059).

## Decision

- The edge of an installation writes in SSM, under `/prospecter/<instance>/edge/`: the domain of each app (`<app>-domain`), its certificate in ACM (`<app>-certificate`), and the secret it adds to the requests it passes on (`origin-secret`, header `X-Origin-Verify`). Each app reads them if they exist; without them nothing changes.
- The front and the storage put their domain and its certificate on their CloudFront distribution, and their CloudFront Function refuses a request without the secret, compared by its digest.
- The api puts its domain on its HTTP API and closes its AWS address; it refuses a request without the secret (`OnlyThroughTheEdge`).
- The addresses the apps publish and read (`config.js`, the CORS, the public address of the files) are those of the domains, read from the edge parameters rather than from the other apps, which a same run applies at the same time.
- Each app publishes where the edge sends the requests of its domain (`/prospecter/<instance>/<app>/edge-target`); the apply workflows run by hand on their branch apply every app, once the edge parameters changed.

## Consequences

- Once its edge is set, an installation answers on its domains only; the internal entry of the storage keeps its AWS address, guarded by its token.
- An installation sets its edge in two passes: the certificates and the parameters, the apps applied, then its records towards their targets.
- The files stored before the domain keep their former address, which the api no longer recognizes.

## Alternatives considered

- The IP ranges of the edge allowed by an AWS WAF: a few dollars a month per instance, and ranges to keep up to date (weighed by the user).

## Revisit when

The edge can authenticate itself to the origins (mutual TLS), or the internal entry of the storage must leave its AWS address.
