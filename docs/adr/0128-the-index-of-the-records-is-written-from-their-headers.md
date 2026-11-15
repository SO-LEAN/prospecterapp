# 0128. The index of the records is written from their headers and merged by union

- Date: 2026-11-15
- Type: architecture
- Status: Accepted
- Implemented by: `make/adr-index.sh`, `Makefile` (`adr-index`, `adr-index-check` in `lint` and `check-secrets`), `.gitattributes`, `docs/adr/README.md`, `docs/adr/TEMPLATE.md`, `docs/architecture/workflow.md`

## Context

Each branch taking a decision added its line at the end of the table of `docs/adr/README.md`. Two branches open at once, their records touching nothing in common, conflicted on that last line at every rebase, and the developers working in parallel resolved it by hand each time. Nothing checked either that a line said what its record says: six titles or dates of the index differed from their headers.

## Decision

- The index is written from the headers of the records by `make adr-index` (`make/adr-index.sh`): one line per record, in the order of the numbers, its title, its type and its date those of its header. The status of a line begins with the status of its header, or says that a later record superseded it: what the journal, immutable (0090), cannot write in the record itself; the index keeps that part of the line. A record written before the type was asked keeps the type of its line.
- `make adr-index-check`, part of `make lint` and of `check-secrets` (always run by `make ci` and the CI), fails while the index differs from the headers, as the contract does from the code (0008).
- `.gitattributes` merges the index by union (`merge=union`): at a rebase, the lines both branches added are kept, without conflict; `make adr-index` puts them back in order when needed.

## Consequences

- Adding a record or changing its status is followed by `make adr-index`; a forgotten one fails the CI.
- The union merges what both sides wrote without judging it: two branches changing the same line keep both, which the check then reports as a duplicate or a mismatch to settle by hand.
- The merge by rebase of GitHub may not apply the union; a pull request it says in conflict on the index alone is rebased locally, where the union applies.

## Alternatives considered

An index without a common last line, written by hand, was weighed in the ticket: it removes the conflict but checks nothing, and the lines had already drifted from their records. The generated index also lets the check catch a status left behind.

## Revisit when

Two branches change the same line of the index often, or GitHub refuses merges the local rebase accepts.
