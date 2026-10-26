# 0082. A local function serves as the owner of the mounted repository

- Date: 2026-10-26
- Status: Accepted
- Implemented by: `docker/lambda/bootstrap`
- Supersedes: [0053](0053-the-api-in-a-local-lambda-with-the-bref-layer.md), how the function writes in the repository

## Context

0053 has the local `bootstrap` write with a `umask` of `0000`, so that what the function writes as root in the shared cache stays writable by the user of the `php` container. But the function only starts as root: PHP-FPM of the layer of Bref serves as `nobody` (`/opt/bref/etc/php-fpm.conf`, read at that path), who cannot write `apps/<app>`. `Kernel::isLambda()` then holds the function for a read-only Lambda of AWS, and Bref moves its cache and its logs to its own `/tmp`: each warm function had its own cache, and the profiler of the API its own history, lost with the function (ticket 31).

## Decision

Before starting Bref, the local `bootstrap` sets the user and the group of PHP-FPM to those of the owner of the mounted code, the user of the `php` container. The functions write in the repository the cache and the logs of the dev environment, shared with the command line, as 0053 meant; the `umask` of `0000` goes.

## Consequences

- The cache of dev is warmed once for every function and the command line; what the functions write belongs to the user of the host.
- The bootstrap edits the configuration of PHP-FPM of the layer in the function; a version of Bref that moves it or names its user otherwise needs the bootstrap changed.
- Files written as `nobody` before stay in `var/`, writable by everyone.

## Alternatives considered

- Leave the functions their cache in `/tmp` and take only the profiles out of it, in `var/profiler`: proposed first in ticket 31; the user wanted the functions to write in the repository, as 0053 meant.

## Revisit when

A version of Bref lets the user of PHP-FPM be configured, or the local functions stop mounting the repository.
