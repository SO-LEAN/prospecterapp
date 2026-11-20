import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { contactQuery, organizationsOfContactQuery } from '@/features/contacts/queries'
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
  contactDetails: { links: [] },
  contactIds: [],
  possibleDuplicates: [],
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
  civility: 'x',
  jobTitle: null,
  contactDetails: { email: null, phoneNumber: null, language: null },
  masked: false,
  _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
}

const adaAttached = {
  ...ada,
  _links: {
    contact: { href: `/api/contacts/${ADA}`, method: 'GET' },
    removeFromOrganization: { href: `/api/organizations/${QA_CONTACTS}/contacts/${ADA}`, method: 'DELETE' },
  },
}

/** The reaction attaching the contact to the organization runs once the organization was read these many times. */
function givenTheApiCollectsAdaAndAttachesHerAfter(readings: number) {
  let contactsRead = 0
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const path = new URL(request.url).pathname
    if (request.method === 'POST' && path === '/api/contacts') {
      return Response.json(ada, { status: 201 })
    }
    if (path === `/api/organizations/${QA_CONTACTS}/contacts`) {
      contactsRead += 1
      return Response.json({ items: contactsRead > readings ? [adaAttached] : [] })
    }
    if (path === `/api/organizations/${QA_CONTACTS}`) {
      return Response.json(qaContacts)
    }
    return Response.json({ items: [] })
  })
}

async function collectAda(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('button', { name: /Modifier.*Contacts/ }))
  await user.click(screen.getByRole('button', { name: 'Nouveau contact' }))
  await user.type(screen.getByLabelText('Prénom'), 'Ada')
  await user.type(screen.getByLabelText('Nom'), 'Lovelace')
  await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))
}

async function renderTheContactsOf(organization: Organization) {
  const queryClient = new QueryClient()
  queryClient.setQueryData(organizationQuery(organization.id).queryKey, organization)
  queryClient.setQueryData(organizationContactsQuery(organization.id).queryKey, { items: [] })
  queryClient.setQueryData(contactQuery(ADA).queryKey, ada)
  queryClient.setQueryData(organizationsOfContactQuery(ADA).queryKey, { items: [] })
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ContactsSection organization={organization} />
    </QueryClientProvider>,
  )
  return { queryClient, user: userEvent.setup({ advanceTimers: vi.advanceTimersByTime }) }
}

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('ContactsSection', () => {
  it('shows the new contact as soon as it is saved, without waiting for the reaction attaching it', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    givenTheApiCollectsAdaAndAttachesHerAfter(Number.POSITIVE_INFINITY)
    const { user } = await renderTheContactsOf(qaContacts)

    await collectAda(user)
    await act(() => vi.advanceTimersByTimeAsync(3_000))

    expect(screen.getByRole('link', { name: 'Ada Lovelace' })).toBeInTheDocument()
    expect(screen.queryByText('Aucun contact rattaché.')).not.toBeInTheDocument()
  })

  it('offers what the attachment of the new contact allows once the reaction attached it, however late', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    givenTheApiCollectsAdaAndAttachesHerAfter(3)
    const { queryClient, user } = await renderTheContactsOf(qaContacts)

    await collectAda(user)
    expect(
      screen.queryByRole('button', { name: 'Retirer Ada Lovelace des contacts' }),
    ).not.toBeInTheDocument()
    await act(() => vi.advanceTimersByTimeAsync(20_000))

    expect(screen.getByRole('link', { name: 'Ada Lovelace' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Retirer Ada Lovelace des contacts' })).toBeInTheDocument()
    expect(queryClient.getQueryState(organizationQuery(QA_CONTACTS).queryKey)?.isInvalidated).toBe(true)
    expect(queryClient.getQueryState(contactQuery(ADA).queryKey)?.isInvalidated).toBe(true)
    expect(queryClient.getQueryState(organizationsOfContactQuery(ADA).queryKey)?.isInvalidated).toBe(true)
  })
})
