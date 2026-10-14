import { describe, expect, it, vi } from 'vitest'
import { applyViolations, isProblem, type Problem } from './problem'

const problem = (violations: Problem['violations']): Problem => ({
  type: 'about:blank',
  title: 'Validation Failed',
  status: 422,
  detail: 'Invalid',
  violations,
})

describe('applyViolations', () => {
  it('puts field violations on the fields and returns the others', () => {
    const setError = vi.fn()

    const global = applyViolations(
      problem([
        { propertyPath: 'email', title: 'Invalid email' },
        { propertyPath: '', title: 'Name or email required' },
      ]),
      setError,
      ['email', 'city'],
    )

    expect(setError).toHaveBeenCalledWith('email', { type: 'server', message: 'Invalid email' })
    expect(global).toEqual(['Name or email required'])
  })

  it('falls back to the detail when there is no violation', () => {
    expect(applyViolations(problem([]), vi.fn(), [])).toEqual(['Invalid'])
  })

  it('ignores errors that are not problem details', () => {
    expect(isProblem(new TypeError('Failed to fetch'))).toBe(false)
    expect(applyViolations(new TypeError('Failed to fetch'), vi.fn(), [])).toEqual([])
  })
})
