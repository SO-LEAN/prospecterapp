# Prospecting

The organizations an account prospects, their contacts, and the exchanges with them. Code: `packages/core/src/Prospecting`.

## Language

- **Organization** (`Organization`): an organization an account prospects, shared by the whole team of the account: its legal identity, its contact details, its address and position, its observations, its logo, its holding, its contacts (`PersonId`s only).
- **Prospector** (`Prospector`): a member as Prospecting sees them: who acts, for which account, and whether they read the personal data an exchange may hold.
- **Exchange** (`Exchange`): a fact of the real world, a call, an email or a meeting with a contact or with the organization itself (`ExchangeFacts`: channel, direction, moment, text, missed), kept for the organizations the contact was part of (ADR 0074, 0076).
- **Timeline**: the exchanges with a contact, or those of an organization and of its contacts, the most recent moment first.
- **To make conform**: what an exchange may still hold of a forgotten contact: its text until a correction declares it conform, its files until they are removed (ADR 0079).

## Rules

- An organization is named by its corporate name or its email, unique among the organizations of an account (`EmailUniqueness`; ADR 0039); it is found by the beginning of its name or of its email (ADR 0071), or recognized by its phone number (ADR 0072).
- A holding is an organization of the same account (`AttachToHolding`).
- A contact is a person the account prospects in PersonalData; added to an organization, removed from it, and a contact no organization names any more is erased in PersonalData (`OrphanContacts`; ADR 0041, 0042). Stopping to prospect an organization lets its contacts go.
- What the state of an organization offers is a policy (`OrganizationPossibilities`): a logo to remove, a holding to detach from, a contact to remove (ADR 0088).
- Every prospector of the account reads the exchanges; without the right to manage personal data, their text comes flagged, their attachments hidden, and their download refused; every prospector may correct the text (ADR 0078).
- An exchange holds 10 files of 10 MB at most (`ExchangePossibilities`; ADR 0077); an erased exchange takes its files along.
- An exchange is kept for the organizations its contact was part of when it was noted; it is erased with the last of them (`StopKeepingExchanges`; ADR 0074).
- The exchanges of a forgotten contact stay in the timelines of their organizations, to make conform; a timeline counts those to make conform (`ExchangeConformity`; ADR 0079).
- In the timeline of an organization, the contact of an exchange is present, removed from it, or forgotten (`ContactPresence`; ADR 0076, 0079).

## What it publishes

- The service `WhichAreProspectedService` (which organizations an account prospects), asked by PersonalData when a contact is collected (ADR 0089).
- The integration event `ContactsLeftWithoutOrganization` (ADR 0046).

## Features

`commencer-a-prospecter`, `consulter-une-organisation`, `rechercher-mes-organisations`, `reviser-l-identite`, `changer-les-coordonnees`, `demenager`, `ecrire-des-observations`, `changer-le-logo`, `retirer-le-logo`, `rattacher-a-une-holding`, `rattacher-un-contact`, `retirer-un-contact`, `arreter-de-prospecter`, `noter-un-echange`, `consulter-un-echange`, `corriger-un-echange`, `corriger-le-texte-d-un-echange`, `supprimer-un-echange`, `joindre-des-fichiers-a-un-echange`, `retirer-une-piece-jointe`, `telecharger-une-piece-jointe`, `consulter-la-timeline-d-une-organisation`, `les-echanges-d-un-contact-retire-ou-efface`, and the questions of `langage-publie`.

## In the code

- `src/Prospecting/{Domain,Application}`: the organizations an account prospects (`Organization`), its contacts (`PersonId`s only, `AddContact` / `RemoveContact`, never alone: `OrphanContacts`), `StopProspecting`, the `Prospector` who starts prospecting an organization or reads (with or without the right to manage personal data), read through the port `Prospectors` (`prospectorFrom()`, implemented outside the core from the members of Accounts), the port `KnownContacts` (whether a person is one the account prospects, answered from PersonalData); `ConsultOrganizationsOfContacts` (batch), `RecognizeCallingOrganization` (which organization has a number); the exchanges with a contact or with the organization itself (`Exchange`: a fact of the real world, its `ExchangeFacts`, ADR 0074, 0076), `NoteExchange` kept for the organizations the contact is part of then (`ContactOrganizations`), `NoteOrganizationExchange` from the card of an organization, with it or one of its contacts; `ConsultTimeline` (the most recent moment first, what it offers: `TimelineIntent`) and `ConsultOrganizationTimeline` (its exchanges and those of its contacts), `ConsultTimelineIntents` (what the timeline of a person offers without reading it, nothing for a person who is no contact: the card of a contact leads to it by a link), `ConsultExchange` (the whole of an exchange, who noted it and who corrected it last, what its state offers: `ExchangeIntent`), read by every prospector of the account, the text flagged and the attachments hidden without the right (`textFlagged`, `hiddenAttachments`, ADR 0078), `CorrectExchangeText` (its text only, by every prospector of the account), `CorrectExchange` (all its facts at once, its contact staying; the last `Correction` kept, the former versions by the activity journal only) and `EraseExchange`, erased with their last organization by `StopKeepingExchanges`; the files joined to an exchange (`Attachment`: 10 MB, 10 per exchange at most, ADR 0077), `AttachFile` (an upload the client sent to the storage, measured then kept through the port `PrivateStorage`), `RemoveAttachment` and `DownloadAttachment` (a link signed for a moment, with the right to manage personal data); an erased exchange takes its files along (`AttachedFiles`); the exchanges of a forgotten contact stay, to make conform (`KeepExchangesOfForgottenContact` reacts to `ContactForgotten`: `Exchange::forgetItsContact()`, ADR 0079): their text until a correction declares it made conform (`madeConform` of `CorrectExchangeText` and `CorrectExchange`, the intent `MakeConform`), their files until removed; the contact of an exchange read in the timeline of an organization is present, removed from it or forgotten (`ContactPresence`), and the timeline counts those to make conform, `ConsultExchangesToMakeConform` listing them alone.
