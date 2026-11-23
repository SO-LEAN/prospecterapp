/**
 * Compares a typed tag to a proposed tag, ignoring the `#`, accents, case and separators, as the API writes a
 * tag (ADR 0138). So « édi » finds #EDITEUR, and « esn » finds #ESN.
 */
export const comparable = (text: string) =>
  text
    .normalize('NFD')
    .replace(/\p{M}+/gu, '')
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '')

export const matches = (tag: string, typed: string) => comparable(tag).includes(comparable(typed))

export const isTheTag = (tag: string, typed: string) => comparable(tag) === comparable(typed)
