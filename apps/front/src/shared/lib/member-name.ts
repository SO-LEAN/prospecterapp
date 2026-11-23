import { i18n } from '@/shared/i18n'

/** A member as the API shows them: their profile's name and email. Both may be empty. */
export type ShownMember = {
  name: { firstName?: string | null; lastName?: string | null }
  email?: string | null
}

/** "Ada Lovelace", or else the profile's email, or else "Membre sans nom". */
export function memberName({ name, email }: ShownMember): string {
  return [name.firstName, name.lastName].filter(Boolean).join(' ') || email || i18n.t('members.unnamed')
}

/** How the app greets a member: by their first name, or else by the name it shows. */
export function memberFirstName(member: ShownMember): string {
  return member.name.firstName || memberName(member)
}
