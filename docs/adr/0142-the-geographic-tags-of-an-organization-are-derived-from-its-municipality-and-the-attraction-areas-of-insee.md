# 0142. The geographic tags of an organization are derived from its municipality and the attraction areas of INSEE

- Date: 2026-11-17
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/Campaigns/Domain/Policy/GeographicTags.php`, `packages/core/data/insee-geography.php` and `packages/core/bin/insee-geography.php`, `packages/core/src/Prospecting/Application/LocateAgain`, `packages/core/features/porter-les-tags-de-l-adresse-de-mes-organisations.feature`, `apps/api/src/Shared/GeoplateformeGeoLocation.php`, `apps/api/src/Prospecting/Command/LocateOrganizationsAgainCommand.php`, `apps/front/src/features/tags`

## Context

A prospector targets their contacts by the big city around their organizations, their department and their region (#STRASBOURG #67 #GRAND_EST), automatic tags ADR 0138 reserved. The nearest town above a population would name Montreuil or Boulogne-Billancourt instead of Paris. The geocoder (IGN Géoplateforme, ADR 0014) answers with the INSEE code of the municipality of each address it finds, that of the arrondissement in Paris, Lyon and Marseille, and finds « Rue de la Loi 16, Bruxelles » in Nantes when no country is said. The tags of a contact are derived at the reading from the facts Campaigns keeps (ADR 0141).

## Decision

- An organization keeps the municipality code its geocoding returned, next to its position (`Location`, what the port `GeoLocation` gives), and publishes it with the country of its address in its facts: `OrganizationFactsChanged.v3` and the service of its facts.
- Its geographic tags are derived from that code only, at the reading, never by geocoding again, so that they are rebuilt from the state: the attraction area of the cities (INSEE zoning AAV 2020) of 50,000 inhabitants or more the municipality belongs to, its department (the beginning of its code) and its region. A municipality outside such an area, or more recent than the tables, gets its department and its region only.
- A tag is the label of the area without « (partie française) », or of the region, upper case, without accents, every other run of characters an underscore, a double name kept whole (#SAINT_ETIENNE, #MARSEILLE_AIX_EN_PROVENCE, #GENEVE_ANNEMASSE); the department is its code (#67, #2A, #971). The names of the areas and of the regions are reserved like the other automatic tags.
- An address whose country is given and is not French (FR, GP, MQ, GF, RE, YT) gets no geographic tag, whatever the geocoder found.
- An organization carries them as automatic tags; its contacts inherit them like the tags put on it by hand; the account lists those its organizations carry, to search by, apart from the tags proposed to put by hand.
- The tables are a PHP file generated from the files INSEE publishes (Licence Ouverte 2.0, its source cited) by a script of the repository, never edited by hand, regenerated each year: it maps the municipal arrondissements of Paris, Lyon and Marseille to their municipality, and keeps the codes of the municipalities merged since 2020 in the area of their last geography.
- The organizations located before are given their code once, by a command geocoding their address again (`app:organizations:locate-again`).

## Consequences

- No service is called to derive or rebuild a tag; the tables weigh some 300 KB, kept in memory by OPcache.
- A municipality created after the tables gets its department and its region only until they are regenerated.
- Cross-border areas are named after their foreign pole (#BALE_SAINT_LOUIS, #LUXEMBOURG).
- Until `app:organizations:locate-again` then `app:tags:rebuild` run on an instance, its organizations carry no geographic tag.

## Alternatives considered

Weighed by the spike of the ticket, its recommendations validated by the maintainer:

- The nearest town of 50,000 inhabitants or more: Boulogne-Billancourt or Montreuil instead of Paris.
- Every attraction area, whatever its size: 512 areas below 50,000 inhabitants nobody targets, and two areas named Valence.
- A municipality outside an area attached to the nearest area: a distance blind to relief and departments, with no official definition.
- Shortening the double names (#MARSEILLE, #GENEVE): a list of exceptions to maintain.
- Geocoding again at each rebuild: the rebuild would depend on a service outside the state. Reverse geocoding from the position: a second port for a single use.

## Revisit when

INSEE publishes a new zoning of the attraction areas, or the prospectors target addresses outside France.
