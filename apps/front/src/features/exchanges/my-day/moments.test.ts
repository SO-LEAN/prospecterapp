import { describe, expect, it } from 'vitest'
import { isInProgress, startsIn, whenToCome } from './moments'

const at = (moment: string) => new Date(moment)

describe('the moments of my day', () => {
  // Intl separates a number from its unit by a no-break space
  it('tells how long is left before an exchange starts, in minutes then in hours', () => {
    expect(startsIn('2026-11-13T10:25:00', at('2026-11-13T10:00:00'))).toBe('dans 25\u00a0min')
    expect(startsIn('2026-11-13T12:10:00', at('2026-11-13T10:00:00'))).toBe('dans 2\u00a0h')
    expect(startsIn('2026-11-13T10:00:30', at('2026-11-13T10:00:00'))).toBe('dans 1\u00a0min')
  })

  it('tells nothing is left once it started', () => {
    expect(startsIn('2026-11-13T09:00:00', at('2026-11-13T10:00:00'))).toBeNull()
  })

  it('knows the exchange in progress, from its start until the end of its duration', () => {
    const call = { occurredAt: '2026-11-13T10:00:00', durationInMinutes: 30 }

    expect(isInProgress(call, at('2026-11-13T09:59:00'))).toBe(false)
    expect(isInProgress(call, at('2026-11-13T10:00:00'))).toBe(true)
    expect(isInProgress(call, at('2026-11-13T10:29:00'))).toBe(true)
    expect(isInProgress(call, at('2026-11-13T10:30:00'))).toBe(false)
    expect(isInProgress({ ...call, durationInMinutes: null }, at('2026-11-13T10:10:00'))).toBe(false)
  })

  it('dates an exchange to come: tomorrow, a day of the week, then its date', () => {
    const friday = at('2026-11-13T10:00:00')

    expect(whenToCome('2026-11-14T09:00:00', friday)).toBe('demain 9h00')
    expect(whenToCome('2026-11-16T14:00:00', friday)).toBe('lundi 14h00')
    expect(whenToCome('2026-11-19T14:00:00', friday)).toBe('jeudi 14h00')
    expect(whenToCome('2026-11-20T14:00:00', friday)).toBe('ven. 20 nov. 14h00')
  })
})
