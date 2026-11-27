import { describe, expect, it } from 'vitest'
import { isInProgress } from './moments'

const at = (moment: string) => new Date(moment)

describe('the moments of my day', () => {
  it('knows the exchange in progress, from its start until the end of its duration', () => {
    const call = { occurredAt: '2026-11-13T10:00:00', durationInMinutes: 30 }

    expect(isInProgress(call, at('2026-11-13T09:59:00'))).toBe(false)
    expect(isInProgress(call, at('2026-11-13T10:00:00'))).toBe(true)
    expect(isInProgress(call, at('2026-11-13T10:29:00'))).toBe(true)
    expect(isInProgress(call, at('2026-11-13T10:30:00'))).toBe(false)
    expect(isInProgress({ ...call, durationInMinutes: null }, at('2026-11-13T10:10:00'))).toBe(false)
  })
})
