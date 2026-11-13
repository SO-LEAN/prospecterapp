# Decision records

Why the project is shaped the way it is: one record per decision, in the order they were made, typed `architecture` or `domain`. The journal is immutable (ADR 0090): a record is never edited once accepted, and a later decision replacing part of a former one says so in its own `Supersedes` line. What is in force is not read here but in the documents by subject, `docs/README.md`; read the records only to know why a rule is, or before changing it ([TEMPLATE.md](TEMPLATE.md)).

| # | Decision | Type | Status | Date |
|---|---|---|---|---|
| [0001](0001-record-architecture-decisions.md) | Record architecture decisions | architecture | Accepted | 2026-10-15 |
| [0002](0002-monorepo-with-a-local-core-package.md) | One repository: front, API and core, the core as a local Composer package | architecture | Accepted | 2026-10-13 |
| [0003](0003-single-linear-main-branch.md) | A single linear `main`, short-lived branches, fast-forward only | architecture | Superseded by [0057](0057-continuous-integration-around-dev.md) | 2026-10-13 |
| [0004](0004-php-8-5-and-symfony-8-1.md) | PHP 8.5 and Symfony 8.1, native types, PHPStan level 5 | architecture | Accepted | 2026-10-13 |
| [0005](0005-clean-architecture-with-the-core-as-hexagon.md) | Clean architecture: the core is the hexagon, the API adapts, the front presents | architecture | Accepted | 2026-10-13 |
| [0006](0006-react-spa-and-json-api.md) | A React SPA and a JSON API replace the server-rendered application | architecture | Accepted | 2026-10-14 |
| [0007](0007-plain-symfony-controllers-one-per-endpoint.md) | Plain Symfony controllers, one per endpoint, resource-oriented URLs | architecture | Superseded by [0025](0025-one-business-intent-per-use-case-and-endpoint.md) | 2026-10-14 |
| [0008](0008-committed-openapi-contract.md) | The OpenAPI contract is generated, committed, checked and typed in the front | architecture | Accepted | 2026-10-14 |
| [0009](0009-api-inputs-and-errors.md) | Inputs have the shape of resources, unknown fields are rejected, errors are problem details | architecture | Accepted | 2026-10-14 |
| [0010](0010-oauth-2-authorization-code-with-pkce.md) | OAuth 2 authorization code with PKCE for the SPA | architecture | Superseded by [0052](0052-cognito-signs-the-members-in.md) | 2026-10-14 |
| [0011](0011-authentication-outside-the-hexagon.md) | Authentication is infrastructure, outside the hexagon | architecture | Accepted | 2026-10-15 |
| [0012](0012-dedicated-authorization-server.md) | Delegate authentication to a dedicated authorization server | architecture | Superseded by [0034](0034-authorization-server-app.md) | 2026-10-15 |
| [0013](0013-front-stack.md) | Front stack: React, TanStack, typed client, shadcn/ui | architecture | Accepted | 2026-10-14 |
| [0014](0014-open-maps-and-state-geocoding.md) | Open maps (MapLibre, OpenFreeMap) and the French State geocoder | architecture | Accepted | 2026-10-14 |
| [0015](0015-local-stack-in-docker.md) | The whole stack runs locally in Docker, driven by make | architecture | Accepted | 2026-10-14 |
| [0016](0016-sqlite-without-migrations.md) | SQLite, schema generated from the mapping, no migrations | architecture | Superseded by [0048](0048-dynamodb-one-table-per-context-on-ministack.md), [0052](0052-cognito-signs-the-members-in.md) | 2026-10-13 |
| [0017](0017-gherkin-acceptance-tests-on-the-core.md) | Gherkin acceptance tests on the core, only ports doubled | architecture | Accepted | 2026-10-15 |
| [0018](0018-readonly-core-model.md) | Readonly requests, responses and value objects; flat entity columns | architecture | Superseded by [0023](0023-tactical-ddd-in-the-core.md) | 2026-10-15 |
| [0019](0019-organizations-reachable-only-by-their-prospector.md) | An organization is reachable only by the organization prospecting it | domain | Superseded by [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md) | 2026-10-15 |
| [0020](0020-command-bus-instead-of-the-facade.md) | Use cases as commands on a command bus, no query bus | architecture | Superseded by [0029](0029-cqrs-with-a-synchronous-messenger-command-bus.md) | 2026-10-15 |
| [0021](0021-use-cases-named-after-business-intents.md) | Use cases named after business intents | architecture | Superseded by [0025](0025-one-business-intent-per-use-case-and-endpoint.md) | 2026-10-14 |
| [0022](0022-clean-code-conventions.md) | Clean code, as taught by Robert C. Martin, applied to PHP and TypeScript | architecture | Accepted | 2026-10-13 |
| [0023](0023-tactical-ddd-in-the-core.md) | Tactical DDD in the core: aggregates, value objects, repositories | architecture | Accepted | 2026-10-15 |
| [0024](0024-uuid-v7-identities-given-by-the-repository.md) | Identities are UUID v7 given by the repository | architecture | Superseded by [0036](0036-client-gives-identity-and-moment-of-a-command.md) | 2026-10-15 |
| [0025](0025-one-business-intent-per-use-case-and-endpoint.md) | One business intent per use case and per endpoint; edition by section | architecture | Accepted | 2026-10-15 |
| [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md) | Roles are checked by the API, ownership and state are decided by the aggregate | architecture | Accepted | 2026-10-15 |
| [0027](0027-hypermedia-links-from-the-possible-intents.md) | Resources carry hypermedia links built from the possible intents of the core | architecture | Accepted | 2026-10-15 |
| [0028](0028-aggregates-keep-their-value-objects.md) | Aggregates keep their value objects; the Doctrine mapping adapts to them | architecture | Accepted | 2026-10-15 |
| [0029](0029-cqrs-with-a-synchronous-messenger-command-bus.md) | CQRS: commands on a synchronous Messenger bus return nothing, only queries return data | architecture | Accepted | 2026-10-15 |
| [0030](0030-bounded-contexts-accounts-prospecting.md) | Bounded contexts Accounts and Prospecting, a shared kernel, code grouped by context | architecture | Accepted | 2026-10-15 |
| [0031](0031-personal-data-context.md) | A PersonalData context keeps the personal data of natural persons, and decides who reads them | domain | Accepted | 2026-10-15 |
| [0032](0032-login-email-kept-by-authentication.md) | Members sign in with an email kept by authentication | architecture | Accepted | 2026-10-15 |
| [0033](0033-storage-app.md) | A storage app keeps the files and serves the images in named sizes | architecture | Accepted | 2026-10-15 |
| [0034](0034-authorization-server-app.md) | An auth app is the authorization server; the API is a resource server | architecture | Superseded by [0052](0052-cognito-signs-the-members-in.md) | 2026-10-15 |
| [0035](0035-plain-requests-and-responses-typed-refusals.md) | Requests and responses are plain data; the use cases translate the refusals of the domain | architecture | Accepted | 2026-10-15 |
| [0036](0036-client-gives-identity-and-moment-of-a-command.md) | The client gives the identity and the moment of a command; aggregates keep their creation and last change | architecture | Accepted | 2026-10-15 |
| [0037](0037-sign-in-screen-in-the-spa.md) | The sign-in screen is in the SPA; the authorization server signs in with a JSON POST and its session cookie | architecture | Accepted | 2026-10-15 |
| [0038](0038-one-database-per-bounded-context.md) | One database per bounded context | architecture | Accepted | 2026-10-15 |
| [0039](0039-set-rules-in-domain-services-indexes-as-safety-net.md) | A rule on a set of aggregates is a domain service; a unique index is its safety net | architecture | Accepted | 2026-10-15 |
| [0040](0040-security-reads-the-caller-with-consult-my-account.md) | The security of the API reads who calls with ConsultMyAccount | architecture | Accepted | 2026-10-15 |
| [0041](0041-contacts-of-prospected-organizations.md) | Contacts: the persons in PersonalData, their identities in the organization, two separate gestures | domain | Accepted | 2026-10-15 |
| [0042](0042-contacts-never-alone-domain-events.md) | A contact is never alone; domain events recorded by the aggregates, on a synchronous event bus | domain | Accepted | 2026-10-15 |
| [0043](0043-a-domain-event-per-intention-and-an-activity-journal.md) | Every intention records a domain event; an activity journal keeps them all | architecture | Accepted | 2026-10-15 |
| [0044](0044-dependencies-are-injected.md) | Dependencies are injected, never built; a PHPStan rule checks it | architecture | Accepted | 2026-10-15 |
| [0045](0045-published-language-between-contexts.md) | Each context publishes the questions the others ask; their anticorruption layers see nothing else | architecture | Accepted | 2026-10-16 |
| [0046](0046-integration-events-between-contexts.md) | Contexts exchange published integration events; domain events stay in their context | architecture | Accepted | 2026-10-16 |
| [0047](0047-a-contract-per-context.md) | Each context keeps what it publishes in a Contract of plain data | architecture | Accepted | 2026-10-16 |
| [0048](0048-dynamodb-one-table-per-context-on-ministack.md) | DynamoDB, one table per bounded context, emulated locally by MiniStack | architecture | Accepted | 2026-10-16 |
| [0049](0049-lists-in-slices-after-a-cursor.md) | Lists come in slices after a cursor, never in numbered pages | architecture | Accepted | 2026-10-16 |
| [0050](0050-text-search-by-a-projection-of-word-beginnings.md) | The text search is a projection of word beginnings, kept by the events, rebuildable | architecture | Superseded by [0071](0071-search-by-the-beginning-of-the-name-or-of-the-email.md) | 2026-10-16 |
| [0051](0051-files-in-an-s3-bucket.md) | The files are in an S3 bucket, served from it; PHP only makes the missing sizes | architecture | Accepted | 2026-10-16 |
| [0052](0052-cognito-signs-the-members-in.md) | Cognito signs the members in; the SPA keeps its screen and signs in by SRP; the API checks the tokens | architecture | Accepted | 2026-10-16 |
| [0053](0053-the-api-in-a-local-lambda-with-the-bref-layer.md) | Locally, the API runs on demand in a Lambda with the Bref layer, the repository mounted | architecture | Accepted | 2026-10-16 |
| [0054](0054-the-storage-in-a-local-lambda-with-the-imagick-layer.md) | Locally, the storage runs in a Lambda too, with the imagick layer of Bref | architecture | Accepted | 2026-10-16 |
| [0055](0055-locally-the-apps-run-only-in-their-lambdas.md) | Locally, the API and the storage run only in their Bref Lambdas | architecture | Accepted | 2026-10-16 |
| [0056](0056-the-command-line-on-the-php-of-the-lambdas.md) | The command line runs on the PHP of the Lambdas | architecture | Accepted | 2026-10-16 |
| [0057](0057-continuous-integration-around-dev.md) | Continuous integration around `dev`: branches of work by pull request, `main` and `dev` protected | architecture | Accepted | 2026-10-16 |
| [0058](0058-main-receives-dev-and-hotfixes-by-merge-commits.md) | `main` receives `dev` and the hotfixes by merge commits | architecture | Accepted | 2026-10-16 |
| [0059](0059-terraform-per-app-values-out-of-the-repository.md) | Each app keeps its Terraform; the values of an installation stay out of the repository | architecture | Accepted | 2026-10-16 |
| [0060](0060-terraform-plans-every-pull-request.md) | Terraform plans every pull request touching the infrastructure, with a read-only role | architecture | Accepted | 2026-10-17 |
| [0061](0061-terraform-applies-each-merge-into-dev.md) | Terraform applies each merge into `dev` to the instances of the dev account | architecture | Accepted | 2026-10-17 |
| [0062](0062-terraform-applies-main-after-approval.md) | Terraform applies each push to `main` to the instances of the prod account, after an approval | architecture | Accepted | 2026-10-17 |
| [0063](0063-the-cd-packages-an-app-and-its-terraform-deploys-it.md) | The continuous deployment packages an app, its Terraform deploys the package | architecture | Accepted | 2026-10-17 |
| [0064](0064-an-account-is-opened-by-its-founder.md) | An account is opened by its founder, from the console until the members open theirs | domain | Accepted | 2026-10-17 |
| [0065](0065-the-files-on-aws-behind-cloudfront.md) | On AWS, CloudFront serves the files from the bucket and fails over to the storage for a missing size | architecture | Accepted | 2026-10-17 |
| [0066](0066-the-spa-on-aws-in-releases.md) | On AWS, the SPA is served by CloudFront in releases kept 30 days after they are replaced | architecture | Accepted | 2026-10-17 |
| [0067](0067-the-apps-behind-an-edge.md) | Behind an edge, the apps take their domains from SSM and answer only what the edge passes on | architecture | Accepted | 2026-10-17 |
| [0068](0068-the-internal-entry-of-the-storage-is-a-signed-function-url.md) | On AWS, the internal entry of the storage is a function URL answering only signed requests | architecture | Accepted | 2026-10-18 |
| [0069](0069-a-new-instance-converges-in-two-applies.md) | The apps read each other without depending on each other: a new instance converges in two applies | architecture | Accepted | 2026-10-18 |
| [0070](0070-a-removed-file-is-forgotten-by-the-caches.md) | A removed file is forgotten by the caches in front of the bucket | architecture | Accepted | 2026-10-18 |
| [0071](0071-search-by-the-beginning-of-the-name-or-of-the-email.md) | An aggregate is found by the beginning of its name or of its email, read in indexes of its table | architecture | Accepted | 2026-10-18 |
| [0072](0072-phone-numbers-in-e164-by-libphonenumber-in-the-core.md) | Phone numbers are kept in E.164 by libphonenumber, inside the core; a call is recognized by its number | domain | Accepted | 2026-10-18 |
| [0073](0073-a-refusal-is-named-by-its-class-and-worded-by-the-client.md) | A refusal is named by its class, and worded by the client in its language | architecture | Accepted | 2026-10-18 |
| [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md) | An exchange with a contact is an aggregate of Prospecting, kept for the organizations of the contact | domain | Accepted, never in the future and the order of a timeline superseded by [0111](0111-an-exchange-is-planned-then-done-to-come-after-the-moment-of-the-reading.md), a missed call by [0112](0112-a-planned-exchange-is-done-missed-moved-or-cancelled-by-its-holder.md) | 2026-10-18 |
| [0075](0075-a-member-is-registered-by-an-administrator-of-the-account.md) | A member is registered by an administrator of the account, under a profile of their own | domain | Accepted | 2026-10-20 |
| [0076](0076-the-timeline-of-an-organization-gathers-its-exchanges-and-those-of-its-contacts.md) | The timeline of an organization gathers its exchanges and those of its contacts | domain | Accepted | 2026-10-20 |
| [0077](0077-the-attachments-are-private-files-reached-by-signed-urls.md) | The attachments of an exchange are private files, sent and read by short-lived signed URLs | architecture | Accepted | 2026-10-22 |
| [0078](0078-every-prospector-reads-the-exchanges-and-corrects-their-text.md) | Every prospector reads the exchanges, their text flagged without the right, and may correct it | domain | Accepted | 2026-10-22 |
| [0079](0079-the-exchanges-of-a-forgotten-contact-stay-to-make-conform.md) | The exchanges of a forgotten contact stay in the timelines of their organizations, to make conform | domain | Accepted | 2026-10-23 |
| [0080](0080-a-makefile-per-project-the-root-one-a-facade.md) | A Makefile per project, run from its directory; the root one a façade | architecture | Accepted | 2026-10-23 |
| [0081](0081-the-api-checks-the-access-the-core-keeps-the-state.md) | The API checks the access to a resource with a voter; the core keeps its state and what a member reads | architecture | Accepted, the intents of an exchange offered without the member superseded by [0112](0112-a-planned-exchange-is-done-missed-moved-or-cancelled-by-its-holder.md) | 2026-10-25 |
| [0082](0082-a-local-function-serves-as-the-owner-of-the-repository.md) | A local function serves as the owner of the mounted repository | architecture | Accepted | 2026-10-26 |
| [0083](0083-the-scripts-of-tools-become-recipes-of-their-project.md) | The scripts of `tools/` become recipes of their project; the PHPStan rules a package | architecture | Accepted | 2026-10-26 |
| [0084](0084-the-e2e-journeys-run-in-the-network-of-the-stack.md) | The e2e journeys run in the network of the stack, the local hosts resolved to its nginx | architecture | Accepted | 2026-10-26 |
| [0085](0085-a-query-presents-its-answer-to-a-presenter-built-for-the-call.md) | A query presents its answer to a presenter built for the call; a service is stateless | architecture | Accepted | 2026-10-26 |
| [0086](0086-the-domain-of-a-context-is-grouped-by-kind-of-building-block.md) | The domain of a context is grouped by kind of building block | architecture | Accepted | 2026-10-28 |
| [0087](0087-a-controller-is-named-after-its-intent-and-its-role.md) | A controller is named after its intent and its role; a property after its type, a repository after its collection | architecture | Accepted | 2026-10-30 |
| [0088](0088-a-query-reads-its-read-model-through-a-port-a-policy-says-what-is-possible.md) | A query reads its read model through a port, in the table of the aggregate; a policy says what is possible | architecture | Accepted | 2026-10-30 |
| [0089](0089-a-contract-declares-the-service-it-offers.md) | A Contract declares the service it offers, answering questions on sets; the anticorruption layers depend on it by its type | architecture | Accepted | 2026-10-31 |
| [0090](0090-the-current-state-lives-in-documents-the-records-are-a-journal.md) | The current state lives in documents by subject; the records are an immutable journal, typed architecture or domain | architecture | Accepted | 2026-10-31 |
| [0091](0091-mutation-tests-measure-what-the-tests-of-a-branch-protect.md) | Tests of mutation measure what the tests of a branch protect: Infection and Stryker on the lines it adds, the survivors listed | architecture | Accepted | 2026-11-02 |
| [0092](0092-the-tests-depend-on-no-service-outside-the-stack.md) | The tests depend on no service outside the stack: the journeys run on its doubles, the geocoder in nginx, the maps in a fixture | architecture | Accepted | 2026-11-04 |
| [0093](0093-a-text-of-the-front-written-in-the-code-is-refused-by-the-lint.md) | A text of the front written in the code is refused by the lint, and the libraries speak through the translations | architecture | Accepted | 2026-11-05 |
| [0094](0094-a-handler-is-decorated-by-the-decorator-of-its-kind.md) | A handler is decorated by the decorator of its kind, and its client depends on the interface of the core | architecture | Accepted | 2026-11-08 |
| [0095](0095-a-command-writes-its-aggregate-and-its-outbox-all-at-once.md) | A command writes its aggregate and its outbox all at once, or nothing: a unit of work spans the command and its reactions | architecture | Accepted | 2026-11-09 |
| [0096](0096-no-activity-journal-an-audit-is-designed-when-it-is-needed.md) | No activity journal: an audit is designed when it is needed, with its purpose, its events, its retention and its readers | architecture | Accepted | 2026-11-09 |
| [0097](0097-events-travel-as-eventbridge-events-reactions-are-subscriptions.md) | Events travel in the shape of EventBridge events, and the reactions are the subscriptions of one file | architecture | Accepted | 2026-11-09 |
| [0098](0098-each-commit-writes-an-outbox-relayed-to-an-eventbridge-bus.md) | Each commit writes its events in an outbox, relayed to a bus of EventBridge | architecture | Accepted | 2026-11-10 |
| [0099](0099-the-contexts-publish-their-facts-by-event-and-by-sliced-service.md) | The contexts publish their facts, by an event and by a sliced service | architecture | Accepted | 2026-11-10 |
| [0100](0100-a-gesture-is-one-use-case-the-member-completes-their-profile.md) | A gesture is one use case: the member completes their profile at their first sign-in | architecture | Accepted | 2026-11-10 |
| [0101](0101-the-reactions-run-after-the-command-in-a-worker.md) | The reactions run after the command, in a worker, each in its own transaction | architecture | Accepted | 2026-11-11 |
| [0102](0102-a-transactional-inbox-per-subscription.md) | A transactional inbox per subscription | architecture | Accepted | 2026-11-11 |
| [0103](0103-the-reporting-context-and-its-rebuildable-projections.md) | The Reporting context and its rebuildable projections | architecture | Accepted | 2026-11-11 |
| [0104](0104-reporting-keeps-its-figures-by-additions-its-rebuild-run-from-the-console.md) | Reporting keeps its figures by additions in a table of its own, its rebuild run from the console | architecture | Accepted | 2026-11-11 |
| [0105](0105-a-read-only-check-of-the-consistency-reads-the-tables-of-every-context.md) | A read-only check of the consistency reads the tables of every context | architecture | Accepted | 2026-11-11 |
| [0106](0106-a-rebuild-abandons-the-version-being-built-before-emptying-it.md) | A rebuild abandons the version being built before emptying it | architecture | Accepted | 2026-11-12 |
| [0107](0107-a-command-on-an-instance-is-refused-while-the-local-code-lags-behind-it.md) | A command on an instance is refused while the local code lags behind it | architecture | Accepted | 2026-11-12 |
| [0108](0108-the-instances-deliver-outbox-by-default-of-the-code.md) | The instances deliver outbox, by the default of the code | architecture | Accepted | 2026-11-12 |
| [0109](0109-the-checks-run-together-the-tests-on-a-ministack-of-their-own.md) | The checks of the integration run together, the tests on a MiniStack of their own | architecture | Accepted | 2026-11-13 |
| [0110](0110-the-integration-runs-the-checks-the-changes-need.md) | The integration runs the checks the changes need, the lint and the tests of a project together | architecture | Accepted | 2026-11-13 |
| [0111](0111-an-exchange-is-planned-then-done-to-come-after-the-moment-of-the-reading.md) | An exchange is planned, then done; what is to come is read after the moment of the reading | domain | Accepted | 2026-11-13 |
| [0112](0112-a-planned-exchange-is-done-missed-moved-or-cancelled-by-its-holder.md) | A planned exchange is done, missed, moved or cancelled by its holder; a cancelled one is listed from its cancellation | domain | Accepted | 2026-11-13 |
