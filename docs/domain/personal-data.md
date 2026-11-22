# PersonalData

Natural persons' personal data, and who reads it. Code: `packages/core/src/PersonalData`.

## Language

- **Person** (`Person`): a natural person's personal data, kept for one account and one purpose:
  - their name (`PersonName`: a first name and a last name, trimmed at both ends, with one space between words, and absent when it is nothing but spaces);
  - a contact's title;
  - their job title;
  - their contact details;
  - their links;
  - their picture.
- **Link** (`ExternalReference` from the shared kernel): the address of a page about the person on an outside tool (LinkedIn, Malt or any other site). It is logged with their contact details. A person has five links at most, and none is required.
  - A link is recognized by its **source** and its **identifier**, normalized to one form however it was pasted. The source is `linkedInProfile` or `malt` for a profile there, and the address's host for a tool Prospecter does not know; the identifier is the address in its normalized form. The pasted address is kept, to open it.
  - Refused: a page of a known tool that designates an organization (a LinkedIn or Welcome to the Jungle company page), and an address on a known tool's site that is none of its recognized pages.
  - A link belongs to a contact or to a member.
  - A person keeps their last three **former links**, whatever their source: the links they had before changing or removing them. Former links still recognize the person. They are never shown nor published, and are forgotten with the person (ADR 0139, 0159, ADR 0165).
- **Title** (`Civility`): how to address a contact when their first name does not tell: Ms or Mr (Madame or Monsieur to the member), or X while it is unknown.
  - It is never absent. It is X for a contact collected without one, for a person saved before titles were logged, and when a member removes it. Any other value is refused.
  - It is logged for contacts only: a member's profile keeps X.
- **Purpose** (`Purpose`): why the account keeps the person: a member's own profile (`Membership`), or a person it prospects (`Prospecting`).
- **Contact**: a person an account prospects, who works in at least one of the organizations it prospects.
- **Possible duplicate**: a contact of the account whom another contact resembles, by first and last names, in one of their organizations (`PersonResemblanceKey`). Perhaps the same person, logged twice. Both are flagged, nothing is refused, until a member checks and says it is not a duplicate (ADR 0170).
- **Reader** (`Reader`): a member as PersonalData sees them: their account, their own person, and whether they hold the right to read personal data in clear.

## Rules

- No other context keeps personal data, only a `PersonId`. The exceptions:
  - the text and attachments of Prospecting's exchanges, which may hold some (ADR 0031, 0074, 0077); a meeting's place and a video call's link are not personal data (ADR 0114);
  - the address of the account a member connects at a provider, kept by Connectors, theirs alone, and forgotten when they disconnect (ADR 0120);
  - whoever booked an appointment through a public link, as they stated it, kept by Booking for 16 days at most, until PersonalData identifies them (ADR 0164).
- Another account does not even learn that a person exists. The person themself and a member of the account with the right read the data in clear. Other members read it masked (`PersonReading`, `Masking`; ADR 0031, ADR 0165):
  - a fixed number of stars, so that the length of a name or an address does not leak;
  - their links as a single link of stars, whatever their number.

  The exception: every member of the account reads a contact's name and title in clear; the rest of the contact stays masked (`PersonReading::ofTheName()`; ADR 0129).
- A member's profile is collected empty when their account is opened or when they are registered. The member fills it in themselves: at their first sign-in, the SPA asks for their name and their profile's email, proposing their login email (`CollectTheFounderProfile`, `CollectTheMemberProfile`; ADR 0064, 0075, 0100).
- A person changes their own data. A member with the right changes the persons their account prospects, but not a colleague's profile. Only a prospected person can be forgotten or have a title, logged by a member with the right (`PersonPossibilities`; ADR 0031, 0075).
- A member with the right collects a contact for at least one organization the account prospects. A contact is never alone: a contact that no organization names any more is erased (`CollectContact`, `ForgetContactsLeftWithoutOrganization`; ADR 0041, 0042).
  - The exception is a contact to attach. A member with the same right collects it without an organization, for the member who met them (`Person::collectToAttach`). That member forgets it when Prospecting reports that nobody attached it in time (`ForgetContactLeftUnattached`, ADR 0146).
  - A contact to attach is one of the account's contacts: counted, tagged and targeted like any other.
