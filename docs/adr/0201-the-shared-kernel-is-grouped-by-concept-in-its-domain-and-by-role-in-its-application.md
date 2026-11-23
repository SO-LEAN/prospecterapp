# 0201. The shared kernel is grouped by concept in its domain and by role in its application

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Supersedes: for "the shared kernel stays flat but for `Event/` and `Exception/`", [0086](0086-the-domain-of-a-context-is-grouped-by-kind-of-building-block.md)
- Implemented by: `packages/core/src/SharedKernel/{Domain,Application}`, `packages/core/tests/Architecture/SharedKernelServesSeveralContextsTest`, `docs/architecture/core.md`

## Context

The shared kernel's domain held some 40 files flat, beside `Event/` and `Exception/`. Its application held 18 flat, beside `Port/` and `Exception/`. Nobody could tell at a glance what it held, or why. Three classes in it served Prospecting only. ADR 0197 already says that a context keeps what is its own.

## Decision

- The shared kernel holds only what several contexts share, as ADR 0197 says. A class serves a context that uses it, directly or through another class of the shared kernel. `tests/Architecture/SharedKernelServesSeveralContextsTest` fails and names a class that serves a single context. It names, as exceptions, the classes only the API uses around every context's handlers.
- The domain has a folder per concept: `Identity`, `Event`, `Exception` (the generic refusals), `Contact`, `Link`, `Company`, `Location`, `Search`, `File`, `Gesture`, `Completion`. A concept keeps its own refusals beside it.
- The application has a folder per role: `Handler`, `Port`, `Service`, `Response`, `Exception`.

## Consequences

- The folders say what the shared kernel holds. A class that serves a single context is found by the test, not by a review.
- A class added to the shared kernel needs a concept or a role.
- Only namespaces changed: the tables, the events and the API's answers stay the same.

## Alternatives considered

- The external references in `Contact`, as the ticket's example put them: they got their own `Link` folder. They have eight classes with their own refusals, and the documents already call them links to outside tools (ADR 0165).

## Revisit when

- A concept folder holds classes that no longer share a concept, or the test's exceptions grow beyond the handlers' decorators.
