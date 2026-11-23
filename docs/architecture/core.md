# The core

This document states the rules in force. The decision records (`docs/adr`) explain why. Read them only for that, or before changing a rule (ADR 0090).

`packages/core` (`prospecter/core`) is the hexagon, in pure PHP. It holds the bounded contexts Accounts, PersonalData, Prospecting, Reporting, Connectors, Campaigns and Booking, and their shared kernel. Each context has its domain (aggregates, value objects, rules, repository interfaces) and its application (use cases by business intent, with their ports and services). `contexts.md` says what the bounded contexts publish to each other. `docs/domain/` holds each context's language and rules.

## Layout

The code is grouped by bounded context, then by layer (ADR 0030). `tests/Architecture/BoundedContextsTest` fails when a context uses another one. It reads the contexts from the folders of `src`, so a new context is checked as soon as it is added. A context's domain uses neither its application nor its Contract (`tests/Architecture/DomainLayerTest`).

A context's `Domain` is grouped by kind of building block (ADR 0086):

- `Model/`: aggregates, entities, value objects, enums, including the intents;
- `Repository/`: the repository interfaces;
- `Service/`: domain services, each a rule on a set of aggregates (ADR 0039);
- `Rule/`: a pure, stateless rule (`Masking`, `Lateness`). "Policy" keeps the event storming's meaning: a reaction that runs a command when an event happens (ADR 0200);
- `Port/`: what the context asks of the others, implemented by an anticorruption layer (ADR 0045);
- `Event/` and `Exception/`.

A folder exists only when it holds something. The shared kernel is only model, so it stays flat, except for `Event/` and `Exception/`.

