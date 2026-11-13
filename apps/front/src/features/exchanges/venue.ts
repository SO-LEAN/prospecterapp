import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { CHANNELS, type Channel, type VenueCorrection } from './queries'
import { isAWebAddress } from './video-call-link'

const MAX_PLACE = 255
const MAX_LINK = 2048

/** The place of a meeting and the link of a video call, as their fields are typed. */
export const venueFields = { place: text(MAX_PLACE), link: text(MAX_LINK) }

/** A video call is joined by a web address, when it has one; the link of another channel is not sent. */
export const isTheLinkAWebAddress = (values: { channel?: Channel; link: string }) =>
  values.channel !== 'videoCall' || values.link === '' || isAWebAddress(values.link)

/** Where the refusal of a link that is no web address is shown, and its words. */
export const linkRefused = { path: ['link'], error: () => i18n.t('exchanges.linkIsAWebAddress') }

/** Mirrors `VenueCorrectionInput` of the API: the place of a meeting, the link of a video call, a web address. */
export const venueSchema = z
  .object({ channel: z.enum(CHANNELS), ...venueFields })
  .refine(isTheLinkAWebAddress, linkRefused)

export type VenueValues = z.input<typeof venueSchema>

export const venueFieldNames = ['place', 'link'] as const

/** Only what its channel has: the place of a meeting, the link of a video call. */
export const venueOf = (values: { channel: Channel; place: string; link: string }): VenueCorrection => ({
  place: values.channel === 'meeting' ? orNull(values.place.trim()) : null,
  link: values.channel === 'videoCall' ? orNull(values.link.trim()) : null,
})
