# Campaigns

Members put tags on their organizations and contacts, to find later the ones that carry them. A team prepares communication campaigns from these tags and runs them contact by contact (ADR 0138, 0144, 0145). Campaigns is downstream of Accounts, PersonalData and Prospecting, like Reporting. Code: `packages/core/src/Campaigns`.

## Language

- **Tag** (`Tag`): a `#` followed by 1 to 30 characters: unaccented capital letters, digits and underscores (#ESN, #SALON_2026). A tag is written one way whatever was typed: accents are removed, every other run of characters becomes an underscore, and nothing is left at either end (`Café owners` is #CAFE_OWNERS). Text that makes no tag is refused (`InvalidTag`).
- **Holder** (`Holder`): what carries tags, an organization (`OrganizationId`) or a contact (`PersonId`).
- **Tagging** (`Tagging`): the tags an account's members put **by hand** on one holder, sorted by name. It is Campaigns' source data.
- **Predefined tags** (`PredefinedTags`): tags proposed to every account, by family (`TagFamily`):
  - the organization type: #APPORTEUR #ESN #EDITEUR #FINAL #CABINET_RECRUTEMENT #PORTAGE #PARTENAIRE;
  - the relationship with a contact: #CLIENT #DECIDEUR #TECH #RH #ACHATS #ANCIEN_COLLEGUE #RECOMMANDE;
  - technologies: #PHP #SYMFONY #LARAVEL #JAVASCRIPT #TYPESCRIPT #REACT #VUE #ANGULAR #NODE #JAVA #DOTNET #PYTHON #GO #AWS #AZURE #GCP #DOCKER #KUBERNETES #DEVOPS #DATA #IA.

  They are only suggestions: an account's tag may have the same value.
- **Automatic tags** (`AutomaticTags`): the reserved tags Prospecter sets by itself: #EMAIL, #PHONE, #SMS, #LINKEDIN, the departments (#67, #2A, #971), the regions (#GRAND_EST…) and the surrounding big cities (#STRASBOURG…).
- **Geographic tags** (`GeographicTags`, ADR 0142): where an organization is. They are derived from the INSEE code of the municipality its geocoding returned and from its address's country (`Locality`). City first, they are:
  - the attraction area of cities of 50,000 inhabitants or more that it belongs to (INSEE zoning AAV 2020). The tag is the area's label without « (partie française) », a double name kept whole: #MARSEILLE_AIX_EN_PROVENCE;
  - its department, taken from the start of its code;
  - its region.
- **Suggested tags** (`SuggestedTags`): the predefined tags by family, then the account's own tags: the tags its holders carry that are not predefined. Separately come the reach's automatic tags (#EMAIL #PHONE #SMS #LINKEDIN), then the geographic tags its organizations carry, sorted by name. These are for searching contacts, never for putting by hand.
- **Tag selection** (`TagSelection`): the tags a member chooses to find the contacts that carry every one of them. It holds 1 to 5 tags, each written as a tag; a tag typed twice is chosen once. Anything else is refused (`InvalidTagSelection`, « Choose from 1 to 5 tags »).
- **Fact** (`Fact`: `OrganizationFact`, `ContactFact`): the state Campaigns derives contacts' tags from, with its revision. For a Prospecting organization: whether it is prospected, the contacts it names, its `Locality`. For a PersonalData contact: whether it is kept, how it is reached. A **terminal** fact (absent) ends it (ADR 0141).
- **Reach** (`Reach`): how a contact is reached: an e-mail, a phone (possibly a mobile), a LinkedIn profile. It never holds the details themselves. It sets the automatic tags #EMAIL, #PHONE, #SMS and #LINKEDIN.
- **Campaign** (`Campaign`, `CampaignId`): a communication campaign of an account's team, prepared by its author (`createdBy`). The members contact each person it targets themselves; Prospecter sends nothing (ADR 0144). A campaign has a name (`CampaignName`, 1 to 100 characters), a targeting, a message template and a status.
- **Channel** (`Channel`): how each contact is contacted: e-mail, call, SMS or LinkedIn. Each channel needs the means its automatic tag stands for: #EMAIL, #PHONE, #SMS, #LINKEDIN.
- **Targeting** (`Targeting`): the channel and the chosen tags (a `TagSelection`). Its **targeted tags** are the chosen tags plus the channel's tag, a tag chosen twice counted once, all within the 5 tags of a search.
- **Message template** (`MessageTemplate`): the text of an e-mail, SMS or LinkedIn message, or a call's pitch, 5,000 characters at most, plus an e-mail's subject. Its variables are `{title}` (Madame or Monsieur), `{firstName}`, `{lastName}`, `{organization}` and `{jobTitle}` (ADR 0190).
  - **Addressee** (`Addressee`): what a message calls a contact.
  - **Rendered message** (`RenderedMessage`): the message for one contact.
  - **Message mode** (`MessageMode`): how an AI running the campaign treats the message. It is sent **as is** (`asIs`, the default), as rendered for each person, or **adapted** (`adapted`) to each person, keeping its idea (ADR 0186).
- **Campaign status** (`CampaignStatus`): draft, then frozen, started, and ended or stopped (ADR 0144). What each status allows (`CampaignIntent`, `CampaignPossibilities`):
  - a draft is revised, discarded and frozen;
  - a frozen campaign is revised (its name and message) and discarded, its recipients are added and removed, and it is started while it keeps a recipient;
  - a started campaign is run (`RunCampaign`) and stopped;
  - until it is stopped or ended, its daily cap is set (`SetDailyCap`);
  - a stopped or ended campaign allows nothing.

  A campaign **has started** once it is started, stopped or ended: its name, its message and its recipients no longer change. Only a started campaign's daily cap still does. It **is running** (`isRunning`) while started.
- **Recipients** (`Recipients`, `Recipient`, `RecipientStatus`): the contacts a frozen campaign keeps. They are kept by identity only (their names belong to PersonalData), each once, in the order added. Each one has a status saying where the team stands with them:
  - to handle (`pending`);
  - **processed** (`processed`) by the exchange logged for them in the campaign, done, or missed for a call that did not go through. The identity of that exchange is kept (ADR 0152);
  - **dismissed** (`dismissed`) without an exchange, for a reason when one is given;
  - no longer to handle (`erased`): PersonalData erased the contact from a started campaign while they were still to handle.

  An **erased** recipient is kept without their name (ADR 0145), along with what the team did with them: processed, missed or dismissed, but without the exchange or the reason.
- **Progress** (`CampaignProgress`): the processed and dismissed recipients, out of all of them, and the recipients processed today, on the member's clock.
- **Daily cap** (`DailyCap`): the most recipients of a campaign processed a day, 1 or more, so that the member's mailbox or LinkedIn account is not flagged. It is only a guard: an AI stops at it and asks its member, who may go beyond it. A campaign may have none.
- **A contact's tags** (`ContactTags`):
  - its own tags, put on it by hand;
  - the tags it **inherits**: put by hand on the organizations that name it, or set by where those organizations are (`InheritedTag`, with those organizations);
  - the **automatic** tags of its reach.
- **An organization's tags**: the ones put by hand, then the automatic ones for where it is.

## Rules

- A member tags an organization their account prospects or a contact of their account (`PutTags`), as Prospecting and PersonalData tell (`TaggableHolders`). Anything else is unknown (`UnknownOrganization`, `UnknownContact`). Every prospector tags, with or without the right to manage personal data.
- A tag already carried is carried once. An automatic tag is never put by hand (`AutomaticTag`, « The tag #EMAIL is set automatically »), and it refuses the whole action.
- A member takes a tag off (`TakeOffTag`). This is refused when the tag is not carried (`TagNotCarried`).
- The suggested tags (`ConsultSuggestedTags`) are derived from the tags carried. A tag nothing carries any more is no longer suggested, and another account's tag never is.
- An organization's tagging is forgotten once Prospecting says the account no longer prospects it (`ForgetTagsOfUnprospectedOrganization`, reacting to its terminal `OrganizationFactsChanged`). A contact's tagging is forgotten once PersonalData says the contact is forgotten (`ForgetTagsOfForgottenContact`, reacting to `ContactForgotten`). So when the account stops prospecting an organization, its tags go with it, and so do the tags of the contacts it erases.

- A contact carries the tags put by hand on each organization that names it. These follow the organizations' tags and the contacts attached and removed. A tag the contact carries by hand and also inherits is its own, shown once. An inherited tag names every organization it comes from (`ContactTags`).
- A contact carries #EMAIL with an e-mail, #PHONE with a phone, #SMS when that phone is a mobile (libphonenumber), and #LINKEDIN with a LinkedIn profile. These are never put on or taken off by hand. Every prospector reads them, with or without the right to manage personal data, for their own account's contacts only (`ConsultContactTags`, a set; `UnknownContact` otherwise).
- An organization carries its municipality's geographic tags (ADR 0142):
  - the surrounding big city when the municipality belongs to an attraction area of 50,000 inhabitants or more, plus its department and region;
  - outside such an area, or in a municipality more recent than the tables, only its department and region;
  - an arrondissement of Paris, Lyon or Marseille counts as its city;
  - a municipality merged since 2020 keeps the tags of its last geography;
  - nothing without a municipality (an address the geocoder did not find), and nothing for an address whose country is given and is not French (FR, GP, MQ, GF, RE, YT), whatever the geocoder found.

  Its contacts inherit them. Every prospector reads them (`ConsultOrganizationTags`) and never puts them by hand.
- A contact's tags are derived when they are read, from the facts Campaigns keeps (ADR 0141). A received fact replaces the known fact of its aggregate when its revision is higher. The terminal fact replaces any other, and none replaces it. A fact received twice counts once (`FactSuccession`, `FactRecorder`). A fact published before the facts carried the contacts and the reach (the first version of the facts) is not recorded.
- A member finds their account's contacts that carry every chosen tag (`SearchContactsByTags`): own, inherited and automatic tags alike, as `ContactTags` derives them for each contact its fact keeps. Results come newest first, a slice after a cursor, with the total count (ADR 0143). Every prospector searches by tags, with or without the right to manage personal data; PersonalData then reads the contacts as the prospector may read them.
- A member prepares a campaign of their account (`PrepareCampaign`) as a draft: a name, a channel, at least one chosen tag, a message. It is refused:
  - without a name (`CampaignWithoutName`, « A campaign has a name »);
  - with a name of more than 100 characters (`CampaignNameTooLong`);
  - with an unknown channel (`UnknownChannel`);
  - with no tag, or more than 5 counting the channel's tag (`InvalidTagSelection`);
  - with a message of more than 5,000 characters (`MessageTooLong`);
  - with a subject for a channel other than e-mail (`SubjectOnlyForAnEmail`);
  - with a message mode other than as is or adapted (`UnknownMessageMode`, « An AI sends the message as is or adapted »);
  - with a daily cap below 1 (`InvalidDailyCap`, « A daily cap is at least 1 send »). Without one, the campaign has no cap.

  The mode is as is when not given, and is revised with the message until the campaign starts.
- A draft targets the account's contacts that carry every one of its targeted tags, as `ContactTags` derives them now. Nothing of its target is stored: the target follows the contacts' tags and contact details (ADR 0144). The screen reads it as the tag search for its targeted tags.
- Any team member revises a whole draft (`ReviseCampaign`) or discards it (`DiscardCampaign`), whoever prepared it. What the campaign's state does not allow is refused (`NotPossibleInItsState`). Another account's campaign is unknown (`UnknownCampaign`).
- A member freezes a draft (`FreezeCampaign`): the contacts its search finds now become its recipients, for good. This is refused when the search finds nobody (`CampaignWithoutRecipient`, « A campaign has at least one recipient ») or more than 500 contacts (`TooManyRecipients`). Once frozen, the list no longer follows the tags, and the channel and tags no longer change (`FrozenSearch`, « The search of a frozen campaign does not change »). Its name and message still change, and it can still be discarded.
- A member adds to a frozen campaign the contacts found by another tag search, with the channel's tag added (`AddRecipientsByTags`), or one chosen contact (`AddRecipient`). This is refused for a contact without the means its channel needs (`UnreachableContact`, « Grace Hopper cannot be reached by email », named as the member reads them), or for someone who is not a contact of the account (`UnknownContact`). A contact already kept is kept once, 500 at most. A member removes a recipient (`RemoveRecipient`), but never the last one (`CampaignWithoutRecipient`); a contact the campaign does not keep is unknown (`UnknownRecipient`).
- A contact PersonalData forgets leaves the recipients of every frozen campaign that keeps it (`ForgetRecipientOfForgottenContact`, reacting to `ContactForgotten`), even when it is the last one: a person's erasure wins over the team's list. A frozen campaign emptied this way is in effect cancelled. It stays frozen with no recipient, is listed as such, can still be discarded, and does not start while it keeps none (`CampaignWithoutRecipient`, « A campaign has at least one recipient »).
- A campaign that has started keeps the forgotten contact, without its name (« Contact effacé » on the screen). The erasure takes the contact's identity, never what the team did:
  - a processed recipient (missed or not) or a dismissed one stays so and counts in the progress;
  - a recipient still to handle no longer is (`erased`);
  - neither the exchange it links to nor its dismissal reason, which may say something about the person, is kept;
  - a running campaign then ends once nobody is left to handle (ADR 0145).
- A member starts a frozen campaign (`StartCampaign`), and the moment is kept. Its recipients, message and channel no longer change (`StartedCampaign`, « A started campaign does not change »), and it can no longer be discarded (`StartedCampaignNotDiscarded`, « A started campaign is stopped, not deleted »). Only a frozen campaign starts (`CampaignNotFrozen`, « Only a frozen campaign starts »): neither a draft nor a stopped one. A member stops a started campaign (`StopCampaign`), and the moment is kept: it stays listed and allows nothing more.
- A member runs a started campaign one recipient after another (ADR 0145). The exchange they log for a recipient to handle in the campaign (Prospecting's `NoteCampaignExchange`) processes that recipient, done or missed, through its facts (`ProcessRecipientOfCampaignExchange`, reacting to `ExchangeFactsChanged`). Or they dismiss the recipient without an exchange (`DismissRecipient`, for a reason when one is given).
  - Only a started campaign is run (`CampaignNotRunning`, « Only a started campaign is run »).
  - A recipient is handled once (`RecipientAlreadyProcessed`, « This recipient is already processed »).
  - A contact who is not a recipient is unknown (`UnknownRecipient`).

  Once nobody is left to handle, the campaign is **ended**, and the moment is kept; it allows nothing more. Processing is a system reaction: a recipient already handled, a campaign no longer running, or an exchange of no campaign changes nothing, and nothing is recorded.
- A processed recipient links to the exchange logged for them, and the exchange links to its campaign (ADR 0152). A member who erases that exchange puts the recipient back to handle (`ReopenRecipientOfErasedExchange`, reacting to its last `ExchangeFactsChanged`, absent, which names its campaign and its contact):
  - an ended campaign is started again; a stopped one stays stopped;
  - an exchange erased with its organization, when the account stopped prospecting the last organization keeping it, never does this: the recipient stays processed without it (ADR 0155);
  - another exchange of the recipient, a recipient erased since, or an erased campaign changes nothing.

  This is a system reaction: nothing is recorded. A campaign erased by the operations leaves its exchanges in their timelines, no longer named by any campaign.
- The operations erase a campaign whatever its status (`EraseCampaign`, `app:campaign:erase <id>`: cleaning up a test, a person's request). It is found by its identity alone, across the accounts (`TaggedAccounts`). No member does this: the application never deletes a started campaign.
- An account's campaigns are listed whole, newest first, each with the count of contacts it targets (`ListCampaigns`, `ConsultCampaign`, `CampaignTargets`). For a draft, that is the contacts its search finds now, every contact's tags derived once (`TagsOfEveryContact`, ADR 0144, 0143). For a frozen campaign, it is its recipients. Each campaign shows its processed and dismissed recipients, none before it starts (`CampaignProgress`).
- A member sets the daily cap of a draft, a frozen or a started campaign, or takes it off (`SetDailyCap`). A cap below 1 is refused, as at preparation. A stopped or ended campaign does not change (`StartedCampaign`). A recipient is processed at the moment its exchange's facts tell it, and an erased recipient still counts. The recipients processed today are those processed on the member's day, on their clock; the screen reads them on the campaign (`ConsultCampaign`, with its daily cap). Going beyond the cap is never refused.
- An AI runs a started campaign for its member (ADR 0186). It asks for the next recipient (`NextRecipient`), on its member's clock: the first one still to handle in the order they were added, with the message rendered for them, its subject, its mode, how many are left, the daily cap and the recipients processed today. Once the cap is reached, the recipient is still given, with the cap said reached: the AI stops and asks its member whether to go beyond it. This is refused once the campaign is no longer running (`CampaignNotRunning`). The campaign is read again at each step, so a recipient handled on the screen meanwhile is no longer returned. Nor is a recipient Prospecting already logged an exchange of the campaign with, before the reaction processes them: `NextRecipient` asks it (`CampaignExchanges`, through `WhichContactsHaveExchangesOfCampaignService`), and the next recipient and the count left pass over them; with nobody else left, it is refused as no longer running (ADR 0195). The AI sends the message itself, then logs it as the recipient's exchange (`NoteCampaignExchange`, logged once), keeping the text it actually sent. An e-mail the import of Gmail logged first joins the campaign as the AI logs it (ADR 0195). Or it dismisses the recipient, for a reason.
- The core renders a campaign's message for a contact of the account (`RenderCampaignMessage`). Each variable takes what is known of the contact: the names, title and job title as PersonalData lets the member read them, and the first organization Prospecting lists for them. A variable takes nothing when nothing is known, and any other text is kept. The subject is rendered the same way. A person the member reads as not a contact of the account is unknown (`UnknownContact`).
- Only a member with the right to manage personal data runs campaigns, for every action and every read. The API requires it (ADR 0081); the core checks no role.
- A rebuild records again the facts of every account, or of one, as Prospecting and PersonalData give them from their state (`RebuildTags`). It goes through the same succession, except that a rebuilt fact replaces a kept fact at the same revision, since that one may have been recorded before the facts carried what they carry now (ADR 0184). Each fact is its own unit of work, without a version or a wait.

## What it publishes

- The service `WhichRecipientsArePendingService`: whether an account's campaign has started, its channel, and which of the asked contacts it keeps as recipients and still has to handle; an unknown campaign is absent. Prospecting asks it before logging a recipient's exchange (ADR 0145).
- The service `NameCampaignsService`: the names of an account's campaigns, an unknown one absent. Prospecting asks it to name the campaign of the exchanges in a timeline (ADR 0145).

Its domain events, `OrganizationTagsChanged`, `ContactTagsChanged`, `CampaignPrepared`, `CampaignRevised`, `CampaignDiscarded`, `CampaignFrozen`, `CampaignRecipientsChanged`, `CampaignStarted`, `CampaignStopped`, `DailyCapSet` and `RecipientDismissed`, stay in the context.

## Features

`taguer-mes-organisations-et-mes-contacts`, `porter-les-tags-de-mes-organisations-et-de-mes-coordonnees`, `porter-les-tags-de-l-adresse-de-mes-organisations`, `rechercher-mes-contacts-par-tags`, `preparer-une-campagne`, `figer-les-destinataires-d-une-campagne`, `demarrer-et-arreter-une-campagne`, `mener-une-campagne`, `fixer-un-plafond-d-envois-par-jour`.

## In the code

- `src/Campaigns/Domain`:
  - `Tag`, `Holder` and `HolderKind`, `TagFamily`, `TagSelection`, the aggregate `Tagging`;
  - the facts (`Fact`, `OrganizationFact`, `ContactFact`), `Reach`, `Locality`, `ContactTags` and `InheritedTag`;
  - the policies `PredefinedTags`, `AutomaticTags`, `GeographicTags` (its tables generated from INSEE data in `data/insee-geography.php` by `bin/insee-geography.php`, never edited by hand) and `FactSuccession`;
  - the domain service `TaggableHolders`;
  - the aggregate `Campaign` (its identity `CampaignId` is in the shared kernel, ADR 0145), `CampaignName`, `Channel`, `Targeting`, `MessageTemplate`, `CampaignBrief` (its name, targeting and message, as a member prepares or revises them), `Candidate` (a contact added by hand, with their tags and their `Addressee`), `MessageMode`, `Addressee`, `RenderedMessage`, `CampaignStatus`, `CampaignIntent`, `Recipients`, `Recipient` (when it was processed among what it keeps), `RecipientStatus`, `RecipientOutcome` (how the exchange that processed a recipient went, done or missed), `DailyCap`;
  - the policies `CampaignPossibilities` and `CampaignProgress` (its processed and dismissed recipients, and those processed on the member's day);
  - the repositories `TaggingRepository`, `FactRepository` and `CampaignRepository`;
  - the ports, implemented by anticorruption layers: `Campaigners` (a member's account, from Accounts), `Addressees` (what a message calls a contact, from PersonalData and Prospecting), `Taggers` (a member's account, from Accounts), `TaggedAccounts` (the accounts, from Accounts, whose tags are rebuilt and among which a campaign is erased), `TaggableOrganizations`, `OrganizationsAndTheirContacts` and `CampaignExchanges` (which recipients have an exchange of the campaign, ADR 0195) (Prospecting), `TaggableContacts` and `ContactsReach` (PersonalData).
- `src/Campaigns/Application`:
  - `PutTags`, `TakeOffTag`, `ConsultOrganizationTags`, `ConsultContactTags`, `ConsultSuggestedTags` (`SuggestedTags`), `SearchContactsByTags` (`TaggedContactPage`), `FactRecorder`, `RebuildTags` without a command's unit of work;
  - `PrepareCampaign`, `ReviseCampaign`, `DiscardCampaign`, `ListCampaigns`, `ConsultCampaign` (`CampaignSheet`, `CampaignTargets`), `RenderCampaignMessage` (`CampaignMessage`), `NextRecipient` (`RecipientToHandle`), `FreezeCampaign`, `AddRecipientsByTags`, `AddRecipient`, `RemoveRecipient`, `StartCampaign`, `StopCampaign`, `SetDailyCap`, `DismissRecipient`, `EraseCampaign`, `TagsOfEveryContact`;
  - the published queries `WhichRecipientsArePending` and `NameCampaigns`;
  - the reactions `ForgetTagsOfUnprospectedOrganization`, `ForgetTagsOfForgottenContact`, `ForgetRecipientOfForgottenContact`, `ProcessRecipientOfCampaignExchange`, `ReopenRecipientOfErasedExchange`, `RecordOrganizationFacts` and `RecordContactFacts`;
  - the ports `TagsRetriever` (the taggings, and where the organizations are: `localityOf()`, `localitiesIn()`), `ContactTagsRetriever`, `TaggedContactsRetriever` and `CampaignSheetRetriever` (a campaign found or not, the names of some; `RecipientInSheet`), and `ContactTagSources`, which derives its contact's tags, including, among the inherited ones, the tags for where its organizations are.
- In the API: its table and items in `docs/architecture/persistence.md`, its endpoints in `docs/architecture/api.md`, its subscriptions in `docs/architecture/contexts.md`, `app:tags:rebuild` in `docs/architecture/messaging.md`.
