import { AsYouType, parsePhoneNumberFromString } from 'libphonenumber-js'

/** The prefix for a number typed without its country code, as the API reads it. */
const DIALED_FROM = 'FR'

/** A phone number while it is typed, laid out as its country writes it: « 06 12 34 56 78 ». */
export const phoneAsTyped = (typed: string): string => new AsYouType(DIALED_FROM).input(typed)

/** A phone number as the API keeps it, in the international format: « +33 6 12 34 56 78 ». A number it cannot read stays as typed. */
export const phoneAsRead = (typed: string): string =>
  parsePhoneNumberFromString(typed, DIALED_FROM)?.formatInternational() ?? typed
