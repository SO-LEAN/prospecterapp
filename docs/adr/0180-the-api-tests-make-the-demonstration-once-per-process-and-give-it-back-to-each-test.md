# 0180. The API tests make the demonstration once per process and give it back to each test

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Supersedes: for the test user pool recreated for each test, [0052](0052-cognito-signs-the-members-in.md)
- Implemented by: `apps/api/tests/Base/DataOfTheDemonstration.php`, `apps/api/tests/Base/MembersOfTheDemonstration.php`, `apps/api/tests/Base/ApiTestCase.php`, `.github/workflows/ci.yml`

## Context
Each API test recreated the seven tables, loaded the data of the demonstration through the domain and its reactions,
recreated the user pool and registered its members: some 0.55 s of setUp, two thirds of the time of the tests, the
loading of the data alone 0.33 s. The check of the API took 4 to 7 minutes on the CI, and its job showed nothing until
its end (`--output-sync=recurse`).

## Decision
- The tables and the user pool are recreated, and the data and the members of the demonstration made, once in each
  process of the tests; what they hold then is kept in memory: the items of each table, the secrets of the accounts.
- Before each test after, they are given back: the items a test changed or removed written again, those it added
  deleted, the secrets put back; the users and the clients of the pool a test added deleted, the login email and the
  password of each member of the demonstration given back. Every test still starts from the same state, whatever ran
  before it.
- A job of the CI prints each line as it comes (`--output-sync=line`); `make ci`, running several checks together,
  prints the output of each once it ends.

## Consequences
- The setUp falls to some 0.08 s; the API tests run in half the time.
- Giving back the user pool relies on what a test can change of a member of the demonstration: its login email and
  its password. A gesture changing something else of a user (disabling it, its other attributes) must be given back
  by `MembersOfTheDemonstration` too. A sign-out everywhere needs nothing: MiniStack revokes only the tokens issued
  before its second.
- A test recreating the tables or the pool itself is given the demonstration back all the same.

## Alternatives considered
- Recreating the pool only for the tests that change it (the ticket): each such test would have to say so, and one
  forgetting it would leak into the next; giving the pool back to every test costs some five calls.
- Running the lint before the tests rather than beside them: measured locally, about a minute longer.

## Revisit when
A test changes what `MembersOfTheDemonstration` does not give back, or the tests of the API move to another emulator.