- A person who booked a slot through a host's public link is identified (`IdentifyBooker`, ADR 0164), in the host's account only. They are, in order:
  1. the account's contact who has their e-mail, whatever its case;
  2. otherwise, one who has their phone;
  3. otherwise, a contact to attach, collected for the host as the booker stated who they are.

  The identified person is published (`BookerIdentified`).
- A member with the right to manage personal data completes a contact with what a client knows about them (`CompleteContact`, ADR 0177).
  - Only empty fields are filled: their title, job title, email, phone number, and their links while there is room among the five. Nothing already logged is overwritten, and nothing changes when there is nothing to fill.
  - The email and the links stay unique among the account's contacts.
  - Beforehand, `CompareWithContact` reports, by the same rule, the fields it would fill and the fields given with a different value than the one logged.
- A person is identified from the clues a client gives about them (`IdentifyPersons`, ADR 0173). It only reads, a whole list at once, in its order, among the account's contacts.
  - It first uses their keys, the surest first (`PersonKeys`): each link, current or former, each email, each mobile; a landline, which a whole organization shares, is not one.
  - If the keys designate one contact, the person is **known**. If they designate several, or a mobile shared by two contacts, the person is **to confirm**, never known.
  - If they designate none, the person's resemblance key finds homonyms, those in the organizations the client says they work for first: the person is to confirm.
  - Otherwise, the person is **new**.
  - Without the right to manage personal data, a person is identified by their names only (`Reader::identifiesByPersonalData()`): a reader of masked data could otherwise guess what they cannot read.
  - A clue that is not a valid key of its kind designates nobody, without refusing the list.
  - A candidate is given by their id and their name, which every member of the account reads.
- A contact keeps their origin (`Origin` from the shared kernel): by hand, or by an AI acting as the member through the MCP server. The AI is never named. `CollectContact` takes it (`byAnAI`), and only the MCP server's tools set it. Completing the contact, even by an AI, keeps the origin. Their card shows it (`createdByAnAI`, ADR 0185).
- An email is unique among the persons an account keeps for a purpose (`EmailUniqueness`; ADR 0039).
- When a contact is collected or renamed, they are suspected of being a duplicate of each account contact that shares their resemblance key, in an organization naming both. The resemblance key is their first and last names, ignoring case, accents and spaces. Prospecting tells which organizations name them (`DuplicateSuspicion`, the port `OrganizationsOfContacts`, the events `DuplicateSuspected` and `DuplicateSuspicionLifted`).
  - The suspicion is lifted for the contacts they no longer resemble.
  - No suspicion without both names, nor for a member's profile.
  - A reaction suspects or lifts the other contact in return (`SuspectDuplicateInReturn`, `LiftSuspicionInReturn`).
  - Being suspected or lifted does not change a contact's modification time.
  - Forgetting a contact lifts their suspicions.
  - Only a member with the right to manage personal data reads who is suspected (`PersonReading::showsPossibleDuplicatesTo()`). After checking, that member says that a contact is not a duplicate of one they are suspected of (`DismissPossibleDuplicate`; refused otherwise: `NotASuspectedDuplicateContact`). The two are never suspected of each other again (`DismissSuspicionInReturn`, ADR 0170).
- A link designates one contact in an account, through their current links or a former one (ADR 0159, ADR 0165).
  - Collecting a contact, or logging a link on one, with a link another contact holds is refused, naming that contact (`ReferenceUniqueness`, `ReferenceAlreadyUsed`).
  - Another account, and a member for their own profile, may log it.
  - A contact keeps a link they already hold, so that two contacts who logged the same link before the rule block nothing. `app:consistency:check` lists them, for the members to merge or correct.
- Searching contacts by the beginning of their email, and recognizing a caller by their phone number, need the right to manage personal data: a reader of masked data could guess them from what they cannot read (ADR 0031, 0072). Without the right, contacts are searched by the beginning of their name only (`Reader::searchesTheEmails()`; ADR 0129).
- A contact is found by the beginning of their last name or of their first name, with or without an email, never by the first name followed by the last name (ADR 0071, 0149).
- Forgetting a contact erases their person. Their exchanges stay in Prospecting, to be redacted (ADR 0079).
- A member presents themselves to the people they give a link to: their name and their picture in clear, which are theirs to give. A person the account prospects is never presented (`MemberPresentationPolicy`, ADR 0153).

## What it publishes

