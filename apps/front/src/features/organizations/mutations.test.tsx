import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, render, renderHook, screen, waitFor } from '@testing-library/react'
import type { ReactNode } from 'react'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { AFTER_THE_REACTIONS_MS } from '@/shared/api/reactions'
import { theDashboardInTheCacheOf } from '@/test/the-dashboard'
import { useRelocate, useReviseLegalIdentity, useStartProspecting } from './mutations'
import type { Organization } from './queries'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-000000000002'

const acme: Organization = {
  id: ACME,
  revision: 1,
  name: 'ACME',
  legalIdentity: { corporateName: 'ACME' },
  contactDetails: { links: [] },
  contactIds: [],
  possibleDuplicates: [],
  createdByAnAI: false,
  createdAt: '2026-10-26T09:00:00+00:00',
  modifiedAt: '2026-10-26T09:00:00+00:00',
  _links: { self: { href: `/api/organizations/${ACME}`, method: 'GET' } },
}

const legalIdentity = { corporateName: 'ACME', form: null, type: null, siret: null }

function withACache({ children }: { children: ReactNode }) {
  return <QueryClientProvider client={new QueryClient()}>{children}</QueryClientProvider>
}

function withTheCache(queryClient: QueryClient) {
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  )
}

function givenTheApiAnswers(organization: Organization) {
  theApi.mockResolvedValue(Response.json(organization))
  render(<Toaster />)
}

/** Sonner removes a toast closed after a timer of its own: once the test is over, it would find no window. */
async function waitForTheToastsToBeRemoved() {
  act(() => {
    toast.dismiss()
  })
  await waitFor(() => expect(document.querySelector('[data-sonner-toast]')).toBeNull())
}

afterEach(async () => {
  vi.useRealTimers()
  await waitForTheToastsToBeRemoved()
  theApi.mockReset()
})

describe('the intentions on an organization', () => {
  it('confirm that the prospecting of a new organization starts', async () => {
    givenTheApiAnswers(acme)
    const { result } = renderHook(useStartProspecting, { wrapper: withACache })

    await act(() =>
      result.current.mutateAsync({
        body: { legalIdentity, address: null, observations: null, holdingId: null },
      }),
    )

    expect(await screen.findByText('Vous prospectez ACME')).toBeInTheDocument()
    expect(screen.queryByText('Modification enregistrée')).not.toBeInTheDocument()
  })

  it('confirm that a section of the organization is saved', async () => {
    givenTheApiAnswers(acme)
    const { result } = renderHook(useReviseLegalIdentity, { wrapper: withACache })

    await act(() => result.current.mutateAsync({ params: { path: { id: ACME } }, body: legalIdentity }))

    expect(await screen.findByText('Modification enregistrée')).toBeInTheDocument()
  })

  it('have the dashboard read again once the reactions to the gesture counted the new organization', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    givenTheApiAnswers(acme)
    const queryClient = new QueryClient()
    const dashboard = theDashboardInTheCacheOf(queryClient)
    const { result } = renderHook(useStartProspecting, { wrapper: withTheCache(queryClient) })

    await act(() =>
      result.current.mutateAsync({
        body: { legalIdentity, address: null, observations: null, holdingId: null },
      }),
    )

    expect(await dashboard.isReadAgainAfterTheReactions()).toBe(true)
  })

  it('have the tags of a new organization read again once the reactions recorded where it is', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    givenTheApiAnswers(acme)
    const queryClient = new QueryClient()
    const organizationTags = ['get', '/api/organizations/{id}/tags', { params: { path: { id: ACME } } }]
    const { result } = renderHook(useStartProspecting, { wrapper: withTheCache(queryClient) })

    await act(() =>
      result.current.mutateAsync({
        body: { legalIdentity, address: null, observations: null, holdingId: null },
      }),
    )
    queryClient.setQueryData(organizationTags, { tags: [], automatic: [] })
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)

    expect(queryClient.getQueryState(organizationTags)?.isInvalidated).toBe(true)
  })

  it('have the tags of the organization and of the contacts read again once the reactions recorded where it moved', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    givenTheApiAnswers(acme)
    const queryClient = new QueryClient()
    const organizationTags = ['get', '/api/organizations/{id}/tags', { params: { path: { id: ACME } } }]
    const contactTags = ['get', '/api/contacts/{id}/tags', { params: { path: { id: ACME } } }]
    const { result } = renderHook(useRelocate, { wrapper: withTheCache(queryClient) })

    await act(() =>
      result.current.mutateAsync({
        params: { path: { id: ACME } },
        body: { street: null, postalCode: null, city: 'Illkirch-Graffenstaden', country: null },
      }),
    )
    queryClient.setQueryData(organizationTags, { tags: [], automatic: [] })
    queryClient.setQueryData(contactTags, { tags: [], inherited: [], automatic: [] })
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)

    expect(queryClient.getQueryState(organizationTags)?.isInvalidated).toBe(true)
    expect(queryClient.getQueryState(contactTags)?.isInvalidated).toBe(true)
  })
})
