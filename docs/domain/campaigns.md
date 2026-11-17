# Campaigns

The tags the members put on their organizations and their contacts, to find later those carrying them and prepare campaigns (ADR 0138). Downstream of Accounts, PersonalData and Prospecting, like Reporting. Code: `packages/core/src/Campaigns`.

## Language

- **Tag** (`Tag`): a `#` then 1 to 30 capital letters without accents, digits and underscores (#ESN, #SALON_2026), written one way whatever was typed: the accents removed, every other run of characters an underscore, nothing at its ends (`éditeur logiciel` is #EDITEUR_LOGICIEL); what makes no tag is refused (`InvalidTag`).
- **Holder** (`Holder`): what carries tags, an organization (`OrganizationId`) or a contact (`PersonId`).
- **Tagging** (`Tagging`): the tags the members of an account put **by hand** on one holder, source data of Campaigns, in the order of their names.
- **Predefined tags** (`PredefinedTags`): proposed to every account by family (`TagFamily`): the type of an organization (#APPORTEUR #ESN #EDITEUR #FINAL #CABINET_RECRUTEMENT #PORTAGE #PARTENAIRE), the relation with a contact (#CLIENT #DECIDEUR #TECH #RH #ACHATS #ANCIEN_COLLEGUE #RECOMMANDE), the technologies (#PHP #SYMFONY #LARAVEL #JAVASCRIPT #TYPESCRIPT #REACT #VUE #ANGULAR #NODE #JAVA #DOTNET #PYTHON #GO #AWS #AZURE #GCP #DOCKER #KUBERNETES #DEVOPS #DATA #IA); suggestions only, a tag of the account having the same value.
- **Automatic tags** (`AutomaticTags`): those Prospecter sets by itself, reserved: #EMAIL, #PHONE, #SMS, #LINKEDIN, the departments (#67, #2A, #971) and the regions (#GRAND_EST…).
- **Tags proposed** (`SuggestedTags`): the predefined tags by family, then the tags of the account: those its holders carry that are not predefined.
- **Fact** (`Fact`: `OrganizationFact`, `ContactFact`): the state of an organization of Prospecting (prospected or not, the contacts it names) or of a contact of PersonalData (kept or not, how it is reached) as Campaigns derives the tags of the contacts from it, with its revision; a **terminal** fact (absent) ends it (ADR 0141).
- **Reach** (`Reach`): how a contact is reached, an e-mail, a phone, a mobile among them, a LinkedIn profile, never the details themselves; it sets the automatic tags #EMAIL, #PHONE, #SMS and #LINKEDIN.
- **Tags of a contact** (`ContactTags`): its own, put on it by hand; those it **inherits**, put by hand on the organizations naming it (`InheritedTag`, with those organizations); the **automatic** ones of its reach.

## Rules

- A member tags an organization their account prospects or a contact of their account (`PutTags`), as Prospecting and PersonalData tell (`TaggableHolders`); anything else is unknown (`UnknownOrganization`, `UnknownContact`). Every prospector tags, with or without the right to manage personal data.
- A tag already carried is carried once; an automatic tag is never put by hand (`AutomaticTag`, « The tag #EMAIL is set automatically »), and refuses the whole gesture.
- A member takes a tag off (`TakeOffTag`), refused when it is not carried (`TagNotCarried`).
- The tags proposed (`ConsultSuggestedTags`) are derived from the tags carried: a tag nothing carries any more is no longer proposed, a tag of another account never is.
- The tagging of an organization is forgotten once Prospecting says the account no longer prospects it (`ForgetTagsOfUnprospectedOrganization`, reacting to its terminal `OrganizationFactsChanged`), that of a contact once PersonalData says it is forgotten (`ForgetTagsOfForgottenContact`, reacting to `ContactForgotten`): stopping to prospect an organization takes its tags with it, and those of its contacts it erases.

- A contact carries the tags put by hand on each organization naming it, following their tags, the contacts attached and removed; a tag it carries by hand and inherits too is its own, shown once; an inherited tag names every organization it comes from (`ContactTags`). It carries #EMAIL with an e-mail, #PHONE with a phone, #SMS when that phone is a mobile (libphonenumber), #LINKEDIN with a LinkedIn profile, never put nor taken off by hand. Every prospector reads them, with or without the right to manage personal data, for the contacts of their account only (`ConsultContactTags`, a set, `UnknownContact` otherwise).
- The tags of a contact are derived when they are read, from the facts Campaigns keeps (ADR 0141): a fact received replaces the known one of its aggregate when its revision is higher; the terminal fact replaces any other, and none replaces it; received twice it counts once (`FactSuccession`, `FactRecorder`). A fact published before the contacts and the reach were (the first version of the facts) is not recorded.
- A rebuild records again the facts of every account, or of one, as Prospecting and PersonalData give them from their state, through the same succession, each fact a unit of work of its own, without version nor wait (`RebuildTags`).

## What it publishes

Nothing. Its domain events, `OrganizationTagsChanged` and `ContactTagsChanged`, stay in the context.

## Features

`taguer-mes-organisations-et-mes-contacts`, `porter-les-tags-de-mes-organisations-et-de-mes-coordonnees`.

## In the code

- `src/Campaigns/Domain`: `Tag`, `Holder` and `HolderKind`, `TagFamily`, the aggregate `Tagging`, the facts (`Fact`, `OrganizationFact`, `ContactFact`), `Reach`, `ContactTags` and `InheritedTag`, the policies `PredefinedTags`, `AutomaticTags` and `FactSuccession`, the domain service `TaggableHolders`, the repositories `TaggingRepository` and `FactRepository`, the ports `Taggers` (the account of a member, from Accounts), `TaggedAccounts` (the accounts, from Accounts), `TaggableOrganizations` and `OrganizationsAndTheirContacts` (Prospecting), `TaggableContacts` and `ContactsReach` (PersonalData), implemented by anticorruption layers.
- `src/Campaigns/Application`: `PutTags`, `TakeOffTag`, `ConsultOrganizationTags`, `ConsultContactTags`, `ConsultSuggestedTags` (`SuggestedTags`), `FactRecorder`, `RebuildTags` without the unit of work of a command, the reactions `ForgetTagsOfUnprospectedOrganization`, `ForgetTagsOfForgottenContact`, `RecordOrganizationFacts` and `RecordContactFacts`, the ports `TagsRetriever` and `ContactTagsRetriever` (`ContactTagSources`).
- In the API: its table and its items in `docs/architecture/persistence.md`, its endpoints in `docs/architecture/api.md`, its subscriptions in `docs/architecture/contexts.md`, `app:tags:rebuild` in `docs/architecture/messaging.md`.