- `src/SharedKernel/{Domain,Application}`: what every context shares, kept small:
  - the identities `AccountId` and `MemberId`, the account and the member who acts, which mean the same in every context (ADR 0197);
  - `DomainEvent`, the base class of every domain event (in `Domain/Event`), and `ReleasesEvents` and `RecordsEvents`;
  - `Email` (lower case), `PhoneNumber` (E.164, ADR 0072), `ContactDetails`;
  - links to outside tools (ADR 0165):
    - `ExternalReference` holds a source (a `KnownSource` or the address's host) and the identifier of what it points to there. It recognizes the link and keeps the pasted address to open it.
    - `Designated` is a person or an organization.
    - `ExternalReferences` holds each link once, five at most.
    - The refusals are `NotAWebAddress`, `ReferenceOfAnotherKind`, `UnrecognizedPage` and `TooManyLinks`;
  - `LegalIdentity` and its `Siret` (fourteen digits checked by Luhn, or by their sum for La Poste, ADR 0158);
  - `Address` and its `GeoPoint`;
  - `Location`, where the geocoder finds an address: its `GeoPoint` and its municipality's INSEE code (ADR 0142);
  - `File`;
  - `Origin`: how an organization, a contact or an exchange was created, by hand or by an AI through the MCP server (ADR 0185);
  - `Gesture`: who acts, in which account, when, and the act's `Origin`. It is the argument object of the aggregates' factories (ADR 0191). The exception is an actor who carries a right that its aggregate checks: PersonalData's `Reader`, the administrator registering a member (ADR 0193);
  - `Completion`: what a client knows about a person or an organization, compared with what is recorded. It lists the fields it would fill, empty until then, and the fields where it gives something else (ADR 0177);
  - `Slice`: a list, newest first, with the cursor to what follows (ADR 0049);
  - `SearchKey` and `SearchKeys`: an aggregate is found by the start of its name or email, ignoring accents, case, spaces and punctuation (ADR 0071);
  - `Uuid`;
  - the business exceptions in `Domain/Exception`: `BusinessException`, `NotFound`, `BusinessRuleViolation` (naming the faulty field), `NotPossibleInItsState`, `NotAllowed`, and `ChangedMeanwhile`. The API throws `ChangedMeanwhile` when another command changed what a command changes since the command read it (ADR 0181);
  - in the application:
    - `CommandHandler`, `QueryHandler`;
    - the marker `OutputPort` for query output ports;
    - the marker `Addition` for a command that only adds and contradicts nothing its client read (a contact collected or attached). When what such a command read changed meanwhile, the API runs it again instead of refusing it (ADR 0181);
    - `EventHandler`, `PublishesEvents`;
    - the decorators `RefusalTranslatingCommandHandler`, `RefusalTranslatingQueryHandler`, `RefusalTranslatingEventHandler`, `TransactionalCommandHandler` and `TransactionalEventHandler`;
    - the `Events` port and the `UnitOfWork` port;
    - `UseCaseRefused` and its `ErrorType`;
    - `FileResponse`;
    - `ExternalLink` (a link as the use cases return it);
    - `Comparison` (a `Completion` as the queries return it, ADR 0177);
    - the `Storage` port and `FileStore`;
    - the `PrivateStorage` port (private files, ADR 0077) and its `Upload`;
    - the `GeoLocation` port and `GeoLocalizer`.

  Each context keeps its own exceptions in `Domain/Exception` (`UnknownOrganization`...).

  Each context also has its own identities, in `Domain/Model`, for the things it refers to in other contexts. It names them in its language (ADR 0197). The context that keeps the aggregate names its identity (`OrganizationId` in Prospecting, `PersonId` in PersonalData, `CampaignId` in Campaigns). Another context keeps that name when its word is the same, as `ExchangeId` does in Prospecting, Reporting and Connectors. It takes its own word otherwise (`ContactId`, `ProfileId` in Accounts, `BookerId` in Booking). A context translates another one's identity where it receives it: in the anticorruption adapter of its port, from the other context's string. Connectors and Prospecting each have a `MessageId`, an e-mail's `Message-ID` header. Both apply the same rule, so that an imported e-mail and the same e-mail an AI logs are recognized as one (ADR 0154, 0166). `tests/Domain/MessageIdTest` checks both.
- `<Context>/Application/<Intent>/`: one package per use case, CQRS (ADR 0029).
  - A command (`Relocate/Relocate`, readonly data) and its handler (`RelocateHandler`, implements `CommandHandler`, `__invoke(Relocate): void`) change the state and return nothing.
  - A query (`ConsultOrganization/ConsultOrganization`) and its handler (`__invoke(ConsultOrganization, ConsultOrganizationOutput): void`) present a response to the output port the client passes in (ADR 0085). Each query declares its port next to it (`ConsultOrganizationOutput`, extending the `OutputPort` marker). The ports of published queries extend nothing, since a Contract imports nothing. The presenter that keeps the answer as is also sits next to the query (`KeptOrganization`, `public private(set)`). It serves the clients that read the answer instead of showing it. Only queries return data.
  - What a context's intents share stays one level up (`OrganizationSheet`, `FileResponse`).
  - A query reads its read model through a port in `Application/Port` (`OrganizationSheetRetriever`, `FoundOrganizationRetriever`, `ExchangeDetailRetriever`, `TimelineRetriever`, `AgendaRetriever`, `CalendarRetriever`, `ToDoListRetriever`, `MyAccountRetriever`, `MyRemindersRetriever`, `MemberRetriever`, `PersonCardRetriever`, `ContactPageRetriever`, `DesignatedOrganizationRetriever`, `DesignatedContactRetriever`, ADR 0088). The scenarios replace it with an in-memory retriever that reads the `InMemoryDatabase`. The exceptions are `CompareWithContact` and `CompareWithOrganization`. They restore the aggregate through its repository and ask it what its completion would fill (`completionWith()`), so that the answer and the write never differ (ADR 0177, to be reviewed). Likewise, `RenderCampaignMessage` asks a campaign for its message, and `NextRecipient` asks it for the next recipient to handle (ADR 0186).

## Rules

- Pure PHP: `composer.json` requires only `php`, `ext-intl` (for `SearchKey` normalization) and `giggsey/libphonenumber-for-php` (what a phone number is, `PhoneNumber`, ADR 0072), apart from dev tools. Never add a framework or infrastructure dependency. `src` imports only PHP, the core and the vendors that `tests/Architecture/PermittedVendorsTest` names (`libphonenumber`). The core reaches the outside through ports (`Port/`, repository interfaces) implemented in `apps/api`.
- Ports have two sides.
  - A use case's primary port is how its client drives it: its handler and its request. A query's primary port also includes the output port the client passes on each call. The output port is the half of the conversation that answers the client. The core calls an `OutputPort`, but the port serves the client, not the core. The client's presenters implement it (the API's presenters, `Kept…` in the core and its scenarios). It is never replaced by a double.
  - The secondary ports are what the core asks of the outside (repositories, the retrievers in `Application/Port`, `Domain/Port`, `Storage`, `PrivateStorage`, `GeoLocation`, `Events`). They are injected once and implemented by the adapters in `apps/api`. They have working doubles in `tests/Double`.
