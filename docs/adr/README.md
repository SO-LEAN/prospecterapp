# Architecture Decision Records

Why the project is shaped the way it is: one file per decision in `docs/adr/`, in the order they were made. The project is being refactored and these decisions will change: a change is a new record superseding the old one ([TEMPLATE.md](TEMPLATE.md)). Read a record before working on its subject.

| # | Decision | Status | Date |
|---|---|---|---|
| [0001](0001-record-architecture-decisions.md) | Record architecture decisions | Accepted | 2026-10-15 |
| [0002](0002-monorepo-with-a-local-core-package.md) | One repository: front, API and core, the core as a local Composer package | Accepted | 2026-10-13 |
| [0003](0003-single-linear-main-branch.md) | A single linear `main`, short-lived branches, fast-forward only | Superseded by 0057 | 2026-10-13 |
| [0004](0004-php-8-5-and-symfony-8-1.md) | PHP 8.5 and Symfony 8.1, native types, PHPStan level 5 | Accepted, the Doctrine mapping superseded by 0048 | 2026-10-13 |
| [0005](0005-clean-architecture-with-the-core-as-hexagon.md) | Clean architecture: the core is the hexagon, the API adapts, the front presents | Accepted | 2026-10-13 |
| [0006](0006-react-spa-and-json-api.md) | A React SPA and a JSON API replace the server-rendered application | Accepted, the OAuth 2 server superseded by 0034, then 0052 | 2026-10-14 |
| [0007](0007-plain-symfony-controllers-one-per-endpoint.md) | Plain Symfony controllers, one per endpoint, resource-oriented URLs | Superseded by 0025 | 2026-10-14 |
| [0008](0008-committed-openapi-contract.md) | The OpenAPI contract is generated, committed, checked and typed in the front | Accepted | 2026-10-14 |
| [0009](0009-api-inputs-and-errors.md) | Inputs have the shape of resources, unknown fields are rejected, errors are problem details | Accepted | 2026-10-14 |
| [0010](0010-oauth-2-authorization-code-with-pkce.md) | OAuth 2 authorization code with PKCE for the SPA | Superseded by 0052 | 2026-10-14 |
| [0011](0011-authentication-outside-the-hexagon.md) | Authentication is infrastructure, outside the hexagon | Accepted, where the credentials are kept superseded by 0052 | 2026-10-15 |
| [0012](0012-dedicated-authorization-server.md) | Delegate authentication to a dedicated authorization server | Superseded by 0034 | 2026-10-15 |
| [0013](0013-front-stack.md) | Front stack: React, TanStack, typed client, shadcn/ui | Accepted, the session library superseded by 0052, the pagination by 0049 | 2026-10-14 |
| [0014](0014-open-maps-and-state-geocoding.md) | Open maps (MapLibre, OpenFreeMap) and the French State geocoder | Accepted | 2026-10-14 |
| [0015](0015-local-stack-in-docker.md) | The whole stack runs locally in Docker, driven by make | Accepted | 2026-10-14 |
| [0016](0016-sqlite-without-migrations.md) | SQLite, schema generated from the mapping, no migrations | Superseded by 0048, 0052 | 2026-10-13 |
| [0017](0017-gherkin-acceptance-tests-on-the-core.md) | Gherkin acceptance tests on the core, only ports doubled | Accepted | 2026-10-15 |
| [0018](0018-readonly-core-model.md) | Readonly requests, responses and value objects; flat entity columns | Superseded by 0023 | 2026-10-15 |
| [0019](0019-organizations-reachable-only-by-their-prospector.md) | An organization is reachable only by the organization prospecting it | Superseded by 0026 | 2026-10-15 |
| [0020](0020-command-bus-instead-of-the-facade.md) | Use cases as commands on a command bus, no query bus | Superseded by 0029 | 2026-10-15 |
| [0021](0021-use-cases-named-after-business-intents.md) | Use cases named after business intents | Superseded by 0025 | 2026-10-14 |
| [0022](0022-clean-code-conventions.md) | Clean code, as taught by Robert C. Martin, applied to PHP and TypeScript | Accepted | 2026-10-13 |
| [0023](0023-tactical-ddd-in-the-core.md) | Tactical DDD in the core: aggregates, value objects, repositories | Accepted, persistence superseded by 0028 | 2026-10-15 |
| [0024](0024-uuid-v7-identities-given-by-the-repository.md) | Identities are UUID v7 given by the repository | Superseded by 0036 | 2026-10-15 |
| [0025](0025-one-business-intent-per-use-case-and-endpoint.md) | One business intent per use case and per endpoint; edition by section | Accepted | 2026-10-15 |
| [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md) | Roles are checked by the API, ownership and state are decided by the aggregate | Accepted, ownership by own users superseded by 0030 | 2026-10-15 |
| [0027](0027-hypermedia-links-from-the-possible-intents.md) | Resources carry hypermedia links built from the possible intents of the core | Accepted | 2026-10-15 |
| [0028](0028-aggregates-keep-their-value-objects.md) | Aggregates keep their value objects; the Doctrine mapping adapts to them | Accepted, the Doctrine mapping superseded by 0048 | 2026-10-15 |
| [0029](0029-cqrs-with-a-synchronous-messenger-command-bus.md) | CQRS: commands on a synchronous Messenger bus return nothing, only queries return data | Accepted | 2026-10-15 |
| [0030](0030-bounded-contexts-accounts-prospecting.md) | Bounded contexts Accounts and Prospecting, a shared kernel, code grouped by context | Accepted, port names superseded by 0045 | 2026-10-15 |
| [0031](0031-personal-data-context.md) | A PersonalData context keeps the personal data of natural persons, and decides who reads them | Accepted, port names superseded by 0045 | 2026-10-15 |
| [0032](0032-login-email-kept-by-authentication.md) | Members sign in with an email kept by authentication | Accepted, where the login email is kept superseded by 0052 | 2026-10-15 |
| [0033](0033-storage-app.md) | A storage app keeps the files and serves the images in named sizes | Accepted, the disk superseded by [0051](0051-files-in-an-s3-bucket.md), the bearer token by [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md) | 2026-10-15 |
| [0034](0034-authorization-server-app.md) | An auth app is the authorization server; the API is a resource server | Superseded by 0052 | 2026-10-15 |
| [0035](0035-plain-requests-and-responses-typed-refusals.md) | Requests and responses are plain data; the use cases translate the refusals of the domain | Accepted, authentication query superseded by 0040 | 2026-10-15 |
| [0036](0036-client-gives-identity-and-moment-of-a-command.md) | The client gives the identity and the moment of a command; aggregates keep their creation and last change | Accepted | 2026-10-15 |
| [0037](0037-sign-in-screen-in-the-spa.md) | The sign-in screen is in the SPA; the authorization server signs in with a JSON POST and its session cookie | Accepted, the protocol superseded by 0052 | 2026-10-15 |
| [0038](0038-one-database-per-bounded-context.md) | One database per bounded context | Accepted, the databases became tables by 0048 | 2026-10-15 |
| [0039](0039-set-rules-in-domain-services-indexes-as-safety-net.md) | A rule on a set of aggregates is a domain service; a unique index is its safety net | Accepted, the unique index superseded by 0048 | 2026-10-15 |
| [0040](0040-security-reads-the-caller-with-consult-my-account.md) | The security of the API reads who calls with ConsultMyAccount | Accepted | 2026-10-15 |
| [0041](0041-contacts-of-prospected-organizations.md) | Contacts: the persons in PersonalData, their identities in the organization, two separate gestures | Accepted, gestures and erasure superseded by 0042 | 2026-10-15 |
| [0042](0042-contacts-never-alone-domain-events.md) | A contact is never alone; domain events recorded by the aggregates, on a synchronous event bus | Accepted, shared events superseded by 0046 | 2026-10-15 |
| [0043](0043-a-domain-event-per-intention-and-an-activity-journal.md) | Every intention records a domain event; an activity journal keeps them all | Accepted, shared events superseded by 0046 | 2026-10-15 |
| [0044](0044-dependencies-are-injected.md) | Dependencies are injected, never built; a PHPStan rule checks it | Accepted | 2026-10-15 |
| [0045](0045-published-language-between-contexts.md) | Each context publishes the questions the others ask; their anticorruption layers see nothing else | Accepted, location superseded by 0047 | 2026-10-16 |
| [0046](0046-integration-events-between-contexts.md) | Contexts exchange published integration events; domain events stay in their context | Accepted, location superseded by 0047 | 2026-10-16 |
| [0047](0047-a-contract-per-context.md) | Each context keeps what it publishes in a Contract of plain data | Accepted | 2026-10-16 |
| [0048](0048-dynamodb-one-table-per-context-on-ministack.md) | DynamoDB, one table per bounded context, emulated locally by MiniStack | Accepted | 2026-10-16 |
| [0049](0049-lists-in-slices-after-a-cursor.md) | Lists come in slices after a cursor, never in numbered pages | Accepted | 2026-10-16 |
| [0050](0050-text-search-by-a-projection-of-word-beginnings.md) | The text search is a projection of word beginnings, kept by the events, rebuildable | Superseded by 0071 | 2026-10-16 |
| [0051](0051-files-in-an-s3-bucket.md) | The files are in an S3 bucket, served from it; PHP only makes the missing sizes | Accepted, on AWS the bucket read by CloudFront only since [0065](0065-the-files-on-aws-behind-cloudfront.md) | 2026-10-16 |
| [0052](0052-cognito-signs-the-members-in.md) | Cognito signs the members in; the SPA keeps its screen and signs in by SRP; the API checks the tokens | Accepted | 2026-10-16 |
| [0053](0053-the-api-in-a-local-lambda-with-the-bref-layer.md) | Locally, the API runs on demand in a Lambda with the Bref layer, the repository mounted | Accepted, on demand superseded by 0055 | 2026-10-16 |
| [0054](0054-the-storage-in-a-local-lambda-with-the-imagick-layer.md) | Locally, the storage runs in a Lambda too, with the imagick layer of Bref | Accepted, on demand superseded by 0055 | 2026-10-16 |
| [0055](0055-locally-the-apps-run-only-in-their-lambdas.md) | Locally, the API and the storage run only in their Bref Lambdas | Accepted | 2026-10-16 |
| [0056](0056-the-command-line-on-the-php-of-the-lambdas.md) | The command line runs on the PHP of the Lambdas | Accepted | 2026-10-16 |
| [0057](0057-continuous-integration-around-dev.md) | Continuous integration around `dev`: branches of work by pull request, `main` and `dev` protected | Accepted, the rebase merges into `main` superseded by 0058 | 2026-10-16 |
| [0058](0058-main-receives-dev-and-hotfixes-by-merge-commits.md) | `main` receives `dev` and the hotfixes by merge commits | Accepted | 2026-10-16 |
| [0059](0059-terraform-per-app-values-out-of-the-repository.md) | Each app keeps its Terraform; the values of an installation stay out of the repository | Accepted, the order of the first deployment superseded by [0069](0069-a-new-instance-converges-in-two-applies.md) | 2026-10-16 |
| [0060](0060-terraform-plans-every-pull-request.md) | Terraform plans every pull request touching the infrastructure, with a read-only role | Accepted | 2026-10-17 |
| [0061](0061-terraform-applies-each-merge-into-dev.md) | Terraform applies each merge into `dev` to the instances of the dev account | Accepted, the first deployment ordered by hand superseded by [0069](0069-a-new-instance-converges-in-two-applies.md) | 2026-10-17 |
| [0062](0062-terraform-applies-main-after-approval.md) | Terraform applies each push to `main` to the instances of the prod account, after an approval | Accepted | 2026-10-17 |
| [0063](0063-the-cd-packages-an-app-and-its-terraform-deploys-it.md) | The continuous deployment packages an app, its Terraform deploys the package | Accepted, the packaging script generalized by [0065](0065-the-files-on-aws-behind-cloudfront.md) (`tools/package/lambda`), the address of the api by [0067](0067-the-apps-behind-an-edge.md) | 2026-10-17 |
| [0064](0064-an-account-is-opened-by-its-founder.md) | An account is opened by its founder, from the console until the members open theirs | Accepted | 2026-10-17 |
| [0065](0065-the-files-on-aws-behind-cloudfront.md) | On AWS, CloudFront serves the files from the bucket and fails over to the storage for a missing size | Accepted, the HTTP API of the storage superseded by [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md), the order of the applies by [0069](0069-a-new-instance-converges-in-two-applies.md) | 2026-10-17 |
| [0066](0066-the-spa-on-aws-in-releases.md) | On AWS, the SPA is served by CloudFront in releases kept 30 days after they are replaced | Accepted, the order of the applies superseded by [0069](0069-a-new-instance-converges-in-two-applies.md) | 2026-10-17 |
| [0067](0067-the-apps-behind-an-edge.md) | Behind an edge, the apps take their domains from SSM and answer only what the edge passes on | Accepted, the internal entry of the storage superseded by [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md) | 2026-10-17 |
| [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md) | On AWS, the internal entry of the storage is a function URL answering only signed requests | Accepted | 2026-10-18 |
| [0069](0069-a-new-instance-converges-in-two-applies.md) | The apps read each other without depending on each other: a new instance converges in two applies | Accepted | 2026-10-18 |
| [0070](0070-a-removed-file-is-forgotten-by-the-caches.md) | A removed file is forgotten by the caches in front of the bucket | Accepted | 2026-10-18 |
| [0071](0071-search-by-the-beginning-of-the-name-or-of-the-email.md) | An aggregate is found by the beginning of its name or of its email, read in indexes of its table | Accepted | 2026-10-18 |
| [0072](0072-phone-numbers-in-e164-by-libphonenumber-in-the-core.md) | Phone numbers are kept in E.164 by libphonenumber, inside the core; a call is recognized by its number | Accepted | 2026-10-18 |
