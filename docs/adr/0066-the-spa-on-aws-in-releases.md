# 0066. On AWS, the SPA is served by CloudFront in releases kept 30 days after they are replaced

- Date: 2026-10-17
- Status: Accepted, the order of the applies superseded by [0069](0069-a-new-instance-converges-in-two-applies.md)
- Implemented by: `apps/front/infra/{bucket,cloudfront,parameters}.tf`, `apps/front/infra/functions/release.js.tftpl`, `apps/front/src/shared/config/env.ts`, `apps/front/public/config.js`, `tools/deploy/front`, the `package-front` and `release-front` targets of the `Makefile`, the workflows `terraform-apply-*.yml`

## Context

The SPA is to run on AWS like the api and the storage (0063, 0065). Vite fixes its configuration, the address of the api, at the build. Its files are named after their content and loaded on demand: a page still open on a former build asks for files a new build no longer has.

## Decision

- One build for every instance: the SPA reads its configuration from `/config.js`, loaded by `index.html` before it, else from the variables of its build (`.env`, locally).
- Each build is a release under `releases/<version>/` of a private bucket, read by CloudFront only; its files are cached for a year, its `index.html` revalidated.
- A CloudFront Function, written by Terraform with the version and the configuration of the instance (the address of the api read in SSM), serves a page of the SPA from the current release and answers `config.js`; deploying is changing the version there.
- The continuous deployment puts the files of a release with the AWS CLI before the apply, then marks the former releases superseded and removes those superseded for more than 30 days.

## Consequences

- A page opened on a former release keeps working for 30 days after its replacement.
- The files of a release reach the bucket outside Terraform; its plan shows the release that will be served.
- The api must allow the address of the SPA, which it reads in SSM: the front of an instance is applied before the api allows it.

## Alternatives considered

- A build per instance, the address of the api read in SSM before the build: a package per instance, a build that depends on AWS (weighed by the user).
- The files of a release as objects of its Terraform: it would remove the files of the former release a page still needs.

## Revisit when

The domain of the platform comes, or the SPA needs a configuration a CloudFront Function cannot hold.
