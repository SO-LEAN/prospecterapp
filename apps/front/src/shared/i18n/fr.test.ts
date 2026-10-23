import { describe, expect, it } from 'vitest'
import { fr } from './fr'

const NO_BREAK_SPACE = ' '
const NARROW_NO_BREAK_SPACE = ' '

type Dictionary = { readonly [key: string]: string | Dictionary }

function labelsOf(dictionary: Dictionary, prefix = ''): [string, string][] {
  return Object.entries(dictionary).flatMap(([key, value]) =>
    typeof value === 'string' ? [[`${prefix}${key}`, value]] : labelsOf(value, `${prefix}${key}.`),
  )
}

function labelsMatching(pattern: RegExp): string[] {
  return labelsOf(fr)
    .filter(([, label]) => pattern.test(label))
    .map(([key]) => key)
}

describe('the French labels', () => {
  it('keep a colon on the line of the word before it, after a no-break space', () => {
    expect(labelsMatching(new RegExp(`[^\\S${NO_BREAK_SPACE}]:`))).toEqual([])
  })

  it('keep a semicolon, a question mark or an exclamation mark on the line of the word before it, after a narrow no-break space', () => {
    expect(labelsMatching(new RegExp(`[^\\S${NARROW_NO_BREAK_SPACE}][;?!]`))).toEqual([])
  })

  it('keep the text inside French quotation marks on their lines, after and before a no-break space', () => {
    expect(labelsMatching(new RegExp(`«(?!${NO_BREAK_SPACE})|(?<!${NO_BREAK_SPACE})»`))).toEqual([])
  })
})
