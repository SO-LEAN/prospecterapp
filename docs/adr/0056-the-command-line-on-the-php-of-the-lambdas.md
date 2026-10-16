# 0056. The command line runs on the PHP of the Lambdas

- Date: 2026-10-16
- Status: Accepted
- Implemented by: `docker/php/Dockerfile`, `docker/php/php.ini`

## Context

Locally the API and the storage run only in their Lambdas, on the PHP of the layer of Bref (0055). The `php` container, which runs the tests, Composer, the console and the tools, was built on the official PHP image with its own extensions: the code was tested on another PHP than the one running it. The user asked for the two to be coherent.

## Decision

- The `php` container is built on `bref/php-85-dev`, the PHP of the layer of the functions with Xdebug and Composer, and gets the layer of imagick of the storage function; git and unzip are added, and the user of the host.
- Its `php.ini` (over the `bref.ini` of Bref): the extensions the apps enable in their functions (`intl`), Xdebug loaded but off, the errors on stderr, and OPcache in memory rather than the file cache Bref sets for its read-only Lambdas, shared by the parallel workers of Rector and PHPStan.
- The versions of Bref are pinned the same in `docker/php/Dockerfile` and `docker/lambda/Dockerfile`: a new version is changed in both.

## Consequences

- The tests and the tools run on the PHP that serves the requests, extensions included.

## Alternatives considered

- Keeping the official PHP image for the command line: the user wants the PHP of the Lambdas.
