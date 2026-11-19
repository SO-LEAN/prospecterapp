# 0160. The LinkedIn page and the website of an organization each designate one organization of its account

- Date: 2026-11-19
- Type: domain
- Status: Accepted
- Supersedes: for the consequence « the organizations and the accounts have no LinkedIn profile » as far as the organizations are concerned, [0139](0139-the-linkedin-profile-is-a-persons-own-kept-in-one-form.md)
- Implemented by: `packages/core/src/Prospecting/Domain/Model/{LinkedInPage,Website,Organization}.php`, `packages/core/src/Prospecting/Domain/Service/{LinkedInPageUniqueness,WebsiteUniqueness}.php`, `packages/core/src/Prospecting/Application/{ChangeContactDetails,StartProspecting}`, `packages/core/features/{changer-les-coordonnees,commencer-a-prospecter,consulter-une-organisation}.feature`, `apps/api/src/Prospecting/Persistence/DynamoOrganizationRepository.php`, `apps/api/src/Prospecting/{Input/OrganizationContactDetailsInput,Resource/OrganizationContactDetailsResource}.php`, `apps/front/src/features/organizations/{organization-contact-details,components/contact-details-section,components/organization-on-the-web}.tsx`

## Context

From LinkedIn, an organization is recognized by the page of its company (`linkedin.com/company/<identifier>`), and a company abroad, without SIRET (0158), by its website; a member, or an AI working for them, must not enter the same organization twice. The contact details of the shared kernel are shared by the persons, the organizations and the accounts, and 0139 kept the LinkedIn profile of a person beside them in PersonalData, the page of a company out of its scope. Several establishments of one company share its page and its site: they are told apart by their SIRET, and attached to a holding carrying the page and the site.

## Decision

- The LinkedIn page and the website are two value objects of Prospecting, held by the organization beside its contact details, noted with them (`ChangeContactDetails`) and when it starts being prospected (`StartProspecting`), never required; the account's own organization has none.
- The page is kept in one form, `https://www.linkedin.com/company/<identifier>`, the identifier in lower case, without the page under it; anything else, a profile of a person, a school or a showcase page included, is refused on its field (`InvalidLinkedInPage`).
- The site is kept as its origin, `<scheme>://<host>` in lower case, `https` when typed without its scheme; its **domain**, its host without `www.`, designates the organization (`acme.fr/blog` is of the domain of `https://www.acme.fr`), with no list of the public suffixes: `shop.acme.fr` is a domain of its own. An address without a host holding a dot, or of another scheme than http(s), is refused (`InvalidWebsite`).
- A page, a domain, designates one organization of an account (`LinkedInPageUniqueness`, `WebsiteUniqueness`, as `SiretUniqueness`): the refusal names the organization that has it (`LinkedInPageAlreadyUsed`, `WebsiteAlreadyUsed`, their context `organization`), at the creation as at a change of the contact details; another account may note them.
- The safety net (0039, 0048): their reservations in the table of Prospecting, `LINKEDIN_PAGE#<account>#<page>` and `WEBSITE#<account>#<domain>`, written under a condition in the transaction of the organization, released when they change or the organization is no longer prospected; `app:keys:rebuild` writes them again.

## Consequences

- A page designated by its numeric identifier (`/company/12345`) and the same page by its name are two values.
- Two organizations of a company sharing its site are refused: the second takes the site of its holding, or none.
- The organizations stored before have neither; nothing to rebuild before they note them.
- A duplicate entered before its page or its site is known is not found: the suspicion of a duplicate by the name and the city, which does not block, is to come.

## Alternatives considered

The maintainer weighed noting the page and the site on the sheet only, as the SIRET (0158), the form of the creation unchanged: a duplicate would be created, then refused once its page noted; the uniqueness at the creation was kept.

## Revisit when

A page is found noted twice under its numeric identifier and its name, or two organizations of one company need the same site.
