# Clean code guide

The rules the code of this project follows, from Robert C. Martin (*Clean Code*, *Clean Architecture*, the Clean Coders videos), applied to PHP 8.5 and TypeScript. Decision: [ADR 0022](adr/0022-clean-code-conventions.md).

This guide is alive: rules get added and sharpened as the code is improved, iteration by iteration. Where the code does not follow a rule yet, the rule names it under **Known debt**: that list is the backlog of the next iterations.

## 1. Names

- **Reveal the intention.** A name says why it exists and how it is used; if it needs a comment, it is the wrong name. `Organization::possibleIntents()`, `makeRoomForThePopupAbove()`.
- **Make meaningful distinctions.** No number series (`$organization2`), no noise words (`Data`, `Info`, `Manager`, `Object`). The type does not belong in the name: `$customers`, not `$customerList`.
- **Pronounceable and searchable.** No abbreviations (`$org`, `$req`), no single letters except tiny loop counters and closure parameters read on the same line.
- **No encodings.** No Hungarian notation, no `m_` or `$str` prefixes, no `I` prefix nor `Interface` suffix on interfaces, no `Impl` on classes: the interface says the role (`OrganizationRepository`), an implementation its technology (`DynamoOrganizationRepository`, `InMemoryOrganizationRepository`). PHP types carry the type.
- **Named constants instead of magic values.** `MIN_SCORE = 0.5`, `MARKER_HEIGHT = 48`, `WHOLE_FRANCE`. A closed set of values is an `enum`, not strings or integers.
- **Classes are nouns, methods are verbs** (`FileStore::store()`); boolean methods read as questions (`isEmpty()`, `hasCorporateName()`).
- **One word per concept.** We say `find` for "may return nothing" and `get` for "throws when missing"; we do not mix `fetch`, `retrieve` and `get`.
- **Length follows scope.** The wider the scope, the longer and more precise the name; private helpers can be shorter than public API.
- **Use the business's words** (prospect, holding, owner), the solution's words (presenter, gateway, adapter) for technical code.

## 2. Functions

- **Small**, then smaller. A function rarely needs more than a few lines; blocks inside `if`, `else`, `while`, `try` are one line, a call to a well-named function.
- **Do one thing**: if a part can be extracted with a name that is not a restatement, the function did more than one thing. *Extract till you drop.*
- **One level of abstraction per function**, and read top-down: each function is followed by those it calls (*stepdown rule*). `StartProspectingHandler::__invoke()` reads as a list of steps.
- **Few arguments**: zero, one, two; three is suspicious; more needs an *argument object*. PHP named arguments make long lists readable at the call site, they do not make them acceptable.
- **No flag arguments**: a `bool` parameter means the function does two things; write two functions, or pass an `enum` when it is a real variant.
- **No output arguments**: return a value, never modify a parameter to give a result back.
- **No hidden side effects**: a function does what its name says, nothing else.
- **Command-query separation**: a method either changes state (command, returns `void`) or answers a question (query, no change), never both. A presenter receives an answer (`present()`, `void`) and is read apart.
- **Prefer exceptions to error codes**; extract the `try/catch` body into its own function, the function handling an error does nothing else.
- **`switch`/`match` on a type only once**, at a boundary or in a factory; elsewhere, polymorphism.

## 3. Comments

- **The code explains itself.** Before writing a comment, try a name: extract a function, a variable, a constant.
- **No redundant, misleading, journal or noise comments**; no docblock that repeats the signature; no commented-out code (Git keeps history).
- **What stays**: the *why* that no name can carry (a constraint of the outside world, a business reason), warnings of consequences, docblocks for what PHP cannot express (generics `list<Organization>`, array shapes, `class-string<T>`, `@template`), and tool directives (`biome-ignore`).
- A comment must be true: update or delete it with the code.

## 4. Formatting

