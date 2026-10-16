import { describe, expect, it } from 'vitest'
import { dataOf, nextSliceOf } from './slices'

describe('nextSliceOf', () => {
  it('reads the cursor of the next slice in the link next', () => {
    expect(
      nextSliceOf({ next: { href: '/api/organizations?q=acme&after=0190a1c0-0000-7000-8000-000000000001' } }),
    ).toBe('0190a1c0-0000-7000-8000-000000000001')
  })

  it('has no cursor after the last slice', () => {
    expect(nextSliceOf({})).toBeUndefined()
  })
})

describe('dataOf', () => {
  it('throws the problem of a failed response', () => {
    const problem = { status: 403, title: 'Forbidden' }

    expect(() => dataOf({ error: problem })).toThrow(expect.objectContaining(problem))
  })
})
