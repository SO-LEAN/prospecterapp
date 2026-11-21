# Prospecting

The organizations an account prospects, their contacts, the exchanges with them, and the to-dos for them. Code: `packages/core/src/Prospecting`.

## Language

- **Organization** (`Organization`): an organization an account prospects, shared by the account's whole team. It has:
  - its legal identity, including its SIRET, its establishment's number, optional (ADR 0158);
  - its contact details, and beside them its links to outside tools and its optional website (the shared kernel's `ExternalReferences`, `Website`, ADR 0160, ADR 0165);
  - its address, its position and its municipality's INSEE code, as the geocoder found them (`Location`, ADR 0142);
  - its observations, its logo, its holding, and its contacts (`PersonId`s only).
- **Possible duplicate**: an organization of the account that another one resembles, by its name, ignoring the legal form, and by its city (`ResemblanceKey`). It may be the same organization, entered twice. Both are flagged, never refused, until a member checks and states that it is not a duplicate (ADR 0162).
- **Establishment** (`Establishment`): a company's establishment as the public company directory lists it: its SIRET, its company's name, its address, its activity (its NAF code) and whether it has closed. It is proposed so that an organization's SIRET can be recorded without typing it (ADR 0158).
- **Prospector** (`Prospector`): a member as Prospecting sees them: who acts, for which account, and whether they read the personal data an exchange may hold.
- **Exchange** (`Exchange`): a real-world fact with a contact or with the organization itself: a call, a message (SMS, email, LinkedIn, Malt), a meeting or a video call (`ExchangeFacts`: channel, direction, moment, text, duration, place or link). It is kept for the organizations the contact was part of (ADR 0074, 0076). Its status (`ExchangeStatus`, ADR 0111, 0112) is planned (still to come), then done, missed when the contact did not come or answer, or cancelled.
- **Planned exchange**: a call, a meeting or a video call agreed on for later. Its text is its agenda, and it lasts a `Duration`. The member who planned it, its author, holds it. Only they can:
  - mark it done (its **report**) or missed, once its time has come;
  - move it, for a new duration if one is given;
  - cancel it (ADR 0112, 0119).

  While planned, it is in the holder's **agenda**, which tells when a `Slot` overlaps it (ADR 0111).
- **My day**: on a member's dashboard, the exchanges they still have planned on the reader's day: first those whose time has come, to handle, then the first five planned after that moment (`ConsultMyDay`). Once cancelled, done or missed, an exchange is no longer on the programme.
- **What is to process**: on a member's dashboard:
  - the exchanges they planned whose end has passed and that they have marked neither done, missed nor cancelled, oldest first, whatever their day, each handled from there (`ConsultWhatIsToProcess`). An exchange still in progress is not to process yet;
  - with them, the to-dos they still have to do, due on the reading day whatever their hour, or on earlier days, the most overdue first. Each shows the number of days since its day, read on the member's clock (ADR 0134).
- **Calendar**: read one period at a time, six weeks at most (`CalendarPeriod`, ADR 0115):
  - the exchanges a member holds, planned or logged by them, whatever their status, each at the moment it happens;
  - the to-dos they hold due on a day of the period, done or not, on the day and at the hour written, never converted to an instant (ADR 0134).
- **Late**: read at the moment of reading, on the member's clock:
  - a planned exchange whose slot has ended and that is still to mark done, missed or cancelled;
  - a to-do not done once its hour, or else its day, has passed.

  The `Lateness` policy decides it from an exchange's `ExchangeStatus` and `Slot`, and from a to-do's `Due` and whether it is done (`Due::hasPassedBy()`, ADR 0134). The read models express it in its terms through `Overdue`.
- **Timeline**: the exchanges with a contact, or those of an organization and its contacts. First what is to come after the moment of reading, soonest first; the first one is a contact's **next exchange**. Then the past, most recent first; a cancelled exchange is listed there from its cancellation (ADR 0111, 0112).
- **Venue**: where a meeting is held, its place, written freely; or the link a video call is joined by, a web address (`VideoCallLink`). The **place proposed** to meet a contact is the address of the first of their organizations that has one.
- **To-do** (`ToDo`): work a prospector has to prepare for a contact or an organization, without them present (send a CV, prepare a proposal). It is due on a day, and at an hour of that day when one is given (`Due`). The member who logged it holds it. It is ticked off once done (ADR 0113).
- **Campaign exchange**: an exchange logged for a recipient of a started campaign in Campaigns, through the campaign's channel, outbound, done or missed. It keeps the campaign's identity. The timeline and the exchange's detail name the campaign as Campaigns names it now, and name none once the campaign is erased (ADR 0145). Once the exchange is erased, its last facts still name its contact and its campaign (ADR 0152).
- **Imported exchange** (`ImportedEmail`): an e-mail a member's mailbox received from a contact, or from an organization's own address, or that the member sent to one. The import of their mailbox logs it as if they did: an e-mail received or sent, done, its text being the subject followed by the e-mail's text. It keeps where it comes from: the provider and the e-mail's identity (ADR 0154), and the id the provider gives its message in the mailbox that imported it, none for one imported before (ADR 0174). Every reader of the account sees where it comes from, in the timelines and in its detail. For those who manage personal data, its detail opens the message in Gmail once its id is kept (the API's presenter, ADR 0174).
- **Contact to attach** (`ContactToAttach`): a contact of the account that no organization names yet: a person who took a meeting with a member before anyone knew whom they work for. That member, its **holder**, holds it with its exchanges and to-dos, kept for no organization. It stays so until it is attached to an organization, or until it is erased from the start of the 30th day after its last meeting, on that meeting's clock (ADR 0146).
- **To redact**: what an exchange may still hold of a forgotten contact: its text until a correction declares it redacted, its files until they are removed (ADR 0079).

## Rules

- An organization is named by its corporate name or its email, which is unique among the account's organizations (`EmailUniqueness`; ADR 0039). It is found by the beginning of its name or of its email (ADR 0071), or recognized by its phone number (ADR 0072).
- A SIRET is fourteen digits checked by Luhn; La Poste's are checked by the sum of their digits (`Siret`). It designates one organization of an account; another account may prospect the same establishment (`SiretUniqueness`, with `SiretAlreadyUsed` naming the organization that has it). The establishments proposed for an organization are those the directory names like it: those of its municipality first, then those anywhere, each once. None are proposed for an organization without a corporate name. When the directory does not answer, the SIRET is typed by hand (`SearchEstablishments`, the `EstablishmentDirectory` port, ADR 0158).
- An organization's links and website:
  - Its links are the addresses of its pages on outside tools, five at most. Each is recognized by its source and an identifier, and the pasted address is kept to open it: a company on LinkedIn (`linkedin-company`) or on Welcome to the Jungle; any other site under its host.
  - A known tool's page that designates a person (a LinkedIn or Malt profile) is refused, and so is a page on its site that is none of its recognized pages (a school, a showcase page) (`ReferenceOfAnotherKind`, `UnrecognizedPage`, `NotAWebAddress`, `TooManyLinks`).
  - Its website is kept as its origin, with `https` when typed without a scheme, and recognized by its domain: its host without `www.` (`InvalidWebsite` for an address that is no site).
  - Links and a domain are recorded with its contact details and when it starts being prospected. Each designates one organization of an account; another account may record them too, and a dropped link is free for another organization (`ReferenceUniqueness`, `WebsiteUniqueness`, with `ReferenceAlreadyUsed` and `WebsiteAlreadyUsed` naming the organization that has them, ADR 0160, ADR 0165).
- Possible duplicates (ADR 0162):
  - When an organization is started, renamed or moved, it is suspected of being a duplicate of each organization of its account that shares its resemblance key, and no longer of those it no longer resembles (`DuplicateSuspicion`, the events `DuplicateSuspected` and `DuplicateSuspicionLifted`). The resemblance key is its corporate name, ignoring case, accents, punctuation and legal form, beside the city typed. There is no suspicion without a name or a city.
  - A reaction suspects or lifts the other organization in return (`SuspectDuplicateInReturn`, `LiftSuspicionInReturn`). Whether suspected or lifted, an organization keeps its modification moment. Stopping prospecting it lifts its suspicions.
  - Every prospector of the account can state, once checked, that it is not a duplicate of an organization it is suspected of (`DismissPossibleDuplicate`; otherwise refused: `NotASuspectedDuplicate`). The two are then never suspected of each other again (`DismissSuspicionInReturn`).
  - Its sheet and the list name its possible duplicates that are still prospected.
- An organization is completed with what a client knows about it (`CompleteOrganization`, ADR 0177). What is empty gets filled: its SIRET, links while there is room among the five, website, email and phone number. What is already recorded is never replaced, and nothing changes when there is nothing to fill. Each value stays unique among the account's organizations. Beforehand, `CompareWithOrganization` says, by the same rule, which fields it would fill and which are given differently. A collected contact joins its organizations, except one that already names them because they were attached meanwhile (`AttachCollectedContact`).
- An organization is identified from the clues a client gives about it (`IdentifyOrganizations`, ADR 0173). This only reads, for a whole list at once, in its order:
  - It looks by its keys, the surest first: its SIRET, each link, its website's domain, each email, then each email's domain taken as a website's domain, except a public mailbox (`OrganizationKeys`, `PublicMailDomains`).
  - One organization designated is **known**; several are **to confirm**, never known.
  - When none is designated, it looks by its name without the legal form (`CorporateNameKey`) among the account's first 50 organizations whose name begins the same way, those of the same city first: to confirm.
  - Otherwise it is **new**.
  - A clue that is no key of its kind designates nothing, without refusing the list.
- A holding is an organization of the same account (`AttachToHolding`).
- An organization that moves is geocoded: its position and its municipality, none when the geocoder does not find its address. One located before its municipality was kept gets it once, its address geocoded again (`LocateAgain`; the event `LocatedAgain` is by the member who started prospecting it, and the organization keeps its modification moment; nothing for one that has it, ADR 0142).
- A contact is a person the account prospects in PersonalData. It is added to an organization and removed from it; a contact that no organization names any more is erased in PersonalData (`OrphanContacts`; ADR 0041, 0042). Stopping prospecting an organization lets its contacts go.
- A contact to attach waits without an organization (ADR 0146):
  - Only its holder plans an exchange, logs an exchange or logs a to-do with it (`ContactToAttachOfAnother`), each kept for no organization (`ContactOrganizations::keepersOf()`).
  - Its page and its timeline are read like any contact's.
  - Each meeting later than its last one pushes its erasure back (`PushBackErasureOfContactToAttach`).
  - Once added to an organization (`AddContact`), its exchanges and to-dos are kept for that organization (`KeepExchangesOfAttachedContact`, `KeepToDosOfAttachedContact`), and it is no longer to attach (`AttachContactToAttach`).
  - The organization proposed to attach it to is the account's organization whose email has its email's domain; none for a public mailbox (`ProposeOrganizationToAttach`, `PublicMailDomains`).
  - A person who books a slot through a member's public link is met by that member then (`PlanBookedAppointment`, ADR 0164):
    - the person becomes a contact to attach when nobody holds them yet and no organization names them;
    - a contact to attach that another member holds passes to this member (`ContactToAttach::handOverTo()`, `ContactToAttachHandedOver`). That meeting pushes their erasure back if it is later, and what the former holder logged with them stays the former holder's;
    - the booked exchange is planned and held by the member, under the appointment's identity, its text being the subject. A video call requests its link from the member's calendar. The exchange is booked by its contact (`Exchange::bookedByAContact()`), which its timeline, its calendar and its detail say.
  - A contact to attach that is never attached is erased by the platform's daily schedule, within the 24 hours following the start of its 30th day (`EraseContactLeftUnattached`, ADR 0147, 0161): its holder forgets its person in PersonalData, its exchanges are erased (`EraseExchangesOfContactLeftUnattached`), and its to-dos go with its person.
- What an organization's state offers is a policy (`OrganizationPossibilities`): a logo to remove, a holding to detach from, a contact to remove (ADR 0088).
- An exchange is logged once it has happened, never in the future, done or missed. A planned exchange is a call, a meeting or a video call, in the future, of 5 minutes to 8 hours (half an hour when not given), planned. An exchange read without a status is done; one read as done with the facts of a call that did not go through is missed. A planned or cancelled exchange is not corrected as a whole (ADR 0111).
- Only a call, a meeting or a video call is planned (`ChannelNotPlanned`) or missed (`ChannelNotMissed`); a message (an SMS, an email, a LinkedIn or a Malt message) is received or sent.
  - Only its holder handles a planned exchange (`NotTheHolder`):
    - marked done or missed once its time has come (`NotYetDue`), its report replacing its agenda, never in the future;
    - moved to a moment to come, for a new duration if one is given, else its own (ADR 0119);
    - cancelled, optionally saying who called it off (`Canceller`);
    - once done, missed or cancelled, it no longer changes (`NotUpcoming`).
  - What an exchange offers depends on its reader and on the moment of reading (`ExchangeSituation`).
  - When its contact is forgotten, an exchange still to come is cancelled (ADR 0112).
- Venues:
  - Only a meeting has a place, and only a video call has a link, a web address (`ExchangeFacts`).
  - A video call may be planned without its link and get it later, or request one from its holder's calendar (`linkRequested`; refused for another channel: `LinkOnlyForAVideoCall`). The system keeps the link created, without an author, as a correction by nobody (`KeepCreatedVideoCallLink` reacts to Connectors' `VideoCallLinkCreated`: `Exchange::receiveLink()`, the event `VideoCallLinkReceived`, by its holder, ADR 0137).
  - A link given or received ends the request. A link given meanwhile wins, and a removed link does not come back.
  - Every prospector of the account corrects a meeting's place or a video call's link, planned or done (`CorrectExchangeVenue`, the intent `CorrectVenue`). Neither is personal data, nor something to redact once the contact is forgotten (ADR 0114).
  - An exchange corrected as a whole keeps them while its channel has one.
- Every prospector of the account reads the exchanges. Without the right to manage personal data, their text comes flagged, their attachments hidden, and their download refused; every prospector can correct the text (ADR 0078). Every prospector plans an exchange and handles those they hold; logging one that took place needs the right (ADR 0129).
- A prospector logs the exchange of a recipient of a started campaign (`NoteCampaignExchange`). It asks Campaigns whether the contact is a recipient still to handle, and the campaign's channel (`CampaignRecipients`, `RecipientInCampaign`). Refused:
  - an unknown campaign, including one of another account (`UnknownCampaign`);
  - a campaign not started (`CampaignNotRunning`);
  - a contact who is not a recipient (`UnknownRecipient`);
  - a recipient already handled (`RecipientAlreadyProcessed`).

  Otherwise the exchange is logged like any other, never in the future, and its facts name its campaign and its contact; Campaigns processes the recipient from them (ADR 0145). It is fingerprinted like any logged exchange, from where it was sent. When an AI logs it (`noteOnce`), the sending logged before is recognized before the recipient is asked, and refused as « already noted » rather than as handled already (ADR 0186).
- An e-mail that Connectors reports exchanged (`EmailExchanged`) is logged as an exchange by the member whose mailbox imported it (`NoteImportedEmail`, `Exchange::importedWithAContact()`, `importedWithTheOrganization()`). It is kept like one logged by hand, for the contact's organizations or for the organization itself.
  - Its identity derives from the account, the e-mail and its contact or organization (`ExchangeId::ofImportedEmail()`). So an e-mail is imported once with each of its correspondents, by the first member whose mailbox imported it. It is never imported again once its exchange is erased, nor when an AI or a member logged it before (its fingerprint, below).
  - Nothing is imported with a contact forgotten or an organization no longer prospected meanwhile, nor with a contact another member holds to attach.
  - Its facts are those of any exchange: Reporting counts it as a logged e-mail (ADR 0154).
  - The e-mail's files that Connectors sent to the storage are attached to it as if by its member (`Exchange::importAttachment()`; their identity derives from the exchange and the upload, `AttachmentId::ofImportedEmail()`). Each upload is kept once the exchange is written (`KeepImportedAttachment`, reacting to `AttachmentImported`; an upload already kept leaves nothing to do).
  - Its text ends with a line for each file it does not hold: left out by the import (over 10 MB, or beyond the tenth), no longer in the storage, or beyond what an exchange holds: « Pièce jointe non importée : video.mp4 (25 Mo), à ouvrir dans Gmail ». The size is rounded in megabytes from one megabyte up, in kilobytes below it, and reads « moins de 1 Ko » below a kilobyte (`AttachmentLeftOut`, ADR 0182).
- The core fingerprints an exchange logged with a contact, an organization or a campaign recipient, or imported (`ExchangeFingerprint`); a client never does. It fingerprints it from where it was read (`MessageReference`, not kept):
  - an e-mail by its `Message-ID`'s identity (the shared kernel's `MessageId`, the import's one) and its correspondent;
  - a LinkedIn message by its conversation (its identifier, or its thread's address), its direction, its minute and its text, ignoring case, punctuation and extra spaces;
  - any other exchange, and one given without a reference, by its channel, its correspondent, its direction, its minute and its text, normalized the same way;
  - a reference of another channel is refused (`ReferenceNotOfTheChannel`).

  Each fingerprint is registered once in the account, and kept once its exchange is erased, with who erased it (`NotedOnce`, `ExchangeRepository::fingerprinted()`):
  - when an AI logs it (`noteOnce`), an exchange logged before is refused: `ExchangeAlreadyNoted` names the exchange logged first, `ExchangeErasedBefore` who erased it and when, and `ExchangeNotedMeanwhile` is raised when two loggings race. The import then logs nothing;
  - logged by hand, it is never refused, and it registers its fingerprint when there is none yet.

  Forgetting a contact forgets their exchanges' fingerprints, erased or kept (`ForgetFingerprintsOfForgottenContact`). ADR 0166.
- An organization and an exchange keep their origin (the shared kernel's `Origin`): by hand, or by an AI acting as the member through the MCP server, never named. The command creating them gives it (`byAnAI` of `StartProspecting`, `NoteExchange`, `NoteOrganizationExchange`, `NoteCampaignExchange`, `PlanExchange`, `PlanOrganizationExchange`), and only the MCP server's tools set it. Nothing changes it afterwards, neither a correction by hand nor a completion by an AI. Their sheet, their timelines and an exchange's detail say it (`createdByAnAI`). An imported exchange keeps its own mark (`importedFrom`). ADR 0185.
- An exchange holds 10 files of 10 MB at most (`ExchangePossibilities`; ADR 0077); an erased exchange takes its files with it.
- An exchange is kept for the organizations its contact was part of when it was logged, and erased with the last of them. `StopKeepingExchanges` saves those still kept, and `EraseExchangeLeftWithoutOrganization` erases each of the others in its own reaction, with nothing left to do when it is already gone (ADR 0074).
- A forgotten contact's exchanges stay in their organizations' timelines, to redact; a timeline counts those to redact (`ExchangeConformity`; ADR 0079).
- To-dos (ADR 0113):
  - A to-do has a text. Like an exchange, it is kept for the organizations its contact is part of when it is logged, or for the organization itself, and goes with the last of them (`StopKeepingToDos`, then `EraseToDoLeftWithoutOrganization`).
  - A forgotten contact's to-dos are erased (`EraseToDosOfForgottenContact`).
  - A done to-do is not ticked off again, and only a done to-do is unticked (`ToDoPossibilities`).
  - Its lists are read whole: a contact's, an organization's (its own and its contacts') and a member's (those still to do). Those to do come first, soonest due first, a day without an hour before its hours; then those done, last done first.
- In an organization's timeline, an exchange's contact is present, removed from it, or forgotten (`ContactPresence`; ADR 0076, 0079).

## What it publishes

- The service `WhichAreProspectedService` (which organizations an account prospects), asked by PersonalData when a contact is collected (ADR 0089).
- The service `WhichOrganizationsHaveTheseEmailsService` (which of an account's organizations have given addresses, whatever their case, by their email reservation), asked by Connectors to recognize whom an e-mail was exchanged with (ADR 0154).
- The service `WhichEmailsHaveTheseOrganizationsService` (the address of each organization an account prospects, none for one without; one it does not prospect is absent), asked by Connectors to catch up the e-mails a mailbox exchanged with an organization itself.
- The service `NameOrganizationsOfContactsService` (for each contact, the full names of the account's organizations that name them, in the order of their identities, the first one first; a contact no organization names is absent), asked by Campaigns to render `{organisation}` in a campaign's message (ADR 0144).
- The service `ConsultHeldExchangesService`: an account's exchanges as their holder holds them. For each: holder, status, channel, moment, duration, place or link, whether a video call requests its link (ADR 0137), contact, the first organization it is still kept for with its name (none for a contact to attach), and whether it was booked through its holder's public link (ADR 0175). An erased exchange, or one no longer kept for any organization, is absent, except the exchange of a contact to attach. Connectors asks it at each fact of an exchange, to push it (ADR 0127).
- The integration events `ContactsLeftWithoutOrganization` (ADR 0046) and `ContactLeftUnattached` (ADR 0146).
- The facts of its organizations (present, located, the contacts each names, ADR 0141; its municipality's code and its address's country, ADR 0142) and of its exchanges (present, their status, the moment they happen, their contact and their campaign, ADR 0145). They are published each time one changes (`OrganizationFactsChanged`, `ExchangeFactsChanged`), and by slice for an account (`ListOrganizationFactsService`, `ListExchangeFactsService`), so that a context can count them without reading its tables (ADR 0099). An organization the account stops prospecting, or an erased exchange, gives a last fact: absent. An exchange no organization keeps any more is erased like one a member erases, and its last fact says it went with its organization (ADR 0155).

## Features

`commencer-a-prospecter`, `consulter-une-organisation`, `rechercher-mes-organisations`, `reviser-l-identite` (its SIRET too), `soupconner-un-doublon`, `dire-que-ce-n-est-pas-un-doublon`, `rechercher-un-etablissement`, `identifier-des-organisations`, `completer-une-organisation`, `changer-les-coordonnees` (its links and its website too), `demenager`, `ecrire-des-observations`, `changer-le-logo`, `retirer-le-logo`, `rattacher-a-une-holding`, `rattacher-un-contact`, `retirer-un-contact`, `arreter-de-prospecter`, `noter-un-echange`, `l-empreinte-d-un-echange`, `reconnaitre-ce-qu-une-ia-a-cree` (with PersonalData), `mener-une-campagne` (with Campaigns), `planifier-un-echange`, `traiter-un-echange-planifie`, `consulter-un-echange`, `corriger-un-echange`, `corriger-le-texte-d-un-echange`, `corriger-le-lieu-ou-le-lien-d-un-echange`, `supprimer-un-echange`, `joindre-des-fichiers-a-un-echange`, `retirer-une-piece-jointe`, `telecharger-une-piece-jointe`, `consulter-la-timeline-d-une-organisation`, `les-echanges-d-un-contact-retire-ou-efface`, `consulter-mon-calendrier`, `consulter-ma-journee`, `consulter-ce-que-j-ai-a-traiter`, `importer-un-e-mail-recu`, `importer-un-e-mail-envoye` and `importer-les-pieces-jointes-d-un-e-mail` (with Connectors), `reconnaitre-un-echange-importe` (with Connectors), `noter-une-action-a-faire`, `modifier-une-action-a-faire`, `cocher-une-action-a-faire`, `supprimer-une-action-a-faire`, `les-actions-a-faire-d-une-organisation-ou-d-un-contact-efface`, `les-contacts-a-rattacher`, `proposer-une-organisation-a-rattacher`, `effacer-les-contacts-jamais-rattaches`, `publier-les-faits-de-prospecting`, and the `langage-publie` questions.

## In the code

`src/Prospecting/{Domain,Application}`:

- Organizations and contacts:
  - the organizations an account prospects (`Organization`); its contacts (`PersonId`s only, `AddContact` / `RemoveContact`, never alone: `OrphanContacts`); `StopProspecting`;
  - the `Prospector` who starts prospecting an organization or reads (with or without the right to manage personal data), read through the `Prospectors` port (`prospectorFrom()`, implemented outside the core from Accounts' members);
  - the `KnownContacts` port (whether a person is one the account prospects, answered from PersonalData);
  - an organization's possible duplicates (`ResemblanceKey`, `DuplicateSuspicion` before the organization is saved, `Organization::reconsiderDuplicates()`, `suspectDuplicateOf()`, `liftSuspicionOf()`, `dismissSuspicionOf()`, `knowItIsNoDuplicateOf()`, `DismissPossibleDuplicate` and the reactions in return `SuspectDuplicateInReturn`, `LiftSuspicionInReturn`, `DismissSuspicionInReturn`, read as `PossibleDuplicate`, ADR 0162);
  - `ConsultOrganizationsOfContacts` (batch), `RecognizeCallingOrganization` (which organization has a number), `IdentifyOrganizations` (which organizations clues designate, read through the `DesignatedOrganizationRetriever` port, ADR 0173).
- Logging and planning exchanges:
  - the exchanges with a contact or with the organization itself (`Exchange`: a real-world fact, its `ExchangeFacts`, ADR 0074, 0076);
  - `NoteExchange`, kept for the organizations the contact is part of at that time (`ContactOrganizations`); `NoteOrganizationExchange`, from an organization's card, with it or one of its contacts;
  - `NoteCampaignExchange` for a campaign recipient (`Exchange::notedInACampaign()`, `RecipientInCampaign`, the `CampaignRecipients` and `CampaignNames` ports; `CampaignsOfTimeline` names a timeline's campaigns, ADR 0145);
  - `PlanExchange` and `PlanOrganizationExchange` likewise, planned (`Exchange::plannedWithAContact()`, `plannedWithTheOrganization()`, `ExchangeFacts::planned()`, `Duration`, ADR 0111);
  - `MarkExchangeDone`, `MarkExchangeMissed`, `MoveExchange` and `CancelExchange`, by its holder (`Exchange::markDone()`, `markMissed()`, `moveTo()`, `cancel()`, its `Cancellation`; a cancelled exchange is listed from its cancellation, `ExchangeChronology`, ADR 0112).
- Reading exchanges:
  - `ConsultTimeline` (up to the moment of reading, most recent first; what it offers: `TimelineIntent`) and `ConsultOrganizationTimeline` (its exchanges and its contacts'); `ConsultUpcomingExchanges` and `ConsultOrganizationUpcomingExchanges` (after that moment, soonest first);
  - `ConsultOverlappingExchanges`: those in a prospector's agenda that a `Slot` overlaps, read through the `AgendaRetriever` port;
  - `ConsultCalendar`: those a prospector holds in a `CalendarPeriod`, whatever their status, read through the `CalendarRetriever` port (ADR 0115), and those they may move at the moment of reading (ADR 0119); their to-dos due on a day of the period, read through `ToDoListRetriever::dueIn()`; and what is late, `Lateness` through `Overdue` (ADR 0134);
  - `ConsultMyDay`: those a prospector still has planned on a day, those of them to mark done or missed at the moment of reading, then the first five after it, read through the `AgendaRetriever` port. What an exchange offers its holder, for the calendar, the day and those to process: `HolderIntents`;
  - `ConsultWhatIsToProcess`: those a prospector planned whose `Slot` has ended at the moment of reading and that are still planned, late as `Lateness` says, oldest first, with what each offers them, read through the `AgendaRetriever` port; their to-dos still to do on the reading day and before (`Due::daysOverdueAt()`, ADR 0134); the contacts to attach they hold, the first to be erased first, read through the `ContactsToAttachRetriever` port (ADR 0146);
  - `ConsultContactToAttach`: until when a person waits for the prospector to attach them;
  - `ConsultTimelineIntents`: what a person's timeline offers without reading it, nothing for a person who is not a contact (a contact's card leads to it by a link);
  - `ConsultExchange`: the whole exchange, who logged it and who corrected it last, and what its state offers its reader at the moment of reading (`ExchangeIntent`). Read just after a move, from the moment it was moved from, it offers moving it back there while that moment is to come (`MoveExchangeBack`, ADR 0135). Every prospector of the account reads it, with the text flagged and the attachments hidden without the right (`textFlagged`, `hiddenAttachments`, ADR 0078).
- Correcting and erasing exchanges:
  - `CorrectExchangeText` (its text only, by every prospector of the account);
  - `CorrectExchangeVenue` (a meeting's place or a video call's link, `VideoCallLink`, by every prospector of the account);
  - `ProposeMeetingPlace` (the address of a contact's first organization that has one);
  - `CorrectExchange` (all its facts at once, its contact staying; the last `Correction` is kept, earlier versions nowhere, ADR 0096);
  - `EraseExchange`; exchanges are erased with their last organization (`StopKeepingExchanges`, then `ExchangeLeftWithoutOrganization` and its reaction `EraseExchangeLeftWithoutOrganization`, one per exchange).
- Attachments:
  - the files attached to an exchange (`Attachment`: 10 MB, 10 per exchange at most, ADR 0077);
  - `AttachFile` (an upload the client sent to the storage, measured then kept through the `PrivateStorage` port), `RemoveAttachment` and `DownloadAttachment` (a link signed for a moment, with the right to manage personal data);
  - an imported e-mail's files, attached by `NoteImportedEmail` and kept by `KeepImportedAttachment` (ADR 0182);
  - an erased exchange takes its files with it, and a removed attachment or logo its file. A reaction removes each from the storage once the removal is written (`DiscardAttachmentsOfErasedExchange`, `DiscardRemovedAttachment`, `DiscardRemovedLogo`, ADR 0101).
- A forgotten contact's exchanges stay, to redact (`KeepExchangesOfForgottenContact` reacts to `ContactForgotten`: `Exchange::forgetItsContact()`, ADR 0079, cancelling one still to come, ADR 0112):
  - their text until a correction declares it redacted (`madeConform` of `CorrectExchangeText` and `CorrectExchange`, the intent `MakeConform`), their files until removed;
  - in an organization's timeline, an exchange's contact is present, removed from it or forgotten (`ContactPresence`), and the timeline counts those to redact; `ConsultExchangesToMakeConform` lists them alone.
- To-dos (`ToDo`, `Due`, ADR 0113):
  - `NoteToDo` for a contact, kept for its organizations; `NoteOrganizationToDo` for the organization itself; `ReviseToDo`, `TickOffToDo`, `UntickToDo`, `EraseToDo`;
  - `ConsultToDo`, `ConsultToDos` (a contact), `ConsultOrganizationToDos` (an organization and its contacts), `ConsultMyToDos` (those a member still has to do), ordered by `ToDoList`, read through the `ToDoListRetriever` port;
  - erased with their last organization (`StopKeepingToDos`, `ToDoLeftWithoutOrganization`, `EraseToDoLeftWithoutOrganization`) or with their contact (`EraseToDosOfForgottenContact` reacts to `ContactForgotten`).
