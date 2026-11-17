# 0138. The Campaigns context keeps the tags put by hand on an organization or a contact, the automatic ones reserved

- Date: 2026-11-17
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/Campaigns`, `packages/core/features/taguer-mes-organisations-et-mes-contacts.feature`, `apps/api/src/Campaigns`, `apps/api/src/Tags`, `apps/front/src/features/tags`

## Context

A prospector wants to tag their organizations (#ESN, #PHP) and their contacts (#CLIENT, #DECIDEUR), to find the contacts carrying them and prepare campaigns. Tags of another kind are to come: those a contact inherits from its organizations and those set automatically from what the other contexts know (#EMAIL, #PHONE, #SMS, #LINKEDIN, then the geographic tags derived from the municipality of an organization), all of them rebuilt from the state of the other contexts, and a search of the contacts carrying every tag chosen. Accounts, PersonalData and Prospecting own the organizations and the contacts; none of them is about targeting.

## Decision

- A sixth bounded context, **Campaigns**, downstream of Accounts, PersonalData and Prospecting like Reporting: it uses nothing of them but their Contract, and publishes nothing.
- The tags a member puts **by hand** on an organization or a contact are **source data** of Campaigns, not a projection: the **tagging** of an organization or of a contact (`Tagging`), an aggregate kept by its account and the `OrganizationId` or `PersonId` it tags, holds them. A member tags only an organization their account prospects and a contact of their account, asked of Prospecting (`WhichAreProspectedService`) and of PersonalData (`WhichAreContactsService`).
- A tag is written one way: a `#` then 1 to 30 capital letters without accents, digits and underscores; what a member types is normalized (the accents removed, every other run of characters an underscore, `éditeur logiciel` is #EDITEUR_LOGICIEL). A predefined tag and a tag of the account have the same value.
- The **automatic** tags are reserved: a member never puts them by hand (`AutomaticTag`, « The tag #EMAIL is set automatically »): #EMAIL, #PHONE, #SMS, #LINKEDIN, the departments (#67, #2A, #971) and the regions. The names of the attraction areas join them with the table that derives them.
- The **tags proposed** to an account are derived, never kept: the predefined tags, by family (the type of the organization, the relation with the contact, the technologies), then the tags its organizations and contacts carry that are not predefined. A tag nothing carries any more is no longer proposed; a tag of another account never is.
- The tagging of an organization is forgotten when Prospecting says the account no longer prospects it (its terminal `OrganizationFactsChanged`), that of a contact when PersonalData says it is forgotten (`ContactForgotten`): Campaigns keeps nothing of what the other contexts erased.
- Campaigns has a table of its own, `<prefix>-campaigns`, like every context.

## Consequences

- The inherited and the automatic tags are a projection of Campaigns beside the tagging, rebuilt from the services of the other contexts without touching what the members put by hand.
- Reading the tags proposed reads every tagging of the account: one query of its partition, enough for the organizations and contacts an account prospects.
- A manual tag named like an attraction area before its table reserves the name stays carried, the area then setting the same tag automatically.
- Tagging asks Prospecting or PersonalData whether the account knows what it tags, at each gesture.

## Alternatives considered

- The tags proposed kept in a counter per tag and account: dropped, deriving them from the tags carried needs nothing written beside them and cannot drift.

## Revisit when

An account carries so many taggings that reading them all to propose its tags is slow, or a tag must be renamed or merged across the account.
