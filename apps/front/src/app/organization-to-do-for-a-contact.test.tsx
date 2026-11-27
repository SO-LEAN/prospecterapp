import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Organization, OrganizationContact } from '@/features/organizations/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** Noting a to-do from an organization's card, on the SPA's routes: for the organization, or for one of its contacts. */

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
    signOutEverywhere: async () => {},
  },
}))

const SAFRAN = '0190a1c0-0000-7000-8000-000000000001'
const NICOLAS = '0190a1c0-0000-7000-8000-000000000002'
const LOU = '0190a1c0-0000-7000-8000-000000000003'
const link = (href: string, method = 'GET') => ({ href, method })

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: link('/api/me') },
}

const safran: Organization = {
  id: SAFRAN,
  revision: 1,
  name: 'Safran SA',
  legalIdentity: { corporateName: 'Safran SA' },
  contactDetails: { links: [] },
  contactIds: [NICOLAS, LOU],
  possibleDuplicates: [],
  holdingId: null,
  createdByAnAI: false,
  createdAt: '2026-11-17T09:00:00+00:00',
  modifiedAt: '2026-11-17T09:00:00+00:00',
  _links: {
    self: link(`/api/organizations/${SAFRAN}`),
    exchanges: link(`/api/organizations/${SAFRAN}/exchanges`),
    toDos: link(`/api/organizations/${SAFRAN}/to-dos`),
  },
}

const aContact = (
  id: string,
  firstName: string,
  lastName: string,
  mayNoteAToDo: boolean,
): OrganizationContact => ({
  id,
  name: { firstName, lastName },
  title: 'x',
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  masked: false,
  _links: {
    contact: link(`/api/contacts/${id}`),
    ...(mayNoteAToDo && { noteToDo: link(`/api/contacts/${id}/to-dos`, 'POST') }),
  },
})

/** The API serves my account with nothing to process, Safran SA, its to-dos, none yet, and its contacts: Nicolas Bonnet accepts a to-do, Lou Durand does not. */
function theApiAnswers() {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/me/connections': { connections: [], _links: { self: link('/api/me/connections') } },
      '/api/me/reminders': { enabled: false, minutesBefore: 10, _links: { self: link('/api/me/reminders') } },
      '/api/me/day': { today: [], next: [], _links: { self: link('/api/me/day') } },
      '/api/me/exchanges-to-process': { items: [], toDos: [], contactsToAttach: [], _links: {} },
      [`/api/organizations/${SAFRAN}`]: safran,
      [`/api/organizations/${SAFRAN}/to-dos`]: {
        toDos: [],
        _links: {
          self: link(`/api/organizations/${SAFRAN}/to-dos`),
          noteToDo: link(`/api/organizations/${SAFRAN}/to-dos`, 'POST'),
        },
      },
      [`/api/organizations/${SAFRAN}/contacts`]: {
        items: [aContact(NICOLAS, 'Nicolas', 'Bonnet', true), aContact(LOU, 'Lou', 'Durand', false)],
        _links: {},
      },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
}

describe('a to-do noted from an organization’s card', () => {
  afterEach(() => theApi.mockReset())

  it('offers it for the organization and for the contacts who accept one only', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/organizations/${SAFRAN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter' }))

    const recipients = within(await screen.findByRole('dialog')).getByRole('group', { name: 'Pour' })
    expect(
      within(recipients)
        .getAllByRole('radio')
        .map((radio) => radio.closest('label')?.textContent),
    ).toEqual(['Safran SA', 'Nicolas Bonnet'])
  })
})
