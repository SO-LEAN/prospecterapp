# 0022. Clean code: names instead of comments, native types

- Date: 2026-10-13
- Status: Accepted
- Implemented by: 2026 upgrade, 58eb5b5, dbaaffe; root `CLAUDE.md`

## Context

The 2018 code had docblocks repeating types and "Class Foo." comments. Code written during the 2026 rewrite, by Claude Code, explained itself with inline comments; the user asked for names instead.

## Decision

- Native types everywhere; docblocks only for what the language cannot say (generics, array shapes, `class-string`).
- No comment that repeats a type or explains the code: the intention gets a name (a function, a constant, a variable); short methods, one level of abstraction each. A class or port docblock may state a reason that no name can carry.
- Readonly DTOs and constructor promotion in PHP; strict TypeScript without `any`.
- Enforced partly by Rector, php-cs-fixer and Biome; the rest by review.

## Consequences

- Code reads like its intent; refactoring keeps names and code together.
- Some reasons that are not visible from the code have to fit in a name, a docblock, or a record like this one.

## Alternatives considered

None were weighed: these are the user's rules for the code.

## Revisit when

Never as a principle; the tooling that enforces it may change.
