# 0184. A fact rebuilt by Campaigns replaces the kept one at the same revision

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Supersedes: for the succession the rebuild records by, [0141](0141-the-tags-of-a-contact-are-derived-from-the-facts-campaigns-keeps-rebuilt-without-versions.md)
- Implemented by: `packages/core/src/Campaigns/Domain/Policy/FactSuccession.php`, `packages/core/src/Campaigns/Application/FactRecorder.php`, `packages/core/src/Campaigns/Application/RebuildTags/RebuildTagsHandler.php`

## Context

ADR 0141 has `app:tags:rebuild` record the facts again through the succession of the worker: a fact replaces the kept one only at a higher revision. The rebuild gives each fact at the revision its aggregate has now, so a fact kept at that revision is ignored whatever it lacks. When the facts grew the locality of an organization (ADR 0142), the organizations whose fact was kept before kept no geographic tag: `app:consistency:check` reported them and proposed `app:tags:rebuild`, which changed nothing; on dev, their revision had to be raised by hand.

## Decision

A fact rebuilt from the state of its context replaces the kept one at the same revision too (`FactSuccession::replacesAsRebuilt`, `FactRecorder::recordAgain`). The rest of the succession holds for it: the terminal fact ends the aggregate for good, and a higher revision recorded meanwhile by the worker is kept. The worker keeps its rule: a fact delivered again or late, at the same revision or lower, replaces nothing.

## Consequences

- A rebuild corrects a fact kept before the facts carried what they carry now, without a new version of the event nor a change of the aggregate.
- A rebuild writes every fact again, unchanged ones included, each under the condition of the one it replaces.
- A recording of the worker and one of the rebuild still commute: at the same revision both describe the aggregate in the same state, the rebuild with what the facts carry now.

## Alternatives considered

The ticket named two ways: a flag on the recording, or a use case of rebuild apart. The second was kept, as a succession of its own beside the worker's in the same policy, the rebuild alone using it, so that no recording of the worker carries a flag it must not set.

## Revisit when

Another context rebuilds facts by the worker's succession (Reporting rebuilds in a new version, ADR 0106, and needs none).
