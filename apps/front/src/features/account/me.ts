import { isProblem } from '@/shared/api/problem'
import { memberFirstName, memberName, type ShownMember } from '@/shared/lib/member-name'
import type { Account } from './mutations'

const asShown = (account: Account): ShownMember => ({
  name: account.name,
  email: account.contactDetails.email,
})

/** How the signed-in member is shown: their name, else their profile's email, else "Membre sans nom". */
export const myName = (account: Account) => memberName(asShown(account))

/** How the signed-in member is greeted: their first name, else the way they are shown. */
export const myFirstName = (account: Account) => memberFirstName(asShown(account))

/**
 * A member completes a profile without a name before anything else, when their account lets them change their name
 * and their contact details.
 */
export const hasAProfileToComplete = (account: Account) =>
  !account.name.firstName &&
  !account.name.lastName &&
  Boolean(account._links.changeMyName && account._links.changeMyContactDetails)

/**
 * A reaction to a member's registration creates their profile a moment later (ADR 0101). Until then, the API refuses
 * their account because it does not find the person.
 */
export const isMyProfileBeingPrepared = (error: unknown) =>
  isProblem(error) && error.status === 404 && error.reason === 'unknownPerson'

const FIRST_DELAY_MS = 1_000
const LONGEST_WAIT_MS = 60_000

/**
 * The delay before reading a profile being prepared again, after `readings` refused readings. The delay doubles each
 * time. There is no further reading once the member has waited a minute.
 */
export function nextReadingOfMyProfile(readings: number): number | false {
  const waited = waitedBefore(readings)

  return waited < LONGEST_WAIT_MS ? Math.min(doubledDelay(readings), LONGEST_WAIT_MS - waited) : false
}

const doubledDelay = (readings: number) => FIRST_DELAY_MS * 2 ** (readings - 1)

/** The sum of the delays before the readings so far, 1 + 2 + … + 2^(n−2) seconds: 2^(n−1) − 1, one second less than the next delay. */
const waitedBefore = (readings: number) => doubledDelay(readings) - FIRST_DELAY_MS