- Names: the interface names the role, the implementation its technology (`OrganizationRepository`, `DynamoOrganizationRepository`, `InMemoryOrganizationRepository`). No `Impl`, no `Interface` suffix, no CRUD verbs in use cases (`Create`, `Get`, `Update`, `Delete`).
- A command handler is one intent. It loads its intent's aggregate, calls its intention methods with `by:` the acting member, saves it with `save()`, and returns nothing. The command carries the member's id as a string. In Prospecting, it is a `MemberId`, the author of what is recorded. In Accounts and PersonalData, where the rule depends on the member, it is the acting member or the reader. `StartProspecting` places and describes the new organization. A query handler only reads.
- A handler changes only its intent's data. Another client or a data recovery may reuse it, but never another use case. One aggregate per use case: what other aggregates or contexts must do follows from the events it recorded (ADR 0042). The one exception is the reaction that plans an appointment booked through a public link. It writes the contact to attach, whom the member met, and the exchange together (`PlanBookedAppointment`, ADR 0164).
- What a use case writes is saved all at once or not at all, in the command's unit of work (the `UnitOfWork` port, ADR 0095). This is the use case's technical atomicity, not a business transaction between aggregates. Each reaction runs after the command, in its own unit (ADR 0101). Its refusal or failure never undoes the command.
- Business rules live in the aggregates: named constructors (`Organization::prospectedBy`, `Account::open`, `Member::register`), intention methods (`relocateTo`, `removeLogo`), no setters. An invariant is checked before the change. Aggregates reference each other by identity, never by object.
- The client gives the identity of what it creates (a UUID v7 string in the command) and the moment of every command (`executedAt`). The exception is what a reaction imports without a client. An imported e-mail's exchange gets an identity that the core derives from what identifies the e-mail (`ExchangeId::ofImportedEmail()`, a version 8 UUID, ADR 0154). The intention methods take the moment (`at:`), and the aggregates keep `createdAt` and `modifiedAt` (ADR 0036).
- A rule on a set of aggregates, out of reach of any single one, is a domain service of the hexagon (ADR 0039). `EmailUniqueness` keeps an email unique among an account's persons for a purpose (PersonalData) and among an account's organizations (Prospecting). It asks the repository (`isEmailUsed()`), and the handler calls it before `save()`. Repositories and doubles answer questions; they never decide.
- The domain never crosses the application boundary (ADR 0035). Requests are flat data (strings, numbers, `\DateTimeImmutable`, `\SplFileInfo`). Responses are the same plus application data structures, built by named constructors (`FileResponse::orNone()`), with intentions by name. `tests/Architecture/ApplicationBoundaryTest` fails otherwise.
- A handler builds the value objects and implements `CommandHandler`, `QueryHandler` or `EventHandler`, with no error handling. Clients call it through the decorators of its kind. The decorators implement the same interface with the same signature (ADR 0094):
  - `RefusalTranslatingCommandHandler`, `RefusalTranslatingQueryHandler` or `RefusalTranslatingEventHandler` translates a `BusinessException` into `UseCaseRefused::because()` (type, domain message, field, the domain exception as previous). Infrastructure exceptions pass through.
  - Inside it, for a command or a reaction, `TransactionalCommandHandler` or `TransactionalEventHandler` begins the unit of work. It commits the unit when the handler succeeds, and discards it otherwise (ADR 0095). The translation sits outside, so that it also translates a refusal detected at commit time (`EmailAlreadyUsed`).
