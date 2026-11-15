# Writing a decision record

Read this before adding a record; the index is `README.md`, the current state `docs/README.md` (ADR 0090).

## When

A decision is worth a record when it shapes the structure (where code lives, how parts talk to each other, which tool or protocol), is costly to reverse, or will surprise someone reading the code: type `architecture`. A business rule is described by its feature (`Règle:` and its scenarios, its reason in a line); it is worth a record of type `domain` only when its reason needs more: a legal reason, alternatives weighed. Anything less belongs in the document of its subject.

## The journal is immutable

- A record is written once: never edited after it is accepted, not even its status.
- A decision changing a former one is a new record, naming in its `Supersedes` line the points of the former records it replaces.
- In the same change, the new record updates the documents of its subjects (`docs/architecture/`, `docs/domain/`): they say what is in force, the records why.
- The index is written from the headers (`make adr-index`, ADR 0128): the number, the title, the type and the date of each line are those of its record, its status begins with the status of the record; a record entirely replaced is marked so on its line in the index (`Superseded by`), and a point replaced may be said after its status, its file staying as it was.

## Template

```markdown
# NNNN. Title stated as the decision

- Date: YYYY-MM-DD
- Type: architecture | domain
- Status: Proposed | Accepted
- Supersedes: for <point>, [NNNN](NNNN-....md) (only when it replaces something)
- Implemented by: commits, files

## Context
The problem, the forces and constraints at the time.

## Decision
What was decided, in a few sentences.

## Consequences
What it makes easier, what it costs, the debt it leaves.

## Alternatives considered
Only the options that were really weighed, who weighed them and why they lost. If none were, say so: never fill this section with options nobody considered.

## Revisit when
The signal that should reopen this decision.
```

`Proposed` is the only status a record changes, once, when it is implemented: before it is accepted, it is a draft.

## Steps

1. Copy the template to `NNNN-title-in-kebab-case.md`, with the next number.
2. Fill it from what was really said and weighed; leave nothing to make the template look complete.
3. Update the documents of its subjects, then `make adr-index` writes its line in the index of `README.md`; again when its status changes. `make lint` and `make ci` fail while the index differs from the headers (`make adr-index-check`).
4. `make adr-references` checks that no reference names a record entirely replaced.

Two branches adding a record each keep both lines of the index at a rebase (`merge=union` in `.gitattributes`): no conflict to resolve; `make adr-index` puts them in order if needed. Two records of the same number: the branch rebased second takes the next one, its references with it.
