import { describe, expect, it } from 'vitest'
import { formatHour, formatSize, formatUpcoming, initials, telUrl } from './format'

describe('initials', () => {
  it('takes the first letter of the first two words', () => {
    expect(initials('Ada Lovelace')).toBe('AL')
  })

  it('keeps two letters at most', () => {
    expect(initials('Jean Paul Sartre')).toBe('JP')
  })

  it('leaves out a tag written in brackets', () => {
    expect(initials('[TEST] Paul Martin')).toBe('PM')
  })

  it('takes the first letter of a word, never a digit', () => {
    expect(initials('3M France')).toBe('MF')
  })

  it('takes an accented letter', () => {
    expect(initials('Élodie François')).toBe('ÉF')
  })

  it('shows a question mark for a name without a letter', () => {
    expect(initials('---')).toBe('?')
  })
})

describe('formatUpcoming', () => {
  const now = new Date('2026-11-13T10:00')

  it('says the day from now with the hour until the day after tomorrow', () => {
    expect(formatUpcoming(new Date('2026-11-13T16:30').toISOString(), now)).toBe('aujourd’hui 16h30')
    expect(formatUpcoming(new Date('2026-11-14T14:00').toISOString(), now)).toBe('demain 14h00')
    expect(formatUpcoming(new Date('2026-11-15T10:00').toISOString(), now)).toBe('après-demain 10h00')
  })

  it('says the date with the hour afterwards, read without counting the days', () => {
    expect(formatUpcoming(new Date('2026-11-16T09:00').toISOString(), now)).toBe('lun. 16 nov., 9h00')
    expect(formatUpcoming(new Date('2026-11-30T09:00').toISOString(), now)).toBe('lun. 30 nov., 9h00')
  })

  it('says the year of a date of another year, never of the day said from now', () => {
    expect(formatUpcoming(new Date('2026-11-26T09:00').toISOString(), new Date('2026-11-17T10:00'))).toBe(
      'jeu. 26 nov., 9h00',
    )
    expect(formatUpcoming(new Date('2027-01-07T09:00').toISOString(), new Date('2026-12-17T10:00'))).toBe(
      'jeu. 7 janv. 2027, 9h00',
    )
    expect(formatUpcoming(new Date('2027-01-01T10:00').toISOString(), new Date('2026-12-31T10:00'))).toBe(
      'demain 10h00',
    )
  })

  it('writes the hour the French way', () => {
    expect(formatHour(new Date('2026-11-17T09:05').toISOString())).toBe('9h05')
  })
})

describe('telUrl', () => {
  it('dials the digits of a number written with spaces', () => {
    expect(telUrl('+33 3 88 11 22 33')).toBe('tel:+33388112233')
  })
})

describe('formatSize', () => {
  it('reads a size in the largest unit it reaches', () => {
    expect(formatSize(512)).toBe('512 octets')
    expect(formatSize(2048)).toBe('2 Ko')
    expect(formatSize(1572864)).toBe('1,5 Mo')
  })

  it('reads an empty file in bytes', () => {
    expect(formatSize(0)).toBe('0 octets')
  })
})
