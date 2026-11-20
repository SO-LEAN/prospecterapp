# 0173. A person or an organization is identified by the core from its clues, known, new or to confirm

- Date: 2026-11-20
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting/Application/IdentifyOrganizations`, `packages/core/src/Prospecting/Domain/Model/{OrganizationKeys,CorporateNameKey}.php`, `packages/core/src/Prospecting/Application/Port/DesignatedOrganizationRetriever.php`, `packages/core/src/PersonalData/Application/IdentifyPersons`, `packages/core/src/PersonalData/Domain/Model/{PersonKeys,Reader}.php`, `packages/core/src/PersonalData/Application/Port/DesignatedContactRetriever.php`, `packages/core/features/{identifier-des-organisations,identifier-des-personnes}.feature`, `apps/api/src/Prospecting/Persistence/DynamoDesignatedOrganizationRetriever.php`, `apps/api/src/PersonalData/Persistence/DynamoDesignatedContactRetriever.php`, `apps/api/src/Identification`

## Context

An AI asked to create the interesting contacts of a member on LinkedIn must duplicate nothing, without downloading every contact of the account. It sees a person or an organization by clues: a name, links to LinkedIn, Malt, Welcome to the Jungle or any other tool, an email, a phone number, a website, a SIRET, a city. The keys that designate one contact or one organization of an account already exist: the links (0159, 0165), the SIRET (0158), the domain of a website (0160), the email (0039), the phone number (0072), the names alike (0162, 0170). The import of Gmail and the screen of creation will need the same recognition.

## Decision

- **A service of the core**, a query per context, read only: `IdentifyOrganizations` (Prospecting) and `IdentifyPersons` (PersonalData), for a list at once, each answered in the order given: **known** (its one candidate), **new** (none), or **to confirm** (its candidates), a candidate named by its id and its name, which every member of the account reads (0129).
- **The keys, the surest first.** A person: each link, current or former, of any source, each email, each mobile (a landline is shared by a whole organization). An organization: its SIRET, each link of any source, the domain of its website, each email, then the domain of each email as that of a website, but those of a public mailbox (`PublicMailDomains`, kept in the core, 0146). The contacts or organizations they designate, each once: one is known, several are to confirm, never known.
- **Then the name**, nothing designated: a person by their resemblance key (0170) among the contacts of the account, those of the organization the client says they work for first; an organization by its name without legal form (`CorporateNameKey`, the name half of the resemblance key of 0162), among the 50 first organizations of the account whose name begins like it, those of the same city first; beyond those 50, none is proposed. Nothing named alike: new.
- **A clue that is no key of its kind** (no web address, a page of another kind, no email, no number, a SIRET failing its key) designates nothing: ignored, never a refusal of the whole list.
- **Without the right to manage personal data**, the links, the emails and the phone numbers of a person identify nobody, their names only (`Reader::identifiesByPersonalData()`): a reader of masked data could guess what they cannot read, as for the search by email and the recognition of a caller (0031, 0072, 0129). The organizations are identified by every prospector, by every key.
- **No new key**: each port reads what the repositories write already (`DesignatedOrganizationRetriever`, `DesignatedContactRetriever`, 0088), the reservations, the index of the phone numbers and the links of the resemblance keys of the persons; the names of the organizations by the search of their beginning (0071).
- **The MCP tool `identifier`, without an endpoint** (as `qui-suis-je`, 0163): no screen asks it yet. It identifies the organizations first, then the persons, a person naming the organization they work for by its position in the list: the organizations it was identified as. A hundred persons and a hundred organizations at most per call, its schema refusing more (the error of the protocol).

## Consequences

- The import of Gmail and the screen of creation reuse the queries, not the tool.
- An organization whose name begins with its legal form (« SAS Acme ») is not found by « Acme »: the search reads the beginning of a name.
- Two contacts noted before the links were unique holding the same one: the reservation names the oldest only (0159).
- A mobile shared by two contacts makes both candidates, to confirm.

## Alternatives considered

- A key of its own for the name of an organization without legal form, written with it and by `app:keys:rebuild`: weighed in the plan, not kept for now, the search of the beginning of a name being enough for the names a client gives.
- An endpoint beside the tool: no screen needs it yet.

## Revisit when

The screen of creation or the import of Gmail identifies, or members miss organizations whose name begins with a legal form.
