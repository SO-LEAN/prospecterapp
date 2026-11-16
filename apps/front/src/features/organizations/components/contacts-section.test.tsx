import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { type Organization, organizationContactsQuery, organizationQuery } from '../queries'
import { ContactsSection } from './contacts-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const QA_CONTACTS = '0190a1c0-0000-7000-8000-000000000001'
const ADA = '0190a1c0-0000-7000-8000-000000000002'

const qaContacts: Organization = {
  id: QA_CONTACTS,
  name: 'QA contacts',
  legalIdentity: { corporateName: 'QA contacts' },
  contactDetails: {},
  contactIds: [],
  createdAt: '2026-11-16T09:00:00+00:00',
  modifiedAt: '2026-11-16T09:00:00+00:00',
  _links: {
    self: { href: `/api/organizations/${QA_CONTACTS}`, method: 'GET' },
    addContact: { href: `/api/organizations/${QA_CONTACTS}/contacts`, method: 'POST' },
  },
}

const ada = {
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  jobTitle: null,
  contactDetails: { email: null, phoneNumber: null, language: null },
  masked: false,
  _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
}

/** The reaction attaching the contact to the organization has not run yet: the organization still has none. */
function givenTheApiCollectsAdaBeforeTheReactionAttachesHer() {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    if (request.method === 'POST' && new URL(request.url).pathname === '/api/contacts') {
      return Response.json(ada, { status: 201 })
    }
    if (new URL(request.url).pathname === `/api/organizations/${QA_CONTACTS}`) {
      return Response.json(qaContacts)
    }
    return Response.json({ items: [] })
  })
}

async function renderTheContactsOf(organization: Organization) {
  const queryClient = new QueryClient()
  queryClient.setQueryData(organizationQuery(organization.id).queryKey, organization)
  queryClient.setQueryData(organizationContactsQuery(organization.id).queryKey, { items: [] })
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ContactsSection organization={organization} />
    </QueryClientProvider>,
  )
  return userEvent.setup({ advanceTimers: vi.advanceTimersByTime })
}

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('ContactsSection', () => {
  it('shows the new contact as soon as it is saved, without waiting for the reaction attaching it', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    givenTheApiCollectsAdaBeforeTheReactionAttachesHer()
    const user = await renderTheContactsOf(qaContacts)

    await user.click(screen.getByRole('button', { name: /Modifier.*Contacts/ }))
    await user.click(screen.getByRole('button', { name: 'Nouveau contact' }))
    await user.type(screen.getByLabelText('Prénom'), 'Ada')
    await user.type(screen.getByLabelText('Nom'), 'Lovelace')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))
    await act(() => vi.advanceTimersByTimeAsync(3_000))

    expect(screen.getByRole('link', { name: 'Ada Lovelace' })).toBeInTheDocument()
    expect(screen.queryByText('Aucun contact rattaché.')).not.toBeInTheDocument()
  })
})
