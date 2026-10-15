# Writing an Architecture Decision Record

Read this before adding or changing a record; the index is `README.md`.

## When

A decision is worth a record when it shapes the structure (where code lives, how parts talk to each other, which tool or protocol), is costly to reverse, or will surprise someone reading the code. Anything less belongs in a `CLAUDE.md` or `docs/`.

## Lifecycle

- `Proposed`: decided as a direction, not implemented yet.
- `Accepted`: in force, implemented (the record names the commits or files).
- `Superseded by NNNN`: replaced; only its status line is edited, with a link to the new record, which links back with `Supersedes NNNN`.
- `Deprecated`: no longer applies, nothing replaces it.

## Template

```markdown
# NNNN. Title stated as the decision

- Date: YYYY-MM-DD
- Status: Proposed | Accepted | Superseded by [NNNN](NNNN-....md)
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

## Steps

1. Copy the template to `NNNN-title-in-kebab-case.md`, with the next number.
2. Fill it from what was really said and weighed; leave nothing to make the template look complete.
3. Superseding: edit only the status line of the old record (`Superseded by [NNNN](...)`), and write `Supersedes NNNN` in the new one.
4. Add or update its line in the index of `README.md`.
