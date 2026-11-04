# 0092. The tests depend on no service outside the stack: the journeys run on its doubles

- Date: 2026-11-04
- Type: architecture
- Status: Accepted
- Implemented by: `docker/nginx/conf.d/geocoder-double.conf`, `docker/lambda/deploy.sh` (`deploy geocoder`), `make e2e`, `apps/front/e2e/fixtures.ts`, `apps/front/biome.json`

## Context

A journey of `make e2e` failed once because the geocoder of the IGN answered 504 to the address of Strasbourg: the route had no start. The next run, unchanged, was green. The journeys reached two services outside the stack: the geocoder, called by the API in its Lambda, which Playwright cannot intercept, and the styles and tiles of OpenFreeMap, loaded by the maps. The tests of the API already doubled the geocoder (`.env.test`), the journey of the route OpenStreetMap (`page.route`). Locally, out of the tests, the stack must keep calling the real services, so that a developer sees the real geocoding.

## Decision

- No test depends on a service outside the stack: it doubles it, and a red test always means a defect of Prospecter.
- The double of the geocoder is a server of nginx on the Docker network only (`http://nginx:8081/search`), answering as the service of the IGN does: a known place (Strasbourg, Colmar) at a fixed position, any other address without feature, so not placed. `make e2e` points the `APP_GEOCODER_URL` of the function of the API at it before the journeys (`deploy geocoder <url>`, MiniStack recycling the containers of a function whose environment changes), and back to the service of `.env` afterwards, whatever their result; `make start` deploys the API on the real service.
- The journeys take their `test` from `e2e/fixtures.ts` (a rule of Biome refuses it from `@playwright/test` in `e2e/`): its automatic fixture serves the styles of OpenFreeMap with a style of its own, without tiles, and refuses any other host outside the stack, the journey failing afterwards with the hosts asked. A journey doubles a service it needs by its own route, which wins.

## Consequences

- The journeys give the same result each time, without the network; their positions are those of the double.
- `make e2e` changes the shared stack while it runs: a developer using https://local.prospecter.io meanwhile gets the double; a run killed without its trap leaves it, until the next `make e2e` or `make start`.
- A journey needing a new place adds it to the map of the double.

## Alternatives considered

None weighed: the ticket chose a double of the stack, the real services kept out of the tests.

## Revisit when

The journeys run in the CI, or a new service outside the stack is called by the API.
