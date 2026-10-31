# PersonalData

The personal data of natural persons, and who reads them. Code: `packages/core/src/PersonalData`.

## Language

- **Person** (`Person`): the personal data of a natural person, kept for one account and one purpose: their name (`PersonName`), their job title, their contact details, their picture.
- **Purpose** (`Purpose`): why the account keeps them: a member's own profile (`Membership`), or a person it prospects (`Prospecting`).
- **Contact**: a person an account prospects, working in at least one of the organizations it prospects.
- **Reader** (`Reader`): a member as PersonalData sees them: their account, their own person, and whether they hold the right to read personal data in clear.

## Rules

- No other context keeps personal data, only a `PersonId`; but the text and the attachments of the exchanges of Prospecting, which may hold some (ADR 0031, 0074, 0077).
- Another account does not even learn that a person exists; the person themself and a member of the account with the right read them in clear, the other members masked: a fixed number of stars, so that the length of a name or of an address does not leak (`PersonReading`, `Masking`; ADR 0031).
- A person changes their own data; a member with the right changes the persons their account prospects, not the profile of a colleague; only a prospected person is forgotten (`PersonPossibilities`; ADR 0031, 0075).
- A contact is collected by a member with the right, for at least one organization the account prospects: a contact is never alone, and a contact no organization names any more is erased (`CollectContact`, `ForgetContactsLeftWithoutOrganization`; ADR 0041, 0042).
- An email is unique among the persons an account keeps for a purpose (`EmailUniqueness`; ADR 0039).
- Searching the contacts by text, and recognizing a caller by their phone number, need the right to manage personal data: a reader of masked data could guess them by what they cannot read (ADR 0031, 0072).
- Forgetting a contact erases their person; their exchanges stay in Prospecting, to make conform (ADR 0079).

## What it publishes

- The service `WhichAreContactsService` (which persons are contacts of an account), asked by Prospecting (ADR 0089).
- The integration events `ContactCollected` and `ContactForgotten` (ADR 0046).

## Features

`enregistrer-un-contact`, `effacer-un-contact`, `consulter-une-personne`, `rechercher-mes-contacts`, `reconnaitre-un-appelant`, `changer-mon-nom`, `changer-mes-coordonnees`, `changer-ma-photo`, `changer-la-fonction`, `changer-le-profil-d-un-collegue`, and the questions of `langage-publie`.

## In the code

- `src/PersonalData/{Domain,Application}`: the personal data of natural persons (`Person`: account, `Purpose`, `PersonName`, job title, contact details, picture), read in clear or masked (`PersonReading`, `Masking`) by a `Reader` obtained through the port `Readers` (`readerFrom()`); queries `ConsultPerson`, `ConsultPersons` (batch), `SearchContacts` and `RecognizeCallingContact` (who of the contacts has a number, with the right to manage personal data), commands to change a person, and for the persons an account prospects (contacts, ADR 0041, 0042) `CollectContact` (for at least one organization, checked through the port `ProspectedOrganizations`) and `ForgetContact`, with the right to manage personal data (ADR 0031); `ForgetContactsLeftWithoutOrganization` reacts to Prospecting, `CollectTheFounderProfile` to the opening of an account and `CollectTheMemberProfile` to the registration of a member (an empty profile of their own, ADR 0064, 0075). No other context keeps personal data, only a `PersonId`, but the text of the exchanges of Prospecting (ADR 0074, to make conform, ADR 0079) and their attachments (ADR 0077).
