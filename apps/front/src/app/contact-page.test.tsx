import { screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Contact } from '@/features/contacts/queries'
import type { Exchange } from '@/features/exchanges/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The header of the card of a contact, on the routes of the SPA: what its reader may see of it, what comes next. */

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: {
    accessToken: async () => 'an access token',
    renewedAccessToken: async () => null,
    signOut: async () => {},
  },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const NOW = new Date('2026-11-16T15:00:00')
const TOMORROW_AT_9 = new Date('2026-11-17T09:00:00').toISOString()

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const theContact = (contact: Partial<Contact>): Contact => ({
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  contactDetails: { email: 'ada@acme.test', phoneNumber: null, language: null },
  masked: false,
  _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
  ...contact,
})

const aVideoCallTomorrowAt9: Exchange = {
  id: '0190a1c0-0000-7000-8000-000000000020',
  channel: 'videoCall',
  direction: 'outbound',
  canceller: null,
  occurredAt: TOMORROW_AT_9,
  status: 'planned',
  durationInMinutes: 60,
  text: null,
  textFlagged: false,
  textToMakeConform: false,
  toMakeConform: false,
  organizationIds: [],
  attachmentCount: 0,
  _links: { self: { href: '/api/exchanges/0190a1c0-0000-7000-8000-000000000020', method: 'GET' } },
}

const withItsExchanges = { exchanges: { href: `/api/contacts/${ADA}/exchanges`, method: 'GET' } }

const ADA_ON_LINKEDIN = 'https://www.linkedin.com/in/ada-lovelace'
/** The api: my account, the contact, what is to come with them; nothing in their other lists. */
function theApiAnswers(contact: Contact, upcoming: Exchange[]) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    const answers: Record<string, unknown> = {
      '/api/me': me,
      [`/api/contacts/${ADA}`]: contact,
      [`/api/contacts/${ADA}/upcoming-exchanges`]: { items: upcoming, _links: {} },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
}

describe('the card of a contact', () => {
  afterEach(() => {
    vi.useRealTimers()
    theApi.mockReset()
  })

  it('says in its header the next exchange planned with the contact', async () => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(NOW)
    theApiAnswers(theContact({ _links: { ...theContact({})._links, ...withItsExchanges } }), [
      aVideoCallTomorrowAt9,
    ])

    await renderTheSpaAt(`/contacts/${ADA}`)

    expect(await screen.findByText('Prochain échange : Visio demain 9h00')).toBeInTheDocument()
  })

  it('says its data are masked to a reader without the right to manage personal data', async () => {
    theApiAnswers(theContact({ masked: true }), [])

    await renderTheSpaAt(`/contacts/${ADA}`)

    expect(await screen.findByRole('heading', { name: 'Ada Lovelace' })).toBeInTheDocument()
    expect(screen.getByText('Données masquées')).toBeInTheDocument()
  })

  it('opens the LinkedIn profile of the contact in a new tab', async () => {
    theApiAnswers(theContact({ contactDetails: { linkedInProfile: ADA_ON_LINKEDIN } }), [])

    await renderTheSpaAt(`/contacts/${ADA}`)

    const profile = await screen.findByRole('link', { name: 'linkedin.com/in/ada-lovelace' })
    expect(profile).toHaveAttribute('href', ADA_ON_LINKEDIN)
    expect(profile).toHaveAttribute('target', '_blank')
    expect(profile).toHaveAttribute('rel', 'noopener noreferrer')
  })

  it('shows the LinkedIn profile masked, no link to it, to a reader without the right', async () => {
    theApiAnswers(theContact({ masked: true, contactDetails: { linkedInProfile: '***' } }), [])

    await renderTheSpaAt(`/contacts/${ADA}`)

    expect(await screen.findByText('***')).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /linkedin/i })).not.toBeInTheDocument()
  })
})