- Exceptions have named constructors (`UnknownOrganization::identifiedBy()`, `BusinessRuleViolation::because()`). A refusal that deserves its own wording has its own class, extending its kind (`InvalidPhoneNumber`, `NoLogoToRemove`), with what it is about in its context. Its class names it to the clients, and the clients word it (ADR 0073). A new one gets its French wording in `refusals` in `apps/front/src/shared/i18n/fr.ts` (the API's `RefusalsAreWordedTest`).
- Value objects are `final readonly`. Aggregates hold their value objects as they are (`private ?Address $address`). The domain knows nothing about persistence, and the API mapping adapts to it (ADR 0028, 0048).
- An aggregate has no getter for reading (ADR 0088). Its repository and the retrievers read its state by reflection (`Hydrator` in the API, `StateOf` in the tests, `AggregateStateIsReadByItsRepository` telling PHPStan). It exposes its identity, what its repository and the domain services ask for, and the questions its rules answer.
- A new property on an aggregate or a value object requires a change to its document in the API (`apps/api/src/<Context>/Persistence/*Document.php`, ADR 0048).
- A domain class is `final` unless the domain extends it (`tests/Architecture/DomainClassesAreFinalTest`).
- A list is sliced, never counted. The exceptions are:
  - an organization's exchanges to redact (ADR 0079);
  - the contacts found by tags (ADR 0143);
  - an account's campaigns, read whole with each one's target counted (ADR 0144).

  The repositories, or the read model retrievers, return a slice after a cursor and search by the aggregate's `searchKeys()`. A read model retriever is a port in `Application/Port` that reads the aggregate's table without restoring it (ADR 0088: `FoundOrganizationRetriever`).
- In Prospecting, authentication, roles and access to a resource are not the core's business (ADR 0081). The API's voters check that the organization or the exchange belongs to the member's account. They also check that the member has the right to manage personal data when an intention needs it. The core refuses a planned exchange to anyone but its holder (`NotTheHolder`, 403, ADR 0112). A request carries the id of the acting member, the author of what is recorded, so that a use case also runs without a signed-in member. What a member reads still depends on them (`Prospectors`: the flagged text, the hidden attachments). PersonalData decides what its reader sees and may do, and checks that a person belongs to the reader's account (`Readers`). Accounts checks that an account is managed by its members.
- An aggregate offers its possible intents, which the API turns into links (ADR 0027). In Prospecting, the API offers only the links that its voters also grant (ADR 0081).
  - An organization offers them through the `OrganizationPossibilities` rule, given the facts of its state. The retrievers of its sheet ask the rule too (ADR 0088).
  - An exchange offers them through `ExchangePossibilities`, given its reader and the time of reading (`ExchangeSituation`, ADR 0112). Its retriever also asks `ExchangeConformity` and `ExchangeReading` what the reader sees.
  - An account offers them through `AccountPossibilities`, given whether the member belongs to it. It offers the right to read personal data through `PersonalDataRight`, which `Member::readsPersonalData()` and the Accounts retrievers ask.
  - A person offers them through `PersonPossibilities`, given their reader, read in clear or masked as `PersonReading` says.

  Every intention method starts with the same check (`ensurePossible`). It throws `NotPossibleInItsState` for an intent that is not offered. A new intention gets a case in the intent enum, a check, and a line in `OrganizationIntentsTest`, `ExchangeTest`, `AccountIntentsTest` or `PersonIntentsTest`.
- Domain events (ADR 0042, 0043):
  - Every intention method records one event in the past tense (`recordThat(new Relocated(...))`). An event carries identities only, never personal data. It also carries the file that a reaction must remove from the storage (`LogoRemoved`, `AttachmentRemoved`, `ExchangeErased`, ADR 0101). An aggregate `implements ReleasesEvents` and `use RecordsEvents`. Its events live in `<Context>/Domain/Event` and never leave the context. So do the parents that group them by subject: Prospecting's `OrganizationEvent`, PersonalData's `PersonEvent`, Accounts' `AccountEvent`, Connectors' `ClientEvent`. A handler of a parent also receives every child added to it later. So only a publication of facts subscribes to a parent (`PublishOrganizationFactsHandler`). A reaction subscribes to each event it reacts to (`tests/Architecture/ParentEventsFeedOnlyFactsTest`). Two Connectors reactions, to `CalendarToFill` (ADR 0133) and to `MailboxToRead` (ADR 0154), are the exception until backlog #273 removes these parents.
  - The handler `use PublishesEvents` and calls `publishWhatHappenedTo($aggregate)` after saving it. The events go to the `Events` port. The translators run within the use case, the reactions after it (ADR 0101).
  - A new intention gets its event, and its intents test checks that the event is recorded.
- What another context must know is an integration event (ADR 0046). It is plain data in `<Context>/Contract/Event` (PersonalData's `ContactCollected` and `ContactForgotten`, Prospecting's `ContactsLeftWithoutOrganization`). It is built from the domain event and published by a handler of the emitting context (`PublishContactCollectedHandler`). The exception is Connectors' `EmailExchanged`, the e-mail that an import reports. The reaction that reads the e-mail publishes it (`ImportMailHandler`), so that no domain event carries the e-mail's text (ADR 0154).
- The other context reacts with an `EventHandler` (`__invoke(TheIntegrationEvent)`). It translates the event into its own words and acts through the intention methods, as the member who made the gesture. No rights are bypassed. Only integration events cross contexts (`tests/Architecture/BoundedContextsTest`).
- A Contract (`<Context>/Contract/{Service,Event}`, ADR 0047) is plain data plus the interfaces of its services (ADR 0089), and imports nothing (`tests/Architecture/ContractTest`). It is what a context promises the others: the context's contract package, deployed separately.
- A contact is never alone: it is collected for at least one organization, and a contact no organization names any more is erased. The exception is a contact to attach. Such a contact is collected without an organization for the member who met them. That member holds them until they are attached, or until they are erased 30 days after their last meeting. A platform schedule asks for that erasure with no member behind it. The erasure runs as the holder (`EraseContactLeftUnattached`, ADR 0146, 0147).
- Services (`FileStore`, `GeoLocalizer`, `EmailUniqueness`, `OrphanContacts`, `ContactOrganizations`) and the decorators (`RefusalTranslating…Handler`, `Transactional…Handler`) are real classes of the hexagon, never doubled. They are injected into the handlers, never built by them (ADR 0044). A constructor builds nothing, and the client builds the services (the API's container, `tests/Acceptance/Application` in the scenarios). `make lint` fails otherwise (`packages/phpstan-rules/DependenciesAreInjectedRule`).
- The API sends commands on a synchronous Symfony Messenger bus.
  - Every `CommandHandler` is registered on it through `RefusalTranslatingCommandHandler`, then `TransactionalCommandHandler`.
  - Every `EventHandler` is registered through `RefusalTranslatingEventHandler`, then `TransactionalEventHandler`. A translator goes on the event bus; any other reaction is a subscription in `config/subscriptions.json` (see `contexts.md`).
  - The API receives the query handlers already wrapped in `RefusalTranslatingQueryHandler`, as a `QueryHandler` named after their intent (`QueryHandler $consultOrganization`). It passes each call its output port (`$this->consultOrganization->__invoke(new ConsultOrganization($id), $presenter)`). `QueryHandler` declares its `__invoke` by `@method` only, so PHPStan checks the call as a method call, not as a callable.
  - It builds commands with named arguments, so renaming a property breaks it (PHPStan and the API tests catch it). Change the core and the API in the same commit.
- Reporting's `RebuildFigures` is the only command without `TransactionalCommandHandler`'s unit of work. Each of its steps, one per fact, commits its own (ADR 0103).
- The core knows no bus. `tests/Acceptance/Application` sends a command to its handler through the same decorators as the API. It finds the handler by the type of `__invoke()`, as Messenger does. It runs the reactions the same way, after the command and each in its own unit (`SynchronousEvents`), on an `InMemoryUnitOfWork`. It is the only place that knows how a handler is called.
- Clean code (`docs/architecture/clean-code.md`): names instead of explanatory comments, docblocks only for generics and array shapes.
