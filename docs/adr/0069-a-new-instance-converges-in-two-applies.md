# 0069. The apps read each other without depending on each other: a new instance converges in two applies

- Date: 2026-10-18
- Status: Accepted
- Supersedes: the order of the first deployment of [0059](0059-terraform-per-app-values-out-of-the-repository.md), [0061](0061-terraform-applies-each-merge-into-dev.md), [0065](0065-the-files-on-aws-behind-cloudfront.md) and [0066](0066-the-spa-on-aws-in-releases.md)
- Implemented by: `apps/api/infra/{front,storage}.tf`, `apps/front/infra/parameters.tf`

## Context

The apps read in SSM what the others publish (0059): the api the addresses of the front and of the storage, the front the address of the api. Each read failed while the parameter did not exist: a new instance had to be applied app after app, in an order with a cycle (the front reads the api, the api reads the front).

## Decision

- An app reads the parameters of another by their path, and takes an empty value for one not published yet.
- A new instance is applied twice: the first run creates every app with what the others published so far, the second gives each the addresses of the others. The apply workflows run by hand do the second.

## Consequences

- No order between the apps, nor between their jobs of a same run.
- After the first run of a new instance, the SPA does not reach its api, nor the api its storage, until the second.

## Alternatives considered

None: an order between the apps could not break the cycle.

## Revisit when

An app needs another to exist before its own creation.
