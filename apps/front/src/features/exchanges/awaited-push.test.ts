import { describe, expect, it } from 'vitest'
import { pauseBeforeReadingAgain } from './awaited-push'

describe('pauseBeforeReadingAgain', () => {
  it('reads an exchange awaiting its push again, slower and slower', () => {
    expect([1, 2, 3, 4, 5].map((readings) => pauseBeforeReadingAgain(true, readings))).toEqual([
      1_000, 2_000, 4_000, 8_000, 15_000,
    ])
  })

  it('stops after the last reading, still awaited', () => {
    expect(pauseBeforeReadingAgain(true, 6)).toBe(false)
  })

  it('never reads again an exchange awaiting nothing', () => {
    expect(pauseBeforeReadingAgain(false, 1)).toBe(false)
  })
})
