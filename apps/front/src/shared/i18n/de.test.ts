import { describe, expect, it } from 'vitest'
import { de } from './de'
import { i18n } from './index'

const NARROW_NO_BREAK_SPACE = '\u202f'

describe('« de » before a name', () => {
  it.each([
    ['Anaïs Andre', 'd’Anaïs Andre'],
    ['Émilie Dubois', 'd’Émilie Dubois'],
    ['Inès Duval', 'd’Inès Duval'],
    ['alstom', 'd’alstom'],
    ['Œuvre Sociale', 'd’Œuvre Sociale'],
    ['Yves Robert', 'd’Yves Robert'],
    ['Ælia Roux', 'd’Ælia Roux'],
  ])('elides before a vowel: %s', (name, expected) => {
    expect(de(name)).toBe(expected)
  })

  it.each([
    ['Pauline Martin', 'de Pauline Martin'],
    ['Sylvie Martin', 'de Sylvie Martin'],
    ['Hager', 'de Hager'],
    ['Yannick Noah', 'de Yannick Noah'],
    ['3M', 'de 3M'],
    ['', 'de '],
  ])('stays whole before a consonant, an h, a semi-vowel y or anything else: %s', (name, expected) => {
    expect(de(name)).toBe(expected)
  })
})

describe('the labels putting « de » before a name', () => {
  it('elide it before a vowel', () => {
    expect(i18n.t('contacts.organizationsOf', { name: 'Anaïs Andre' })).toBe('Organisations d’Anaïs Andre')
    expect(i18n.t('contacts.forget.title', { name: 'Émilie Dubois' })).toBe(
      `Effacer la fiche d’Émilie Dubois${NARROW_NO_BREAK_SPACE}?`,
    )
    expect(i18n.t('dashboard.subtitle', { organization: 'Alstom' })).toBe(
      'Vue d’ensemble de la prospection d’Alstom.',
    )
  })

  it('keep it whole before a consonant', () => {
    expect(i18n.t('contacts.organizationsOf', { name: 'Pauline Martin' })).toBe(
      'Organisations de Pauline Martin',
    )
  })
})
