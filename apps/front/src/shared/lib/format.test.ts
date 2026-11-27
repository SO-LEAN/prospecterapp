import { describe, expect, it } from 'vitest'
import {
  formatCalendarDay,
  formatClockTime,
  formatDayTitle,
  formatFullDay,
  formatHour,
  formatMoment,
  formatMonthTitle,
  formatRange,
  formatSize,
  formatTimeLeft,
  formatUpcoming,
  formatWeekday,
  formatWeekTitle,
  initials,
  telUrl,
} from './format'

/** Wednesday 25 November 2026, 10:00 in the browser's time zone. */
const NOW = new Date('2026-11-25T10:00')

describe('formatMoment', () => {
  it('writes the day with its weekday, a middle dot, then the hour', () => {
    expect(formatMoment(new Date('2026-11-17T11:00').toISOString(), NOW)).toBe('mar. 17 nov. · 11h00')
  })

  it('adds the year when it is not the current one', () => {
    expect(formatMoment(new Date('2027-01-04T09:00').toISOString(), NOW)).toBe('lun. 4 janv. 2027 · 9h00')
  })

  it('reads the moment on the clock of a given time zone', () => {
    expect(formatMoment('2026-11-24T00:05:00+01:00', NOW, 'America/New_York')).toBe('lun. 23 nov. · 18h05')
  })
})

describe('formatRange', () => {
  const at = (moment: string) => new Date(moment).toISOString()

  it('writes the day once when the range ends on the day it starts', () => {
    expect(formatRange(at('2026-11-20T16:15'), at('2026-11-20T16:45'), NOW)).toBe(
      'ven. 20 nov. · 16h15 à 16h45',
    )
  })

  it('writes both days when the range ends on another day', () => {
    expect(formatRange(at('2026-11-20T16:15'), at('2026-11-21T09:00'), NOW)).toBe(
      'ven. 20 nov. · 16h15 à sam. 21 nov. · 9h00',
    )
  })
})

describe('formatDayTitle', () => {
  it('writes the whole day, with a single capital', () => {
    expect(formatDayTitle(new Date('2026-11-17T00:00'))).toBe('Mardi 17 novembre 2026')
  })
})

describe('formatFullDay', () => {
  it('writes the whole day in a sentence or an accessible name', () => {
    expect(formatFullDay(new Date('2026-11-17T00:00'))).toBe('mardi 17 novembre 2026')
  })
})

describe('formatMonthTitle', () => {
  it('writes the month and its year, with a single capital', () => {
    expect(formatMonthTitle(new Date('2026-11-17T00:00'))).toBe('Novembre 2026')
  })
})

describe('formatWeekday', () => {
  it('writes the short weekday heading a column of days', () => {
    expect(formatWeekday(new Date('2026-11-17T00:00'))).toBe('mar.')
  })
})

describe('formatWeekTitle', () => {
  it('writes the first and the last day of the week, its month, its year and its number', () => {
    expect(formatWeekTitle(new Date('2026-11-16T00:00'), new Date('2026-11-22T00:00'))).toBe(
      '16 au 22 nov. 2026 · S47',
    )
  })

  it('writes the month of each day when the week spans two months', () => {
    expect(formatWeekTitle(new Date('2026-11-30T00:00'), new Date('2026-12-06T00:00'))).toBe(
      '30 nov. au 6 déc. 2026 · S49',
    )
  })

  it('writes the year of each day when the week spans two years', () => {
    expect(formatWeekTitle(new Date('2026-12-28T00:00'), new Date('2027-01-03T00:00'))).toBe(
      '28 déc. 2026 au 3 janv. 2027 · S53',
    )
  })
})

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
  const at = (moment: string) => new Date(moment).toISOString()

  it('adds the day said from now to the date, never in its place', () => {
    expect(formatUpcoming(at('2026-11-26T14:00'), NOW)).toBe('jeu. 26 nov. · 14h00 (demain)')
  })

  it('says the day from now until the day after tomorrow', () => {
    expect(formatUpcoming(at('2026-11-27T10:00'), NOW)).toBe('ven. 27 nov. · 10h00 (après-demain)')
  })

  it('says the date alone afterwards', () => {
    expect(formatUpcoming(at('2026-11-28T09:00'), NOW)).toBe('sam. 28 nov. · 9h00')
  })
})

describe('formatTimeLeft', () => {
  const at = (moment: string) => new Date(moment)

  // Intl separates a number from its unit by a no-break space
  it('tells how long is left before something starts, in minutes', () => {
    expect(formatTimeLeft('2026-11-13T10:25:00', at('2026-11-13T10:00:00'))).toBe('dans 25\u00a0min')
  })

  it('rounds a minute begun up', () => {
    expect(formatTimeLeft('2026-11-13T10:00:30', at('2026-11-13T10:00:00'))).toBe('dans 1\u00a0min')
  })

  it('tells it in hours from an hour on', () => {
    expect(formatTimeLeft('2026-11-13T11:00:00', at('2026-11-13T10:00:00'))).toBe('dans 1\u00a0h')
  })

  it('tells nothing is left once it started', () => {
    expect(formatTimeLeft('2026-11-13T10:00:00', at('2026-11-13T10:00:00'))).toBeNull()
  })
})

describe('formatHour', () => {
  it('writes the hour the French way', () => {
    expect(formatHour(new Date('2026-11-17T09:05').toISOString())).toBe('9h05')
  })
})

describe('formatCalendarDay', () => {
  it('writes a day kept as written with its weekday, whatever the time zone', () => {
    expect(formatCalendarDay('2026-11-24', NOW)).toBe('mar. 24 nov.')
  })

  it('adds an hour kept as written when there is one', () => {
    expect(formatCalendarDay('2026-11-24', NOW, '08:30')).toBe('mar. 24 nov. · 8h30')
  })
})

describe('formatClockTime', () => {
  it('writes an hour kept as written the French way', () => {
    expect(formatClockTime('08:30')).toBe('8h30')
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
