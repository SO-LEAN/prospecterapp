import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import { ContactsToAttachSection } from './components/contacts-to-attach-section'
import { ErasureReminders } from './components/erasure-reminders'
import { ToAttachBanner } from './components/to-attach-banner'
import { daysBeforeErasure } from './erasure'
import type { ContactToAttach } from './queries'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.hoisted(() => {
  window.matchMedia = (query: string) =>
    ({
      matches: false,
      media: query,
      addEventListener: () => undefined,
      removeEventListener: () => undefined,
    }) as unknown as MediaQueryList
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

/** Wednesday 18 November 2026, 10:00 in the time of the browser. */
const NOW = new Date(2026, 10, 18, 10, 0)
const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ACME = '0190a1c0-0000-7000-8000-00000000ac00'
const CREATED = '0190a1c0-0000-7000-8000-00000000c000'

/** Ada Lovelace, met for a video call, erased from the start of the given day if nobody attaches her. */
function ada(erasedOn: Date, email = 'ada@acme.fr', proposes = true): ContactToAttach {
  return {
    id: ADA,
    name: { firstName: 'Ada', lastName: 'Lovelace' },
    email,
    lastMeetingAt: new Date(2026, 10, 20, 14, 0).toISOString(),
    erasableFrom: erasedOn.toISOString(),
    _links: {
      self: { href: `/api/contacts/${ADA}`, method: 'GET' },
      ...(proposes && {
        proposedOrganization: { href: `/api/contacts/${ADA}/proposed-organization`, method: 'GET' },
      }),
    },
  }
}

const sent = () =>
  theApi.mock.calls
    .map(([request]) => request as Request)
    .filter((request) => request.method === 'POST')
    .map(async (request) => [new URL(request.url).pathname, await request.clone().json()] as const)

function theApiProposes(organization: { id: string; name: string } | null) {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    if (pathname.endsWith('/proposed-organization')) return Response.json({ organization })
    if (pathname === '/api/organizations' && request.method === 'GET')
      return Response.json({ items: [], _links: {} })
    if (pathname === '/api/organizations') return Response.json({ id: CREATED }, { status: 201 })
    return Response.json({ id: ACME }, { status: 200 })
  })
}

async function show(ui: React.ReactNode) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      {ui}
      <Toaster />
    </QueryClientProvider>,
  )
}

beforeEach(() => {
  vi.useFakeTimers({ now: NOW, shouldAdvanceTime: true })
  localStorage.clear()
})

afterEach(() => {
  toast.dismiss()
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the days before the erasure', () => {
  it('counts the days left until the start of the day of the erasure', () => {
    expect(daysBeforeErasure(new Date(2026, 10, 25).toISOString(), NOW)).toBe(7)
    expect(daysBeforeErasure(new Date(2026, 10, 19).toISOString(), NOW)).toBe(1)
    expect(daysBeforeErasure(new Date(2026, 11, 20).toISOString(), NOW)).toBe(32)
  })
})

describe('the contacts to attach to process', () => {
  it('shows each with their email and the days left before their erasure, orange from a week, red the day before', async () => {
    await show(<ContactsToAttachSection contacts={[ada(new Date(2026, 10, 25))]} />)

    const line = screen.getByRole('listitem')
    expect(within(line).getByRole('link', { name: 'Ada Lovelace' })).toHaveAttribute(
      'href',
      `/contacts/${ADA}`,
    )
    expect(within(line).getByText('ada@acme.fr')).toBeInTheDocument()
    expect(within(line).getByText('Effacé dans 7 jours')).toHaveAttribute('data-urgency', 'soon')
  })

  it('says the day before in red', async () => {
    await show(<ContactsToAttachSection contacts={[ada(new Date(2026, 10, 19))]} />)

    expect(screen.getByText('Effacé dans 1 jour')).toHaveAttribute('data-urgency', 'imminent')
  })

  it('attaches the contact to the organization proposed by the domain of their email', async () => {
    theApiProposes({ id: ACME, name: 'ACME' })
    await show(<ContactsToAttachSection contacts={[ada(new Date(2026, 11, 20))]} />)

    await userEvent.click(screen.getByRole('button', { name: 'Rattacher' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Rattacher à ACME' }))

    await waitFor(async () =>
      expect(await Promise.all(sent())).toEqual([[`/api/organizations/${ACME}/contacts`, { personId: ADA }]]),
    )
  })

  it('creates a new organization for their domain, its name deduced from it, then attaches them to it', async () => {
    theApiProposes(null)
    await show(<ContactsToAttachSection contacts={[ada(new Date(2026, 11, 20))]} />)

    await userEvent.click(screen.getByRole('button', { name: 'Rattacher' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Nouvelle organisation' }))
    expect(screen.getByRole('textbox', { name: 'Raison sociale' })).toHaveValue('Acme')
    expect(screen.getByRole('textbox', { name: 'E-mail' })).toHaveValue('contact@acme.fr')
    await userEvent.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await waitFor(async () =>
      expect(await Promise.all(sent())).toEqual([
        [
          '/api/organizations',
          {
            legalIdentity: { corporateName: 'Acme', form: null, type: null },
            contactDetails: { email: 'contact@acme.fr', phoneNumber: null, language: null },
            address: null,
            observations: null,
            holdingId: null,
          },
        ],
        [`/api/organizations/${CREATED}/contacts`, { personId: ADA }],
      ]),
    )
  })

  it('offers no attachment without the right to manage personal data', async () => {
    await show(<ContactsToAttachSection contacts={[ada(new Date(2026, 11, 20), '*****', false)]} />)

    expect(screen.queryByRole('button', { name: 'Rattacher' })).not.toBeInTheDocument()
  })
})

describe('the banner of the page of a contact to attach', () => {
  it('says the day they are erased without attachment', async () => {
    await show(
      <ToAttachBanner
        contactId={ADA}
        toAttach={{ lastMeetingAt: NOW.toISOString(), erasableFrom: new Date(2026, 11, 20).toISOString() }}
      />,
    )

    expect(screen.getByRole('status')).toHaveTextContent('Contact à rattacher, effacé le 20/12/2026')
  })
})

describe('the reminders before the erasure', () => {
  it('reminds a week before the erasure', async () => {
    await show(<ErasureReminders contacts={[ada(new Date(2026, 10, 25))]} />)

    expect(await screen.findByText('Ada Lovelace — Effacé dans 7 jours')).toBeInTheDocument()
  })

  it('reminds the day before', async () => {
    await show(<ErasureReminders contacts={[ada(new Date(2026, 10, 19))]} />)

    expect(await screen.findByText('Ada Lovelace — Effacé dans 1 jour')).toBeInTheDocument()
  })

  it('reminds nothing more than a week before', async () => {
    await show(<ErasureReminders contacts={[ada(new Date(2026, 10, 30))]} />)
    await act(async () => vi.advanceTimersByTimeAsync(100))

    expect(screen.queryByText(/Ada Lovelace — Effacé dans/)).not.toBeInTheDocument()
  })
})
