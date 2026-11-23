# 0200. A policy is a reaction, as in event storming; a pure rule of the domain is a Rule

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Supersedes: for the `Policy/` folder of a context's `Domain`, [0086](0086-the-domain-of-a-context-is-grouped-by-kind-of-building-block.md)
- Implemented by: `packages/core/src/*/Domain/Rule`, `docs/architecture/core.md`

## Context

Each context's `Domain/Policy` held 28 pure, stateless rules: what an aggregate's state offers (`OrganizationPossibilities`), the rights and the reading of personal data (`PersonalDataRight`, `Masking`), and calculations (`FreeSlots`, `Lateness`). None of them triggers anything. This is the meaning Evans's book gives to "policy": a rule or a strategy the domain applies.

Event storming gives the word another meaning: when an event happens, and if a condition holds, then a command runs. The core is about to get such reactions, in `<Context>/Application/Policy`. With `Domain/Policy` still there, one word would name two different things.

## Decision

- "Policy" takes the event storming's meaning: a reaction to an event that runs a command.
- A pure, stateless rule of the domain is a `Rule`, in `<Context>/Domain/Rule`. Each class kept its name, except `MemberPresentationPolicy`, which became `MemberPresentation`.

The event storming's meaning wins because the reactions need a name and the team models with event storming, where the word has only that meaning. A pure rule needs no special word.

## Consequences

The records before this one still say `Domain/Policy`. `docs/README.md` says where those classes are now. Someone who knows Evans's book will read "policy" differently, and `docs/architecture/core.md` says which meaning holds.

## Alternatives considered

None: the maintainer chose the event storming's meaning when the reactions were planned.

## Revisit when

The reactions get another name, or a pure rule needs to be told apart from another kind of domain rule.
