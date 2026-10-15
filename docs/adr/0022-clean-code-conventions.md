# 0022. Clean code, as taught by Robert C. Martin, applied to PHP and TypeScript

- Date: 2026-10-13, completed on 2026-10-15
- Status: Accepted
- Implemented by: 2026 upgrade, 58eb5b5, dbaaffe; rules in [docs/clean-code.md](../clean-code.md)

## Context

The 2018 code had docblocks repeating types and "Class Foo." comments. Code written during the 2026 rewrite, by Claude Code, explained itself with inline comments; the user asked for names instead, then for the whole project to follow clean code as taught in Robert C. Martin's Clean Coders videos and books. The project is being refactored, and the user improves the code iteration by iteration.

## Decision

- The code follows Robert C. Martin's *Clean Code* and *Clean Architecture*, applied to PHP 8.5 and TypeScript: names, functions, comments, formatting, objects and data structures, error handling, boundaries, tests, classes, SOLID, component principles, the dependency rule, simple design, the boy scout rule.
- The rules live in `docs/clean-code.md`, a living guide: they are sharpened as the code improves, without a new record for each. This record changes only if the reference itself changes.
- The guide lists, rule by rule, where the code does not follow it yet (**Known debt**): it is the backlog of the improvement iterations.
- Tooling enforces what it can (native types by Rector, PHPStan level 5, php-cs-fixer, Biome); the rest is reviewed.

## Consequences

- One reference for people and for Claude Code (pointed to by the root `CLAUDE.md`, read when writing or reviewing code).
- Known debt at the time of writing: requests with up to ten arguments, an anemic entity model with getters and setters, train wrecks, a command-query violation in `Passwords::verify()`, a hidden side effect in `Organization::setOwnedBy()`, a fat `UserNotifier` port, `null` returned by entity getters, the magic `__call` of `UseCasesFacade` (ADR 0020).
- Some reasons invisible in the code have to fit in a name, a docblock on a class or a port, or a record like this one.

## Alternatives considered

None were weighed: these are the user's rules for the code. A summary of clean code rules produced by another assistant was used as a starting point and completed with what it missed (command-query separation, stepdown rule, error handling, objects versus data structures, tests, component principles).

## Revisit when

Never as a principle; the guide is updated at each iteration, and the tooling that enforces it may change.
