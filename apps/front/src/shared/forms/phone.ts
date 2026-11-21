import { AsYouType } from 'libphonenumber-js'

/** The prefix a number typed without its country code is read with, as the API reads it. */
const DIALED_FROM = 'FR'

/** A phone as it is being typed, laid out as its country writes it: « 06 12 34 56 78 ». */
export const phoneAsTyped = (typed: string): string => new AsYouType(DIALED_FROM).input(typed)
