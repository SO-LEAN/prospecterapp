import { describe, expect, it, vi } from 'vitest'
import { applyViolations, isProblem, messageOf, type Problem } from './problem'

const problem = (violations: Problem['violations'], reason: string | null = null): Problem => ({
  type: 'about:blank',
  title: 'Validation Failed',
  status: 422,
  detail: 'Invalid',
  violations,
  reason,
})

const violation = (propertyPath: string, title: string, reason: string | null = null, context = {}) => ({
  propertyPath,
  title,
  reason,
  context,
})

describe('applyViolations', () => {
  it('puts field violations on the fields and returns the others', () => {
    const setError = vi.fn()

    const global = applyViolations(
      problem([violation('email', 'Invalid email'), violation('', 'Name or email required')]),
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

describe('the reason of a refusal', () => {
  it('words a known refusal in French, with what it is about', () => {
    const setError = vi.fn()

    applyViolations(
      problem([
        violation('phoneNumber', 'Phone number "12" is not valid', 'invalidPhoneNumber', {
          phoneNumber: '12',
        }),
      ]),
      setError,
      ['phoneNumber'],
    )

    expect(setError).toHaveBeenCalledWith('phoneNumber', {
      type: 'server',
      message: 'Le numéro « 12 » n’est pas un numéro de téléphone valide.',
    })
  })

  it('keeps the words of the API for an unknown refusal', () => {
    expect(messageOf(problem([violation('', 'Something new', 'somethingNew')], 'somethingNew'))).toBe(
      'Invalid',
    )
    expect(applyViolations(problem([violation('', 'Something new', 'somethingNew')]), vi.fn(), [])).toEqual([
      'Something new',
    ])
  })

  it('words a problem without violation by its reason', () => {
    expect(messageOf({ ...problem([], 'unknownOrganization'), status: 404 })).toBe(
      'Organisation introuvable.',
    )
  })
})
