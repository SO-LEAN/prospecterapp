# Reporting

Each account's figures, kept from the facts the other contexts publish, and counted again from their state when needed (ADR 0103). Code: `packages/core/src/Reporting`.

## Language

- **Figures**: what an account counts:
  - the organizations it prospects (`activeOrganizations`);
  - those placed on the map (`locatedOrganizations`);
  - its contacts (`contacts`, the persons it keeps for its prospecting);
  - the exchanges it keeps that were **held**, done or missed (`exchanges`);
  - those held in the current month (`exchangesThisMonth`).

  An exchange still to come, or cancelled, does not count.
- **Fact** (`Fact`: `OrganizationFact`, `ExchangeFact`, `ContactFact`): the state of another context's aggregate as Reporting counts it, with its revision. A **terminal** fact (absent) ends the aggregate (ADR 0099).
- **Change** (`FiguresChange`): what each of an account's figures gains or loses, with the exchanges by month.
- **Month** (`Month`): a calendar month, `YYYY-MM`, cut in UTC.
- **Version**: the facts and figures a count made. The **active** version is the one read. The version **being built** by a rebuild stays out of sight until it is complete. A version is **live** while it is active or being built.
- **Rebuild**: counting every account again from the other contexts' state, in a new version.

## Rules

- A received fact replaces its aggregate's known fact when its revision is higher. The terminal fact replaces any other, and none replaces it. Otherwise the fact is ignored: received twice, it counts once (`FactSuccession`).
- The figures change by the difference between the received fact's contribution and the known fact's (`FiguresDelta`):
  - an organization moved without a point leaves the located ones;
  - an exchange whose moment moves to another month moves from one month to the other;
  - a planned exchange counts once done or missed, in its moment's month, and never once cancelled (`ExchangeFact::withStatus()`: held unless planned or cancelled; a fact published before exchanges had a status, `ExchangeFactsChanged.v1`, was held).

  The change is written with the fact and added to the figures, so that facts recorded at the same time count as if recorded one after the other.
- A received fact is recorded in every live version (`FactRecorder`).
- A member reads their account's figures in the active version, with the exchanges of the month in which they ask. Before the first rebuild, the figures are zeros.
- A rebuild takes the next version (1 when there is no active one).
  1. It first abandons what a previous rebuild left behind. The version being built is no longer live. The rebuild waits 60 s for the recordings in flight to end, which is longer than the worker runs. Then it removes every version but the active one. So nothing records in a version while the rebuild empties it (ADR 0106).
  2. It then starts building its version and waits again for the recordings in flight.
  3. It records again every account's facts as the other contexts list them.
  4. It activates its version and removes the previous one.

  It does not recreate the terminal facts. Each of its steps, and each fact, is a unit of work of its own.

## What it publishes

Nothing: Reporting is downstream of Accounts, PersonalData and Prospecting.

## Features

`tenir-les-chiffres`, `consulter-les-chiffres`, `recalculer-les-chiffres`.

## In the code

- `src/Reporting/Domain`:
  - the facts, `FiguresChange` and `Month`;
  - the rules `FactSuccession` and `FiguresDelta`;
  - the repository `FactRepository` (a version's facts and the figures they make);
  - the ports `SourceAccounts` (the accounts, and a member's account, from Accounts), `ProspectingFacts` and `PersonalDataFacts` (an account's facts as they are now, from the fact services of Prospecting and PersonalData), implemented by anticorruption layers.
- `src/Reporting/Application`:
  - `FactRecorder`;
  - the reactions `RecordOrganizationFacts`, `RecordExchangeFacts`, `RecordContactFacts` to `OrganizationFactsChanged`, `ExchangeFactsChanged`, `ContactFactsChanged`;
  - `ConsultFigures` (`FiguresResponse`);
  - `RebuildFigures` (`SourceFacts`, every account's facts), without a command's unit of work;
  - the ports `ProjectionVersions` (the active version, the live ones, abandon the one being built, remove the abandoned ones, start, activate), `FiguresRetriever` and `RecordingsInFlight` (awaits the end of the recordings already begun).
- In the API (ADR 0104): its table, its items and the rebuild are in `docs/architecture/persistence.md`, the subscription `reporting-figures` in `docs/architecture/contexts.md`. The dashboard shows `ConsultFigures` (`GET /api/dashboard`, `docs/architecture/api.md`).
