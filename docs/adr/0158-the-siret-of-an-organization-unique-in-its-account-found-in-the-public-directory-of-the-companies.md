# 0158. The SIRET of an organization, unique in its account, found in the public directory of the companies

- Date: 2026-11-18
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/SharedKernel/Domain/{Siret,LegalIdentity}.php`, `packages/core/src/Prospecting/Domain/Service/SiretUniqueness.php`, `packages/core/src/Prospecting/Domain/Port/EstablishmentDirectory.php`, `packages/core/src/Prospecting/Domain/Model/Establishment.php`, `packages/core/src/Prospecting/Application/SearchEstablishments`, `packages/core/features/{reviser-l-identite,rechercher-un-etablissement}.feature`, `apps/api/src/Prospecting/Directory/RechercheEntreprisesDirectory.php`, `apps/api/src/Prospecting/Persistence/DynamoOrganizationRepository.php`, `apps/api/src/Prospecting/Controller/SearchEstablishmentsController.php`, `apps/front/src/features/organizations/components/{legal-identity-section,establishment-search}.tsx`

## Context

The name of an organization is written in a thousand ways; its SIRET does not lie. An organization of Prospecter has an address: it is an establishment, hence the SIRET rather than the SIREN, optional, a company abroad having none. Typing fourteen digits is error-prone, and the member wants it found from the name and the city, by a free service: the French State publishes the directory of the companies (« Recherche d'entreprises », recherche-entreprises.api.gouv.fr), without key nor subscription, a few requests a second.

## Decision

- The SIRET is part of the legal identity (`LegalIdentity` of the shared kernel, `Siret`): fourteen digits keyed by Luhn, the establishments of La Poste (SIREN 356000000) keyed by the sum of their digits, a multiple of 5, as INSEE numbers them; noted with the identity of an organization (`ReviseLegalIdentity`). The identity of the account's own organization takes none.
- It designates one organization of an account (`SiretUniqueness`, as `EmailUniqueness`, ADR 0039): another account may prospect the same establishment; the refusal names the organization that has it. Its reservation in the table of Prospecting (`SIRET#<account>#<siret>`, written under a condition) is the safety net of two members noting it at once.
- The establishments an organization may be are asked to the directory through a port of the domain (`EstablishmentDirectory`), named like it: those of its municipality first (the INSEE code the geocoder found, ADR 0142, the directory sharing it, arrondissements included), then those of anywhere, each once — a rule of the core. An organization without corporate name has nothing to search for. The activity shown is the NAF code of the establishment, the directory giving no label.
- The directory failing, limiting the requests or not answering makes the search unavailable for now (503, `directoryUnreachable`): the SIRET is still typed by hand.
- No double of nginx for the directory: no journey searches it (ADR 0122); the tests of the API answer it by `OutsideServices` (ADR 0092), and the stack of a checkout asks the real service.

## Consequences

- A search costs two requests to the directory when the municipality of the organization is known.
- The name of an organization is not corrected from the directory, nor its address or its legal form (out of the ticket).
- A QA walk on the local stack asks the real directory.

## Alternatives considered

- The SIRET as a property of the organization apart from its legal identity, noted by an intent of its own: it would have kept the identity of the account untouched, but split the identity the member reads and edits in one section; the ticket placed it in the legal identity.
- A double of nginx switched in while the journeys run, as for the geocoder: no journey crosses the search, so nothing would ask it.

## Revisit when

A journey must cross the search of the directory, or the directory asks a key or limits a free use.
