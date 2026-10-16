# 0039. A rule on a set of aggregates is a domain service; a unique index is its safety net

- Date: 2026-10-15
- Status: Accepted, the unique index superseded by a conditional reservation in [0048](0048-dynamodb-one-table-per-context-on-ministack.md)
- Implemented by: `packages/core/src/{PersonalData,Prospecting}/Domain/EmailUniqueness.php`, `isEmailUsed()` of their repositories, the unique constraints of `apps/api/config/doctrine/{PersonalData/Person,Prospecting/Organization}.orm.xml`

## Context

An email is unique among the persons of an account for a purpose, and among the organizations of an account (0031). One aggregate cannot check it: the rule needs the others. It was checked in the `save()` of the Doctrine repositories, and again in the in-memory double, so the scenarios tested the copy of the double, and a DynamoDB adapter would have needed a third one. It was written there when the rule spanned several tables, which no index can express; it now spans one table per context. The user asked why a business rule was in an adapter.

## Decision

- The rule is a domain service of its context, `EmailUniqueness`, a real class of the hexagon called by the handlers before `save()`. It asks the repository a question (`isEmailUsed(account, [purpose], email, except)`); the repository answers, the double too, neither decides.
- The database keeps a unique index on the same scope as a safety net: two requests may pass the check at once, the index refuses the second, and the adapter translates its violation into `EmailAlreadyUsed`, the database failure kept as previous.

## Consequences

- The scenarios test the real rule once; the doubles lost their copy.
- A new store answers the question and provides its own guarantee against races (a conditional write on DynamoDB).
- A handler that changes an email must call the service: forgetting it leaves only the index, which still refuses the duplicate.

## Alternatives considered

- Keeping the check in the repositories: the rule stays out of the hexagon and is copied into every adapter and double.
- The unique index alone: the rule would be known only to the database, and the scenarios could not express it.

## Revisit when

- A rule on a set of aggregates can no longer be answered by one question to one repository.