- Automated: php-cs-fixer (`@Symfony`), Rector and Biome decide; nobody formats by hand (`make fix`).
- **Vertical order**: related code close together, a caller above its callees, newspaper metaphor (the high level first, the details below).
- **Class layout**: constants, properties, constructor, public methods; each private helper right below the first method that uses it.
- Small files: one class per file, short enough to read without scrolling much.

## 5. Objects and data structures

- **Objects hide their data and expose behaviour; data structures expose data and have no behaviour.** Do not mix both halves in one class.
- **Tell, don't ask**: ask an object to do something rather than reading its state to decide for it (`$organization->removeLogo()` refuses when there is no logo).
- **Law of Demeter**: a method talks to its own fields, its parameters and the objects it creates, not to objects returned by them. No train wrecks: `$user->getOrganization()->getId()` became `$member->accountId()` (aggregates reference each other by identity, ADR 0023).
- **Requests, responses and value objects are data structures**: `final readonly` with public properties, no behaviour except their invariants (`File` validates its URL). A response is built by a named constructor from what it answers (`OrganizationResponse::of()`), an exception too (`UnknownOrganization::identifiedBy()`).
- **Prefer immutability**: `readonly` classes and properties; a change produces a new value object.

## 6. Error handling

- **Exceptions, not return codes**, with the context needed to understand them: business refusals are domain exceptions (`Domain/Exception`: `NotFound`, `BusinessRuleViolation` naming its field, `NotPossibleInItsState`, `NotAllowed`, `EmailAlreadyUsed`, with named constructors), translated into `UseCaseRefused` and its `ErrorType` by the decorator `RefusalTranslatingHandler` around every handler, the domain exception kept as previous (ADR 0035); infrastructure failures go through untouched. Error handling is one thing: it lives in that decorator, never in the handlers.
- **Define exceptions by the caller's needs**: one type per way the caller reacts (not found, refused, conflict), not one per failure.
- **Do not return `null`, do not pass `null`** when it can be avoided: throw, return an empty collection, or a special case object. `?Type` only when absence is a real, expected answer.
- **Unchecked exceptions**: PHP has no checked exceptions; document `@throws` on ports, where implementers need it.

**Known debt**
- Aggregate accessors return `null` for absent value objects (`address()`, `logo()`): acceptable as optional data of a prospect.

## 7. Boundaries

- **Wrap third-party code** behind our own interfaces: the core sees ports (`GeoLocation`, `Storage`), never DynamoDB, Cognito, Symfony or HTTP clients.
- **The domain does not bend to a tool**: an aggregate keeps its value objects even when the ORM finds them awkward; the adapter adapts (the documents of the DynamoDB repositories, restored without the constructor, ADR 0028, 0048). No comment, property or method of the core mentions persistence.
- **Learning tests** for a library we depend on in a way we are unsure of; the adapter tests play this role (`GeoplateformeGeoLocationTest`).
- **Code that does not exist yet**: define the interface we wish we had, and adapt to the real one later.

## 8. Tests

- **The three laws of TDD**: write no production code except to pass a failing test; write no more of a test than is enough to fail; write no more production code than is enough to pass.
- **Clean tests**: as readable as production code, the domain-specific language of the tests is built on purpose (`Vocabulary`, `Expect`, the Gherkin steps).
- **One concept per test**; Arrange, Act, Assert (Given, When, Then).
- **F.I.R.S.T.**: fast, independent, repeatable, self-validating, timely (written before the code).
- **Test behaviour, never structure**: no getter and setter tests, no mocks verifying calls; only ports are doubled (ADR 0017, `docs/core-tests.md`).

## 9. Classes

