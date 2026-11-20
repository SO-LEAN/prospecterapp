# 0165. A person and an organization have links to any tool, each recognized by its source and identifier, unique in the account

- Date: 2026-11-20
- Type: domain
- Status: Accepted
- Supersedes: for the form and the model of the LinkedIn profile, [0139](0139-the-linkedin-profile-is-a-persons-own-kept-in-one-form.md); for the LinkedIn profile as the only reference a contact is recognized by, its former ones included, [0159](0159-a-linkedin-profile-designates-one-contact-of-the-account-its-former-ones-still-recognize-them.md); for the LinkedIn page of an organization, [0160](0160-the-linkedin-page-and-the-website-of-an-organization-each-designate-one-organization-of-its-account.md)
- Implemented by: `packages/core/src/SharedKernel/Domain/{ExternalReference,ExternalReferences,KnownSource,Designated}.php`, `packages/core/src/SharedKernel/Domain/Exception/{NotAWebAddress,ReferenceOfAnotherKind,UnrecognizedPage,TooManyLinks}.php`, `packages/core/src/SharedKernel/Application/ExternalLink.php`, `packages/core/src/{PersonalData,Prospecting}/Domain/Service/ReferenceUniqueness.php`, `packages/core/features/{noter-les-liens-d-un-contact,changer-les-coordonnees,commencer-a-prospecter}.feature`, `apps/api/src/{PersonalData,Prospecting}/Persistence`, `apps/api/src/Consistency/ReferencesOfTheContacts.php`, `apps/front/src/shared/forms/links-field.tsx`, `apps/front/src/shared/ui/external-links.tsx`

## Context

A person or an organization is found on many tools beside LinkedIn: Malt, Welcome to the Jungle, others nobody foresees yet. The LinkedIn profile of a person (0139, 0159) and the LinkedIn page of an organization (0160) each had their model, their uniqueness, their reservation and their field; a new tool would have needed all of them again, before the tools of the MCP server rely on them to never enter the same person or organization twice. A contact forgotten with the others an organization leaves alone costs a write per reference they hold, in one transaction of 100 writes at most (0159).

## Decision

- A **link** is an external reference of the shared kernel: a **source** and an **identifier**, taken from the address pasted, which recognize it, and the address pasted itself (trimmed, `https` when typed without its scheme), which opens it.
- The known sources recognize their pages, find the identifier in them (in lower case, without what follows) and say what they designate: a profile on LinkedIn (`/in/`) or on Malt (`malt.fr/profile/`) a person, a company on LinkedIn (`/company/`) or on Welcome to the Jungle (`/<language>/companies/`) an organization. A page of a known source designating the other kind is refused (`ReferenceOfAnotherKind`), as an address on its site that is none of its pages (a school, a showcase page, `UnrecognizedPage`), as 0160 did.
- Any other site is a source of its own, its host without `www.`, the address in lower case without its scheme, `www.`, its query, its fragment nor its final `/` its identifier: adding a known source later is one recognition more, `app:keys:rebuild` recognizing again every link by its address.
- A sheet has five links at most (`TooManyLinks`), never required; a person keeps their last three former links, whatever their source.
- A link designates one person and one organization of the account at most, the refusal naming the one that has it (`ReferenceUniqueness` of each context, `ReferenceAlreadyUsed`); the former links of a contact still recognize them (0159 extended to every source), the links an organization drops are free. A person's links are personal data in PersonalData, masked as one link of stars whatever their number; an organization's are in Prospecting. The website stays apart, recognized by its domain (0160).
- The safety net: the reservation `REFERENCE#<account>#<source>#<identifier>` in the table of each context, as the profiles and the pages had theirs; `app:keys:rebuild` takes the LinkedIn profiles and pages written before as links and deletes their former reservations.

## Consequences

- A contact forgotten costs eleven writes at most (the person, their email, their first name, five links, three former ones): eight contacts in the transaction forgetting those an organization leaves alone, down from fourteen.
- The links shown on a sheet open what was pasted, whatever its case or its query; two addresses of one reference keep the first pasted.
- `app:keys:rebuild` runs once on each instance, for the profiles and pages stored before; until it runs, a profile or a page noted before is shown as a link but not reserved under its reference.
- Whether a contact is reached on LinkedIn, for Campaigns, is whether they have a link of the source `linkedin`; the channel LinkedIn of the exchanges and the campaigns is unchanged.

## Alternatives considered

The maintainer weighed, on 2026-11-20:
- no cap on the links: forgetting the contacts an organization leaves alone would no longer fit one transaction; three former links per source rather than three in all: fewer contacts per forgetting;
- the address shown rebuilt from the source and the identifier: one form, but a page of an unknown site depending on its case or its query would no longer open;
- an address on the site of a known source that is none of its pages taken as a link of an unknown source under its host: it would have undone the refusal of the school and showcase pages of 0160.

## Revisit when

A sheet needs more than five links, or an organization leaves more than eight contacts alone at once.
