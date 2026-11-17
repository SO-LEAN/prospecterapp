# Campaigns

The tags the members put on their organizations and their contacts, to find later those carrying them and prepare campaigns (ADR 0138). Downstream of Accounts, PersonalData and Prospecting, like Reporting. Code: `packages/core/src/Campaigns`.

## Language

- **Tag** (`Tag`): a `#` then 1 to 30 capital letters without accents, digits and underscores (#ESN, #SALON_2026), written one way whatever was typed: the accents removed, every other run of characters an underscore, nothing at its ends (`éditeur logiciel` is #EDITEUR_LOGICIEL); what makes no tag is refused (`InvalidTag`).
- **Holder** (`Holder`): what carries tags, an organization (`OrganizationId`) or a contact (`PersonId`).
- **Tagging** (`Tagging`): the tags the members of an account put **by hand** on one holder, source data of Campaigns, in the order of their names.
- **Predefined tags** (`PredefinedTags`): proposed to every account by family (`TagFamily`): the type of an organization (#APPORTEUR #ESN #EDITEUR #FINAL #CABINET_RECRUTEMENT #PORTAGE #PARTENAIRE), the relation with a contact (#CLIENT #DECIDEUR #TECH #RH #ACHATS #ANCIEN_COLLEGUE #RECOMMANDE), the technologies (#PHP #SYMFONY #LARAVEL #JAVASCRIPT #TYPESCRIPT #REACT #VUE #ANGULAR #NODE #JAVA #DOTNET #PYTHON #GO #AWS #AZURE #GCP #DOCKER #KUBERNETES #DEVOPS #DATA #IA); suggestions only, a tag of the account having the same value.
- **Automatic tags** (`AutomaticTags`): those Prospecter sets by itself, reserved: #EMAIL, #PHONE, #SMS, #LINKEDIN, the departments (#67, #2A, #971) and the regions (#GRAND_EST…).
- **Tags proposed** (`SuggestedTags`): the predefined tags by family, then the tags of the account: those its holders carry that are not predefined.

## Rules

- A member tags an organization their account prospects or a contact of their account (`PutTags`), as Prospecting and PersonalData tell (`TaggableHolders`); anything else is unknown (`UnknownOrganization`, `UnknownContact`). Every prospector tags, with or without the right to manage personal data.
- A tag already carried is carried once; an automatic tag is never put by hand (`AutomaticTag`, « The tag #EMAIL is set automatically »), and refuses the whole gesture.
- A member takes a tag off (`TakeOffTag`), refused when it is not carried (`TagNotCarried`).
- The tags proposed (`ConsultSuggestedTags`) are derived from the tags carried: a tag nothing carries any more is no longer proposed, a tag of another account never is.
- The tagging of an organization is forgotten once Prospecting says the account no longer prospects it (`ForgetTagsOfUnprospectedOrganization`, reacting to its terminal `OrganizationFactsChanged`), that of a contact once PersonalData says it is forgotten (`ForgetTagsOfForgottenContact`, reacting to `ContactForgotten`): stopping to prospect an organization takes its tags with it, and those of its contacts it erases.

## What it publishes

Nothing. Its domain events, `OrganizationTagsChanged` and `ContactTagsChanged`, stay in the context.

## Features

`taguer-mes-organisations-et-mes-contacts`.

## In the code

- `src/Campaigns/Domain`: `Tag`, `Holder` and `HolderKind`, `TagFamily`, the aggregate `Tagging`, the policies `PredefinedTags` and `AutomaticTags`, the domain service `TaggableHolders`, the repository `TaggingRepository`, the ports `Taggers` (the account of a member, from Accounts), `TaggableOrganizations` (Prospecting) and `TaggableContacts` (PersonalData), implemented by anticorruption layers.
- `src/Campaigns/Application`: `PutTags`, `TakeOffTag`, `ConsultTags`, `ConsultSuggestedTags` (`SuggestedTags`), the reactions `ForgetTagsOfUnprospectedOrganization` and `ForgetTagsOfForgottenContact`, the port `TagsRetriever`.
- In the API: its table and its items in `docs/architecture/persistence.md`, its endpoints in `docs/architecture/api.md`, its subscriptions in `docs/architecture/contexts.md`.
