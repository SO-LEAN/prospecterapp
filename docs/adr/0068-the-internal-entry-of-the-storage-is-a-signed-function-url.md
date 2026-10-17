# 0068. On AWS, the internal entry of the storage is a function URL answering only signed requests

- Date: 2026-10-18
- Status: Accepted
- Implemented by: `apps/storage/infra/{function-url,cloudfront,lambda,parameters}.tf`, `apps/storage/src/InternalCaller.php`, `apps/api/src/Shared/{StorageClient,StorageAuthentication,TokenOnlyStorageAuthentication,SigV4StorageAuthentication}.php`, `apps/api/config/services.yaml`, `apps/api/infra/lambda.tf`

## Context

Behind the edge (0067), the internal entry of the storage, its HTTP API, kept its AWS address: the api stores and removes files there with the token of the services, and CloudFront asks it a missing size (0065). Anybody knowing the address could call it without the edge: the token guards the writes, not the sizes, each asked again being an invocation.

## Decision

- The internal entry is a function URL with IAM authorization: only requests signed by a role allowed to call the function are answered.
- CloudFront signs its requests for a missing size with an origin access control of type `lambda`; the function allows its distribution only.
- The api signs its requests with the credentials of its function (SigV4, service `lambda`) in the `prod` environment; locally the internal server stays on the Docker network, with the token only.
- The token of the services moves to its own header, `X-Storage-Token`, the `Authorization` header carrying the signature; the storage keeps checking it.
- The function reads its public address in SSM at its start, the distribution calling the function.

## Consequences

- No entry of the storage answers an unsigned request on AWS.
- A new public address of the files reaches a warm function only when it starts again.
- The permissions boundary of the roles allows `lambda:InvokeFunctionUrl`.
- The HTTP API of the storage goes once the api calls the function URL.

## Alternatives considered

- IAM authorization on the routes of the HTTP API, the route of the sizes left open behind a secret header CloudFront adds: an entry still open, and a second secret (weighed by the user).

## Revisit when

The api and the storage share a network, or the files outgrow what a Lambda request carries.
