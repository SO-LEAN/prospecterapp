import { describe, expect, it } from 'vitest'
import { organizationSchema, toFormValues, toInput } from './schema'

describe('organization schema', () => {
  it('requires a corporate name or an email', () => {
    const result = organizationSchema.safeParse(toFormValues())

    expect(result.success).toBe(false)
    expect(result.error?.issues[0]?.path).toEqual(['corporateName'])
  })

  it('accepts an organization with only an email', () => {
    expect(organizationSchema.safeParse({ ...toFormValues(), email: 'contact@acme.test' }).success).toBe(true)
  })

  it('sends empty fields as null', () => {
    const values = toFormValues()
    const input = toInput({ ...values, corporateName: 'ACME', address: { ...values.address, country: 'FR' } })

    expect(input).toMatchObject({
      corporateName: 'ACME',
      email: null,
      address: { street: null, postalCode: null, city: null, country: 'FR' },
      holdBy: null,
    })
  })
})
