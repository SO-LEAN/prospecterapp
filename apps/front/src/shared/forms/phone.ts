import { AsYouType, parsePhoneNumberFromString } from 'libphonenumber-js'

/** The prefix a number typed without its country code is read with, as the API reads it. */
const DIALED_FROM = 'FR'

/** A phone as it is being typed, laid out as its country writes it: « 06 12 34 56 78 ». */
export const phoneAsTyped = (typed: string): string => new AsYouType(DIALED_FROM).input(typed)

/** A phone as the API keeps it, in the international format: « +33 6 12 34 56 78 »; one it cannot read, as typed. */
export const phoneAsRead = (typed: string): string =>
  parsePhoneNumberFromString(typed, DIALED_FROM)?.formatInternational() ?? typed
