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
