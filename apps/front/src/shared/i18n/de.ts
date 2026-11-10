const VOWEL = /^[aeiouæœ]/
const Y_BEFORE_A_CONSONANT = /^y[^aeiou]/
const DIACRITICS = /\p{Diacritic}/gu

function baseLettersOf(name: string): string {
  return name.normalize('NFD').replace(DIACRITICS, '').toLowerCase()
}

/** Elides before a vowel; never before an h, a mute h cannot be told from an aspirated one. */
function startsWithAVowelSound(name: string): boolean {
  const letters = baseLettersOf(name)
  return VOWEL.test(letters) || Y_BEFORE_A_CONSONANT.test(letters)
}

/** The French preposition « de » before a name: « d’Anaïs », « de Pauline ». */
export function de(name: string): string {
  return startsWithAVowelSound(name) ? `d’${name}` : `de ${name}`
}
