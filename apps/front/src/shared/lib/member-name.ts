import { i18n } from '@/shared/i18n'

/** A member as the API shows them: the name of their profile and its email, both possibly empty. */
export type ShownMember = {
  name: { firstName?: string | null; lastName?: string | null }
  email?: string | null
}

/** "Ada Lovelace", else the email of the profile, else "Membre sans nom". */
export function memberName({ name, email }: ShownMember): string {
  return [name.firstName, name.lastName].filter(Boolean).join(' ') || email || i18n.t('members.unnamed')
}

/** How a member is greeted: by their first name, else as they are shown. */
export function memberFirstName(member: ShownMember): string {
  return member.name.firstName || memberName(member)
}
