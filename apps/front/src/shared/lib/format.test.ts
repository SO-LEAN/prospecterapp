import { describe, expect, it } from 'vitest'
import { formatSize, telUrl } from './format'

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
