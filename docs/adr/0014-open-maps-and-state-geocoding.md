# 0014. Open maps (MapLibre, OpenFreeMap) and the French State geocoder

- Date: 2026-10-14
- Status: Accepted
- Implemented by: c8bcf16, 5c2e73a, 9b577cb; `apps/api/src/Gateway/GeoplateformeGeoLocation.php`, `apps/front/src/shared/ui/map`

## Context

Organizations are shown on a map. The 2018 application used Google Maps for both the map and geocoding, with an API key; the placeholder key made every geocoding fail silently.

## Decision

- Map: MapLibre with OpenFreeMap vector tiles (no key), loaded only on pages that show a map. Markers open a quick look of the organization.
- Geocoding: the French State service, IGN Géoplateforme (Base Adresse Nationale), free and without key, behind the core's `GeoLocation` port. A best match under a 0.5 score is ignored; an unavailable service leaves the organization without position instead of failing the save.

## Consequences

- No key, no account, no cost.
- Only addresses in France are located; others are saved without position.
- MapLibre 6 needs its worker URL given explicitly under Vite (`setWorkerUrl`).

## Alternatives considered

- Keeping Google Maps for the map (needs a valid API key): MapLibre + OpenFreeMap was chosen.
- For geocoding, OpenStreetMap's Nominatim was offered after Google proved unusable without a key; the user chose the French State service instead.

## Revisit when

Organizations outside France matter (add a geocoder behind the same port), or OpenFreeMap's availability is not enough for production.
