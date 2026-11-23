import type { components } from '@/shared/api/schema'
import type { CampaignMessage, Channel } from './queries'

type ContactDetails = components['schemas']['PersonContactDetailsResource']

/** The source of a person's LinkedIn profile among their links. */
const LINKEDIN_PROFILE = 'linkedInProfile'

/**
 * What opens the channel's tool for a recipient: the mail, the SMS, the call, or the profile in a new tab.
 */
export type Opening = { href: string; external: boolean }

/**
 * How the channel reaches a recipient: their e-mail, their phone, or their LinkedIn profile. Undefined when
 * unknown.
 */
export function meansOf(channel: Channel, contactDetails: ContactDetails): string | null {
  switch (channel) {
    case 'email':
      return contactDetails.email ?? null
    case 'call':
    case 'sms':
      return contactDetails.phoneNumber ?? null
    case 'linkedIn':
      return contactDetails.links.find((link) => link.source === LINKEDIN_PROFILE)?.address ?? null
  }
}

const dialled = (phone: string) => phone.replaceAll(' ', '')

const query = (fields: Record<string, string | null | undefined>) =>
  Object.entries(fields)
    .flatMap(([name, value]) => (value ? [`${name}=${encodeURIComponent(value)}`] : []))
    .join('&')

/**
 * The link that opens the channel's tool. It carries the message rendered for the recipient when the tool
 * accepts one.
 */
export function openingOf(
  channel: Channel,
  contactDetails: ContactDetails,
  message: Pick<CampaignMessage, 'text' | 'subject'>,
): Opening | null {
  const means = meansOf(channel, contactDetails)
  if (!means) return null
  switch (channel) {
    case 'email':
      return {
        href: `mailto:${means}?${query({ subject: message.subject, body: message.text })}`,
        external: false,
      }
    case 'sms':
      return { href: `sms:${dialled(means)}?${query({ body: message.text })}`, external: false }
    case 'call':
      return { href: `tel:${dialled(means)}`, external: false }
    case 'linkedIn':
      return { href: means, external: true }
  }
}
