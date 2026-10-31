# 0090. The current state lives in documents by subject; the records are an immutable journal, typed architecture or domain

- Date: 2026-10-31
- Type: architecture
- Status: Accepted
- Supersedes: for how a decision is recorded, kept and read, [0001](0001-record-architecture-decisions.md)
- Implemented by: `docs/README.md`, `docs/architecture/`, `docs/domain/`, the index `docs/adr/README.md`, `docs/adr/TEMPLATE.md`, the `CLAUDE.md` files, `make adr-references` (part of `make lint`), and the references to the records replaced in part, verified one by one

## Context

A decision was recorded by a record, and a later decision changing part of it edited its status line ("Accepted, X superseded by …, Y by …"). Forty of the eighty-nine records in force were superseded or specified in part: the rule in force on a subject was written nowhere in one place, and reading it meant following a chain of records. The index of the records, loaded in every session by the root `CLAUDE.md` (20 KB), grew with each decision, and the `CLAUDE.md` of the projects repeated the rules in force at length (24 KB for the API, 21 KB for the core), loaded as soon as a file of the project is touched. An agent reading a superseded point could follow it. The records also mixed architecture and business decisions, the business rules being described by the scenarios of the core besides. Michel wants the agents of development and review to read only what is in force, the history kept but not read by default.

## Decision

- **The current state lives in documents by subject**: `docs/architecture/<subject>.md` (the core, the contexts and what they publish, the persistence, the API, the authentication, the files, the front, the local stack, the infrastructure, the monorepo, the workflow, clean code, the tests) and `docs/domain/<context>.md` (the language, the rules and the invariants of a context, and the features describing them). A document says the rule in force, without its history, and names the records that justify it. `docs/README.md`, short and stable, lists the subjects: the root `CLAUDE.md` loads it instead of the index of the records.
- **The `CLAUDE.md` of a project keeps what a session needs at once**: its role, its commands, its traps, and the documents of its subjects; the rules are in the documents, read when the subject is worked on.
- **The records are an immutable journal**: a record is never edited once accepted, not even its status. A new record changes the documents of its subjects in the same change, and names in its `Supersedes` line the points of the former records it replaces; the documents are what is in force. The index lists number, decision, type and date, and a record entirely replaced as such; it is read, with the records, only to know why a rule is, or before changing it.
- **A record is typed** `architecture` (the structure, a protocol, a tool) or `domain` (a business rule with a real trade-off: a legal reason, alternatives weighed). The records written before get their type in the index, once. A business rule itself is described by its feature (`Règle:` and its scenarios), its reason in a line of the rule; a record of type `domain` only when its reason needs more.
- **A reference to a record is kept** in the code and the documents, as the reason of what it cites; `make adr-references` fails when a reference names a record entirely replaced. The references to the records replaced in part were verified once, each pointing to the record in force for what it cites.

## Consequences

- An agent reads the list of the subjects, then the document of its subject; it reads the records only to know why, or before changing a decision.
- A decision costs a record and the update of the documents of its subjects; nothing else is edited, the index but for its line.
- The documents must be kept true: a document contradicting the code is a defect, like a failing test.
- The history of a subject stays in the records, in the order they were made; the status lines of the records before this one keep their partial supersessions as they were written.
- The documents of `docs/` moved to `docs/architecture/` (`docs/core-tests.md` became `tests.md`, `docs/front-architecture.md` became `front.md`); the records before this one name their former paths, which `docs/README.md` lists.

## Alternatives considered

- Keeping the `CLAUDE.md` of the projects as the documents in force: they are loaded as soon as a file of their project is touched, whatever the subject; Michel preferred documents read by subject.
- Replacing the references to the records by references to the documents: about four hundred lines in the code for a less precise reason; Michel kept the numbers, checked.
- Two journals, one for architecture and one for the domain: the records of the domain already numbered would move and their references break; Michel chose one journal, typed.

## Revisit when

- A document grows past what an agent reads at once: split it by subject.
