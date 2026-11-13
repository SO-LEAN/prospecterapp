import { describe, expect, it } from 'vitest'
import { formatHour, formatSize, formatUpcoming, telUrl } from './format'

describe('formatUpcoming', () => {
  const now = new Date('2026-11-13T10:00')

  it('says the hour today and tomorrow, the days afterwards', () => {
    expect(formatUpcoming(new Date('2026-11-13T16:30').toISOString(), now)).toBe('aujourd’hui 16h30')
    expect(formatUpcoming(new Date('2026-11-14T14:00').toISOString(), now)).toBe('demain 14h00')
    expect(formatUpcoming(new Date('2026-11-17T09:00').toISOString(), now)).toBe('dans 4 jours')
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
