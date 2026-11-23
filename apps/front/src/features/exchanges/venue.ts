import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { CHANNELS, type Channel, type VenueCorrection } from './queries'
import { isAWebAddress } from './video-call-link'

const MAX_PLACE = 255
const MAX_LINK = 2048

/** A meeting's place and a video call's link, as the member types them in their fields. */
export const venueFields = { place: text(MAX_PLACE), link: text(MAX_LINK) }

/** A video call's link, when it has one, is a web address. The form does not send another channel's link. */
export const isTheLinkAWebAddress = (values: { channel?: Channel; link: string }) =>
  values.channel !== 'videoCall' || values.link === '' || isAWebAddress(values.link)

/** Where the form shows the refusal of a link that is not a web address, and the refusal's words. */
export const linkRefused = { path: ['link'], error: () => i18n.t('exchanges.linkIsAWebAddress') }

/** Mirrors the API's `VenueCorrectionInput`: a meeting's place, or a video call's link, which is a web address. */
export const venueSchema = z
  .object({ channel: z.enum(CHANNELS), ...venueFields })
  .refine(isTheLinkAWebAddress, linkRefused)

export type VenueValues = z.input<typeof venueSchema>

export const venueFieldNames = ['place', 'link'] as const

/** Only what the exchange's channel has: a meeting's place, or a video call's link. */
export const venueOf = (values: { channel: Channel; place: string; link: string }): VenueCorrection => ({
  place: values.channel === 'meeting' ? orNull(values.place.trim()) : null,
  link: values.channel === 'videoCall' ? orNull(values.link.trim()) : null,
})
