# 0086. The domain of a context is grouped by kind of building block

- Date: 2026-10-28
- Status: Accepted
- Implemented by: `packages/core/src/{Accounts,PersonalData,Prospecting}/Domain/{Model,Repository,Service,Policy,Port}`, the ports `Readers` and `Prospectors`, their adapters `AccountsReaders` and `AccountsProspectors` (`packages/core/tests/Double`), `apps/api/src/{PersonalData,Prospecting}/AntiCorruption`

## Context

The `Domain` of a context kept its events and its exceptions in `Event/` and `Exception/`, and everything else flat: aggregates, value objects, enums, repository interfaces, domain services, ports to the other contexts. Opening it, Michel could not see which classes were services; `Masking` fitted none of the folders, and the ports `ReaderService` and `ProspectorService` were named like services.

## Decision

- The `Domain` of a context is grouped by kind of building block, as `Event/` and `Exception/` already were:
  - `Model/`: aggregates, entities, value objects, enums (the intents among them);
  - `Repository/`: the interfaces of the repositories;
  - `Service/`: the domain services, a rule on a set of aggregates (ADR 0039);
  - `Policy/`: a pure rule without state (`Masking`);
  - `Port/`: what the context asks the others, implemented by an anticorruption layer (ADR 0045);
  - `Event/` and `Exception/`.
- In the API, the anticorruption layers implementing the ports of a context are grouped in `src/<Context>/AntiCorruption`, as its repositories are in `src/<Context>/Persistence`: the class names the context it reads (`AccountsReaders`), the folder the pattern, never a suffix.
- A folder exists only when it holds something. The shared kernel stays flat but for `Event/` and `Exception/`: it holds model only.
- The ports `ReaderService` and `ProspectorService` become `Readers` and `Prospectors`, their adapters `AccountsReaders` and `AccountsProspectors`: in `Port/`, the suffix `Service` would read as a domain service.

## Consequences

- Opening the `Domain` of a context shows what it decides (`Service/`, `Policy/`) and what it asks the others (`Port/`); opening a context of the API shows its anticorruption layers.
- The same layout in every context, whatever its number of aggregates.
- A class of the model now imports the repositories and services it names, and the other way round: the dependencies inside a domain are visible.

## Alternatives considered

- Grouping by aggregate (`Domain/Organization/`, `Domain/Exchange/`), as Evans advises for modules, where a context has more than one aggregate: Michel and Claude weighed it; it scatters the services among the aggregates, needs a common folder for what crosses them (`Prospector`, `TimelineIntent`, the ports), gives two layouts in the core (PersonalData has one aggregate), and a second aggregate would reorganize a whole context.

## Revisit when

A context grows so many classes in `Model/` that its aggregates no longer stand out.
