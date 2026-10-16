# 0054. Locally, the storage runs in a Lambda too, with the imagick layer of Bref

- Date: 2026-10-16
- Status: Accepted
- Extends: [0053](0053-the-api-in-a-local-lambda-with-the-bref-layer.md)
- Implemented by: `docker/lambda/{Dockerfile,deploy.sh}`, `docker/nginx/runtime/{fpm,lambda}/storage.conf`, `docker/nginx/templates/static.conf.template`, `bref/bref` in `apps/storage`

## Context

`make lambda` runs the API in a Lambda with the layer of Bref on MiniStack (0053). The storage app, which resizes the images with ImageMagick (0033, 0051), still ran on PHP-FPM; the user wants it in a Lambda as well, nginx keeping locally only the part Cloudflare will play: https and the hosts. The layer of PHP of Bref has no imagick; Bref publishes it as a layer of its own (`bref/extra-imagick-php-85`).

## Decision

- `make lambda` deploys a second function, `prospecter-dev-storage`, the same way as the API: the mounted `apps/storage`, the dev environment, behind an HTTP API of its own (`prospecter-dev-storage`, stage `local`), without CORS (its images are plain GETs, the static host adds its own header).
- Its layers: the one of PHP and the one of imagick (the `/opt` of `bref/extra-imagick-php-85`), each published once per version; `BREF_BINARY_RESPONSES=1`, Bref sending the images it makes base64-encoded only when asked.
- Both entries of the app go to it through one runtime file of nginx (`RUNTIME`, `fpm` or `lambda`, which chooses for the API as well): the size missing from the bucket on the static host, and `/files` of the internal server where the API stores and removes files.

## Consequences

- In the Lambda mode no request of the application reaches PHP-FPM; the tests of the storage stay on PHP-FPM.
- A new extension the storage needs comes as a layer of Bref, or enabled in a `php/conf.d` of the app.

## Revisit when

- The storage gets its production setup (Cloudflare in front of the bucket, 0051): the static host of nginx is what Cloudflare then replaces.
