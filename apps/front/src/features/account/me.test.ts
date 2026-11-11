import { describe, expect, it } from 'vitest'
import { hasAProfileToComplete, isMyProfileBeingPrepared, nextReadingOfMyProfile } from './me'
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

describe('isMyProfileBeingPrepared', () => {
  const refused = (status: number, reason: string) => ({
    type: 'about:blank',
    title: 'Refused',
    status,
    reason,
  })

  it('takes the profile not found yet for a profile being prepared', () => {
    expect(isMyProfileBeingPrepared(refused(404, 'unknownPerson'))).toBe(true)
  })

  it('takes any other failure for an error', () => {
    expect(isMyProfileBeingPrepared(refused(404, 'unknownMember'))).toBe(false)
    expect(isMyProfileBeingPrepared(new TypeError('Failed to fetch'))).toBe(false)
  })
})

describe('nextReadingOfMyProfile', () => {
  const delaysAfterTheFirstReading = () => {
    const delays: number[] = []
    for (let readings = 1, delay = nextReadingOfMyProfile(readings); delay !== false; ) {
      delays.push(delay)
      readings += 1
      delay = nextReadingOfMyProfile(readings)
    }
    return delays
  }

  it('reads the profile again after a delay doubled each time, a second first', () => {
    expect(delaysAfterTheFirstReading().slice(0, 5)).toEqual([1_000, 2_000, 4_000, 8_000, 16_000])
  })

  it('stops reading once the member waited a minute', () => {
    const delays = delaysAfterTheFirstReading()

    expect(delays.reduce((waited, delay) => waited + delay, 0)).toBe(60_000)
    expect(delays.at(-1)).toBe(29_000)
  })
})
