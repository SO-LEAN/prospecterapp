import { describe, expect, it } from 'vitest'
import { telUrl } from './format'

describe('telUrl', () => {
  it('dials the digits of a number written with spaces', () => {
    expect(telUrl('+33 3 88 11 22 33')).toBe('tel:+33388112233')
  })
})
