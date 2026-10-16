# 0004. PHP 8.5 and Symfony 8.1, native types, PHPStan level 5

- Date: 2026-10-13
- Status: Accepted, the Doctrine mapping superseded by 0048
- Implemented by: `docs/upgrade-2026.md`

## Context

The project had not been touched since 2021 (PHP 7.2, Symfony 4.4, webpack-encore on Node 9, Travis). Installing it again failed on dead endpoints before any code ran.

## Decision

- Upgrade straight to PHP 8.5 and Symfony 8.1: fresh Symfony recipes, then the application configuration ported, rather than every intermediate major version.
- Doctrine mapping in XML (YAML removed by ORM 3; the entities belong to the core, so no attributes in them).
- Native types everywhere, applied by Rector and checked by PHPStan level 5. Level 6 would require iterable docblocks on every array, against the rule of no type comments (0022).
- PHPUnit 13 with attributes.

## Consequences

- The codebase uses current PHP (readonly classes, promotion, `match`, enums when needed).
- Symfony 8.1 is supported until January 2027: the next upgrade has to be planned.
- Rector output must be reviewed: wrong docblocks became wrong native types.

## Alternatives considered

- PHP 8.4 + Symfony 7.4 LTS, supported until November 2029, was offered (and recommended); PHP 8.5 + Symfony 8.1 was chosen, accepting regular upgrades.
- Upgrading through every major version (4.4 → 5.4 → 6.4 → 7.4 → 8.1) instead of fresh recipes: judged more expensive for this small codebase (`docs/upgrade-2026.md`).
- PHPStan level 6: it requires iterable docblocks everywhere, against the rule of no type comments (0022).
- For the assets, before the SPA replaced them: webpack-encore kept with Bootstrap 4, the same with Bootstrap 5, or AssetMapper without Node (chosen then, removed by 0006).

## Revisit when

Symfony 8.1 nears its end of support (January 2027), or PHPStan can express collection types without docblocks.