- The service `WhichAreContactsService`: which persons are an account's contacts. Prospecting asks it (ADR 0089).
- The service `WhichContactsHaveTheseEmailsService`: which of an account's contacts have given addresses, whatever their case, through their emails' reservations; a forgotten contact is absent. Connectors asks it to recognize who an e-mail was exchanged with; the address never leaves the question (ADR 0154).
- The service `WhichEmailsHaveTheseContactsService`: contacts' addresses (none for a contact without one), as a member reads them. It answers only a member who reads them in clear, allowed to manage their account's personal data; a contact they do not read is absent. Connectors asks it to reimport the e-mails that member's mailbox exchanged with a contact; Connectors passes the address to the provider in its search and does not keep it.
- The service `NameContactsService`: persons' names and job titles as a member reads them, in clear or masked, and a contact's title, in clear like their name. Their e-mail and phone are given only when read in clear. A forgotten person, or one the member does not reach, is absent. Connectors asks it to name the contact of an exchange it pushes (ADR 0127) and to invite the booker of a booked exchange (ADR 0175). Campaigns asks it to render a campaign's message for a contact (ADR 0144).
- The service `PresentMembersService`: members' names and pictures, in clear, checking no caller; a prospected, forgotten or unknown person is absent. Booking asks it to say who a public page's host is (ADR 0153).
- The integration events `ContactCollected` and `ContactForgotten` (ADR 0046).
- Its contacts' facts: present, and how each one is reached (an e-mail, a phone, a mobile among them as libphonenumber types the number, a LinkedIn profile among their links). These are booleans, never the details (ADR 0141). They are published each time one changes (`ContactFactsChanged`), and by slice for an account (`ListContactFactsService`), for a context that counts them or derives from them without reading PersonalData's tables (ADR 0099). A forgotten contact gives a last fact: absent, reached by no means. A member's profile is not a contact and publishes none.

## Features

`enregistrer-un-contact`, `enregistrer-le-nom-d-une-personne-sans-espaces-en-trop`, `soupconner-un-doublon-de-contact`, `dire-qu-un-contact-n-est-pas-un-doublon`, `effacer-un-contact`, `consulter-une-personne`, `rechercher-mes-contacts`, `reconnaitre-un-appelant`, `identifier-des-personnes`, `completer-un-contact`, `changer-mon-nom`, `changer-mes-coordonnees`, `noter-les-liens-d-un-contact`, `noter-la-civilite-d-un-contact`, `changer-ma-photo`, `changer-la-fonction`, `changer-le-profil-d-un-collegue`, `publier-les-faits-de-personaldata`, and the questions in `langage-publie`.

## In the code

- `src/PersonalData/{Domain,Application}`: natural persons' personal data (`Person`: account, `Purpose`, `PersonName`, `Civility`, job title, contact details, their links (`ExternalReferences`) and former links, picture). It is read in clear or masked (`PersonReading`, `Masking`) by a `Reader`, obtained through the port `Readers` (`readerFrom()`). A profile is collected by the `Gesture` of the member who opens the account or registers the newcomer (`Person::collect()`); a contact by a `Reader` with the right, from a `NewContact` (the person, their `ContactCard` and their organizations; `collectAsContact()`), a contact to attach from their `ContactCard` (`collectToAttach()`). The `Reader` stands for the `Gesture` of the other contexts (ADR 0193).
- Queries:
  - `ConsultPerson`, `ConsultPersons` (batch);
  - `SearchContacts` (by name, and by email with the right to manage personal data);
  - `RecognizeCallingContact` (which contact has a number, with the right to manage personal data);
  - `IdentifyPersons` (which contacts the clues designate, read through the port `DesignatedContactRetriever`, ADR 0173).
- Commands that change a person (`ChangeCivility` for a contact only). For the persons an account prospects (contacts, ADR 0041, 0042): `CollectContact` (for at least one organization, checked through the port `ProspectedOrganizations`) and `ForgetContact`, with the right to manage personal data (ADR 0031).
- Reactions: `ForgetContactsLeftWithoutOrganization` reacts to Prospecting. `CollectTheFounderProfile` reacts to an account's opening and `CollectTheMemberProfile` to a member's registration (an empty profile of their own, ADR 0064, 0075). `IdentifyBooker` reacts to Booking (ADR 0164).
- No other context keeps personal data, only a `PersonId`. The exceptions: the text of Prospecting's exchanges (ADR 0074, to be redacted, ADR 0079) and their attachments (ADR 0077), the address of a Connectors connection's external account (ADR 0120), and the booker of a Booking appointment (ADR 0164).
