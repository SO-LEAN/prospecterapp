# 0188. The repository is written in plain technical English

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Supersedes: for "the `CLAUDE.md` of a project keeps what a session needs at once: its role, its commands, its traps", [0090](0090-the-current-state-lives-in-documents-the-records-are-a-journal.md): the root `CLAUDE.md` keeps the everyday commands only, and the full reference lives in `docs/architecture/commands.md`
- Implemented by: `docs/architecture/clean-code.md` (section 14), `CLAUDE.md`, `docs/architecture/workflow.md`, `docs/architecture/commands.md`

## Context

ADR 0017 made English the language of the code and the commits. The grammar and the vocabulary of the repository are fine, but its syntax is French: chains of `of`, French word order, nested relative clauses, commit subjects written as statements, a few false friends. Agents write like what they read, so every new branch spread that style. The root `CLAUDE.md` also described every console command inline, which costs context in every session.

## Decision

- Prose (docs, docblocks, new records) is plain, idiomatic technical English: short sentences, active voice, possessives and compound nouns rather than chains of `of`.
- Commit subjects follow the conventional format, in the imperative, 72 characters at most, with the why in the body.
- Names in code use the words an English-speaking developer would use, in English word order.
- A table of false friends lists what to avoid; it grows with the reviews.
- The boy scout rule covers text: a branch that touches a doc, a docblock or a name moves it to this style. Code is renamed only when a branch touches it, never in a global pass.
- The rule lives in `docs/architecture/clean-code.md`. The root `CLAUDE.md` states it in short and `docs/architecture/workflow.md` links to it.
- The `CLAUDE.md` files and `docs/architecture` are rewritten in this style, wording only. The command reference leaves the root `CLAUDE.md` for `docs/architecture/commands.md`.

## Consequences

- What agents read first no longer teaches them French syntax.
- `README.md`, `docs/README.md` and `docs/domain` are rewritten next. The records stay as written, an immutable journal; the Gherkin scenarios stay in French; the git history stays as it is, so its `Refs` keep working.
- Code names such as `ConsultHeldExchangesOutput` or `civilityTitle` stay until a branch touches them.
- A session reads the console commands in `docs/architecture/commands.md` when it needs them.

## Alternatives considered

- `make help` as the command reference: its one-line descriptions cannot hold the ADR references nor the order of the rebuild commands.

## Revisit when

The reviews keep finding French syntax in new text, or the false-friend table grows too long to read.
