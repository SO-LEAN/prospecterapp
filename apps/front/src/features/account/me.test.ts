import { describe, expect, it } from 'vitest'
import { hasAProfileToComplete } from './me'
import type { Account } from './mutations'

const link = (href: string) => ({ href, method: 'PATCH' })

const anAccount = (account: Partial<Account>): Account => ({
  name: { firstName: null, lastName: null },
  contactDetails: { email: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: {
    self: { href: '/api/me', method: 'GET' },
    changeMyName: link('/api/me/name'),
    changeMyContactDetails: link('/api/me/contact-details'),
  },
  ...account,
})

describe('hasAProfileToComplete', () => {
  it('asks a member without a name to complete their profile', () => {
    expect(hasAProfileToComplete(anAccount({}))).toBe(true)
  })

  it('lets a member with a first name or a name in', () => {
    expect(hasAProfileToComplete(anAccount({ name: { firstName: 'Ada', lastName: null } }))).toBe(false)
    expect(hasAProfileToComplete(anAccount({ name: { firstName: null, lastName: 'Lovelace' } }))).toBe(false)
  })

  it('asks nothing a member may not give: their name or their contact details', () => {
    const self = { href: '/api/me', method: 'GET' }

    expect(hasAProfileToComplete(anAccount({ _links: { self, changeMyName: link('/api/me/name') } }))).toBe(
      false,
    )
    expect(
      hasAProfileToComplete(
        anAccount({ _links: { self, changeMyContactDetails: link('/api/me/contact-details') } }),
      ),
    ).toBe(false)
  })
})
