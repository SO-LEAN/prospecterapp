# Reporting

The figures of each account, kept from the facts the other contexts publish, and counted again from their state when needed (ADR 0103). Code: `packages/core/src/Reporting`.

## Language

- **Figures**: what an account counts: the organizations it prospects (`activeOrganizations`), those placed on the map (`locatedOrganizations`), its contacts (`contacts`, persons it keeps for its prospecting), the exchanges it keeps (`exchanges`), and those of the current month (`exchangesThisMonth`).
- **Fact** (`Fact`: `OrganizationFact`, `ExchangeFact`, `ContactFact`): the state of an aggregate of another context as Reporting counts it, with its revision; a **terminal** fact (absent) ends the aggregate (ADR 0099).
- **Change** (`FiguresChange`): what each figure of an account gains or loses, the exchanges by month.
- **Month** (`Month`): a month of the calendar, `YYYY-MM`, cut in UTC.
- **Version**: the facts and the figures a count made; the **active** one is read, the one **being built** by a rebuild is out of sight until it is complete. A version is **live** while it is active or being built.
- **Rebuild**: counting every account again from the state of the other contexts, in a new version.

## Rules

- A fact received replaces the known one of its aggregate when its revision is higher; the terminal fact replaces any other, and none replaces it; otherwise it is ignored, received twice it counts once (`FactSuccession`).
- The figures change by the difference between the contribution of the fact received and of the known one (`FiguresDelta`): an organization moved without a point leaves the located ones, an exchange whose moment changes month moves from one month to the other. The change is written with the fact and added to the figures, so that facts recorded at the same time count as recorded one after the other.
- A fact received is recorded in every live version (`FactRecorder`).
- A member reads the figures of their account in the active version, the exchanges of the month of the moment they ask; before the first rebuild, zeros.
- A rebuild takes the next version (1 without an active one). It first abandons what a former rebuild left: the version being built is no longer live, it waits for the recordings in flight to end (60 s, longer than the worker), then removes every version but the active one, so that nothing records in a version while it is emptied (ADR 0106). It then starts building its version, waits again for the recordings in flight, records again the facts of every account as the other contexts list them, then activates its version and removes the former one. It does not recreate the terminal facts. Each of its steps, each fact, is a unit of work of its own.

## What it publishes

Nothing: Reporting is downstream of Accounts, PersonalData and Prospecting.

## Features

`tenir-les-chiffres`, `consulter-les-chiffres`, `recalculer-les-chiffres`.

## In the code

- `src/Reporting/Domain`: the facts and `FiguresChange`, `Month`, the policies `FactSuccession` and `FiguresDelta`, the repository `FactRepository` (the facts of a version and the figures they make), the ports `SourceAccounts` (the accounts, and the account of a member, from Accounts), `ProspectingFacts` and `PersonalDataFacts` (the facts of an account as they are now, from the services of facts of Prospecting and PersonalData), implemented by anticorruption layers.
- `src/Reporting/Application`: `FactRecorder`; the reactions `RecordOrganizationFacts`, `RecordExchangeFacts`, `RecordContactFacts` to `OrganizationFactsChanged`, `ExchangeFactsChanged`, `ContactFactsChanged`; `ConsultFigures` (`FiguresResponse`); `RebuildFigures` (`SourceFacts`, the facts of every account), without the unit of work of a command; the ports `ProjectionVersions` (the active version, the live ones, abandon the one being built, remove the abandoned ones, start, activate), `FiguresRetriever` and `RecordingsInFlight` (awaits the end of the recordings begun).
- In the API (ADR 0104): its table, its items and the rebuild in `docs/architecture/persistence.md`, the subscription `reporting-figures` in `docs/architecture/contexts.md`. `ConsultFigures` is shown by the dashboard (`GET /api/dashboard`, `docs/architecture/api.md`).