- **Small**, measured in responsibilities, not lines. A class described with "and", "or", "but" has more than one.
- **Cohesive**: most methods use most fields. When a subset of fields serves a subset of methods, it is another class waiting to be extracted (`FileStore` and `GeoLocalizer` came out of the use cases).
- **Organized for change**: adding a feature adds a class rather than editing many.
- **Dependencies are injected, never built** (ADR 0044): a constructor only receives what the class needs, and builds nothing but the exception it throws; a service is built by the container (the composition root of the tests in the core), never by the code. `make lint` fails otherwise (`packages/phpstan-rules/DependenciesAreInjectedRule`).
- **A service is stateless** (an in-memory cache aside); **an object keeping the state of one call is no service** (ADR 0084): built where it is used, it may receive the services of its builder. A presenter is one: it implements the output port of a query, keeps its view model, and its client reads it once the query answered (`$presenter->resource`); the rule allows building a class implementing `OutputPort`.

## 10. SOLID

- **Single responsibility**: a module has one reason to change, i.e. answers to one actor. Each use case serves one intent; presenters change with the API format, not with the rules.
- **Open/closed**: extend behaviour by adding code (a new adapter behind a port, a new use case), not by editing working code.
- **Liskov substitution**: an implementation honours the contract of its interface, including its exceptions; the in-memory doubles behave like the DynamoDB adapters, or the scenarios lie.
- **Interface segregation**: clients depend only on what they use: a repository offers the queries its use cases need (`sliceProspectedBy`, `ofIds`), no generic finder.
- **Dependency inversion**: high-level policy does not depend on details; both depend on abstractions owned by the policy (ports in the core, adapters in the API).

## 11. Architecture and components

- **The dependency rule**: source code dependencies point inwards, towards the business rules. The core knows nothing of the API, the API nothing of the front (ADR 0005).
- **Policy and details**: the database, the web, the frameworks are details; the business rules do not know them. Use cases are the application's policy, entities the enterprise's.
- **Boundaries cross with simple data**: requests and responses are plain data structures, never entities, value objects or framework objects (ADR 0035, guarded by `ApplicationBoundaryTest` in the core and `DomainBehindTheApplicationTest` in the API).
- **Component cohesion**: classes released together are reused together (REP), change together (CCP), and nobody depends on what they do not use (CRP).
- **Component coupling**: no cycle between packages (ADP); depend in the direction of stability (SDP): the front and the API depend on the stable core; the more stable a component, the more abstract it is (SAP): the core exposes ports.
- **Screaming architecture**: the folders say what the application does (`Prospecting/Application/StartProspecting`, `Accounts/Domain/Account`, `features/organizations`): grouped by bounded context first, by layer inside (ADR 0030), not which framework it uses.
- **Keep options open**: decisions on frameworks, databases and delivery are deferred and isolated behind boundaries (ADR 0029, 0048, 0052).

## 12. Simplicity

- **Kent Beck's simple design**, in order: passes all the tests, reveals intention, no duplication, fewest elements.
- **DRY, with judgement**: remove real duplication of knowledge; leave accidental duplication alone (two codes that look alike but change for different reasons, such as `OrganizationResponse` and `FoundOrganization`, an organization for its page and for a list).
- **YAGNI**: no code for a need that does not exist yet; dead code is deleted (the old `Customer`, `Call`, `Email` and `Event` entities were).
- **KISS**: the simplest design that passes the tests.

## 13. Discipline

- **The boy scout rule**: leave the code a little cleaner than you found it, in every change.
- **Refactor continuously, under tests**: small steps, the scenarios green after each.
- **No broken windows**: lint, analysis and tests pass before a commit (`make lint`, `make test`).

## PHP and TypeScript specifics

- PHP: native types everywhere, `readonly` classes and promoted properties, `enum` for closed sets, `match` instead of `switch`, first-class callables (`$this->allowsInItsState(...)`, `FoundOrganization::of(...)`), `never` and `throw` expressions, strict types in the core, PHPStan level 5 (ADR 0004).
- TypeScript: strict mode, no `any`, named functions for intentions (`isClientError`, `nextSliceOf`), no manual memoization (the React Compiler does it), components small and composed.
