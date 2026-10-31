# The bounded contexts

What is in force; the reasons are in the records it names (`docs/adr`), read only to know why or before changing it (ADR 0090).

Accounts, PersonalData and Prospecting (ADR 0030, 0031), and the shared kernel of what they all share (`core.md`). Their language and their rules: `docs/domain/accounts.md`, `personal-data.md`, `prospecting.md`.

## What a context sees of another

- A context never uses another one: it declares a port in its own words (`Prospectors`, `Readers`, `KnownContacts`, `ProspectedOrganizations`), implemented outside the core by an anticorruption adapter (`App\Prospecting\AntiCorruption\AccountsProspectors`, `App\PersonalData\AntiCorruption\AccountsReaders`, `App\Prospecting\AntiCorruption\PersonalDataKnownContacts`, `App\PersonalData\AntiCorruption\ProspectingOrganizations`), doubled in the scenarios by the same code (`tests/Double`). The adapter sees of the other context only what it publishes (ADR 0045): the service its `Contract/Service` declares (ADR 0089: `ConsultMembersService`, `WhichAreContactsService`, `WhichAreProspectedService`), injected by its type, implemented by the handler of the query in its `Application`, its answer kept by `KeptMembers`, `KeptContacts`, `KeptProspected`. A published query asks about a set, a single element being a set of one, and refuses nothing: what is unknown is absent from its answer, the client deciding what an absence means; it answers plain data with only what the others need, checking no caller, fixed by `features/langage-publie.feature`; `tests/Architecture/BoundedContextsTest` fails otherwise. A port of the asking context asks about a set where a use case asks about several elements (`ProspectedOrganizations::prospectedAmong()`). A new question between contexts is a new published service, its scenario, and the rule it answers in the aggregate (`Person::isAContactOf()`).

## What each publishes

| Context | Services (`Contract/Service`, ADR 0089) | Integration events (`Contract/Event`, ADR 0046) |
|---|---|---|
| Accounts | `ConsultMembersService`: who the members are | `AccountOpened`, `MemberRegistered` |
| PersonalData | `WhichAreContactsService`: which persons are contacts of an account | `ContactCollected`, `ContactForgotten` |
| Prospecting | `WhichAreProspectedService`: which organizations an account prospects | `ContactsLeftWithoutOrganization` |

In the API, the anticorruption layers implementing the ports of a context are in `src/<Context>/AntiCorruption` (ADR 0086), the delivery of a context uses nothing of another but its Contract (`api.md`).
