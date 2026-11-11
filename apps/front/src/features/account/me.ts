import { isProblem } from '@/shared/api/problem'
import { memberFirstName, memberName, type ShownMember } from '@/shared/lib/member-name'
import type { Account } from './mutations'

const asShown = (account: Account): ShownMember => ({
  name: account.name,
  email: account.contactDetails.email,
})

/** How the signed-in member is shown: their name, else the email of their profile, else "Membre sans nom". */
export const myName = (account: Account) => memberName(asShown(account))

/** How the signed-in member is greeted: their first name, else as they are shown. */
export const myFirstName = (account: Account) => memberFirstName(asShown(account))

/**
 * A profile without a name is completed by its member before anything else, when their account offers them to change
 * their name and their contact details.
 */
export const hasAProfileToComplete = (account: Account) =>
  !account.name.firstName &&
  !account.name.lastName &&
  Boolean(account._links.changeMyName && account._links.changeMyContactDetails)

/**
 * The profile of a member is created by a reaction to their registration, a moment after it (ADR 0101): until then,
 * their account is refused for a person not found.
 */
export const isMyProfileBeingPrepared = (error: unknown) =>
  isProblem(error) && error.status === 404 && error.reason === 'unknownPerson'

const FIRST_DELAY_MS = 1_000
const LONGEST_WAIT_MS = 60_000

/**
 * The delay before reading again a profile being prepared, after `readings` readings refused: doubled each time, none
 * once the member waited a minute.
 */
export function nextReadingOfMyProfile(readings: number): number | false {
  const delay = FIRST_DELAY_MS * 2 ** (readings - 1)
  const waited = delay - FIRST_DELAY_MS

  return waited < LONGEST_WAIT_MS ? Math.min(delay, LONGEST_WAIT_MS - waited) : false
}
