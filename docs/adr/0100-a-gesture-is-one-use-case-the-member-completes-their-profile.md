# 0100. A gesture is one use case: the member completes their profile at their first sign-in

- Date: 2026-11-10
- Type: architecture
- Status: Accepted
- Supersedes: for « a gesture of the console made of several commands dispatches them together (`CommandBus::dispatchTogether()`) », [0095](0095-a-command-writes-its-aggregate-and-its-outbox-all-at-once.md)
- Implemented by: `apps/api/src/SignIn/Command/{OpenAccountCommand,RegisterMemberCommand}.php`, `apps/api/src/Shared/CommandBus.php`, `apps/front/src/routes/_app.tsx`, `apps/front/src/features/account/components/complete-my-profile-form.tsx`, `apps/front/src/shared/lib/member-name.ts`

## Context

A transaction spans one use case, ideally one aggregate; what another context must do follows from the events, in its own transaction (ADR 0042, 0098). Two gestures of the console grouped use cases of Accounts and of PersonalData in one unit of work to fill in the profile of the person they registered: `app:account:open` (`OpenAccount`, then `ChangePersonContactDetails`) and `app:member:register` (`RegisterAMember`, then `RenamePerson` and `ChangePersonContactDetails`). The outbox refuses a commit publishing in two contexts (ADR 0098): these gestures could not go through it, and the asynchronous reactions depend on it. Yet PersonalData creates the profile of a member empty on `AccountOpened` and `MemberRegistered` (ADR 0064, 0075), and the data of a person are theirs to give (ADR 0031).

## Decision

- A gesture runs one use case: `app:account:open` runs `OpenAccount`, `app:member:register` runs `RegisterAMember`, then each registers the credentials. The options `--first-name` and `--last-name` are gone; the login email is not the email of the profile. `CommandBus::dispatchTogether()` and `RefusalTranslatingUnitOfWork`, left without users, are removed.
- The member completes their profile: while the profile of the signed-in member has no name, the SPA shows « Complétez votre profil » before anything else, asking their first name and their name, the email of the profile proposed as their login email (`GET /api/me/sign-in`), theirs to change. It sends them through the endpoints of the member, each its own use case and transaction: the email first (`PATCH /api/me/contact-details`), the one a refusal may stop (already the email of another member of the account, shown on its field), then the name (`PATCH /api/me/name`). No personal data goes through Accounts.
- Until then, a member is shown by their name, else the email of their profile, else « Membre sans nom » (`shared/lib/member-name.ts`), in the menu, the home page and the exchanges.

## Consequences

- Each gesture commits in one context: the gestures of the console go through the outbox.
- A member registered from the console signs in to an empty profile; the members of the demonstration have a name, a third one, `newcomer@solean-it.io`, an empty profile for the journey of the completion.
- The completion is two use cases: an email accepted then a name failing leaves the email given, the screen still shown until the name is; sending again gives the same email again.

## Alternatives considered

- A BFF of the SPA orchestrating several use cases for one gesture: left aside, no gesture needs it once the member fills in their profile.

## Revisit when

A gesture needs what several contexts do together, at once, for its user.
