/**
 * A tag as typed, compared to a tag proposed: without its mark, its accents, its case, nor its separators, the way
 * the API writes a tag (ADR 0138), so that « édi » finds #EDITEUR and « esn » #ESN.
 */
export const comparable = (text: string) =>
  text
    .normalize('NFD')
    .replace(/\p{M}+/gu, '')
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '')

export const matches = (tag: string, typed: string) => comparable(tag).includes(comparable(typed))

export const isTheTag = (tag: string, typed: string) => comparable(tag) === comparable(typed)
