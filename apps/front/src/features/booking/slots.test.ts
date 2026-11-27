import { describe, expect, it } from 'vitest'
import { cityOf } from './slots'

describe('the words of the slots', () => {
  it('names a time zone after its city, its words spaced', () => {
    expect(cityOf('America/New_York')).toBe('New York')
  })
})
