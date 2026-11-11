import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { createMemoryHistory, createRouter, RouterProvider } from '@tanstack/react-router'
import { render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import { routeTree } from './routeTree.gen'

/**
 * The layout every page of a member signed in goes through (`routes/_app.tsx`), on the routes of the SPA: what it shows
 * while their profile is being prepared, once it is, and when their account cannot be read.
 */

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

const link = (href: string) => ({ href, method: 'PATCH' })

const myEmptyProfile: Account = {
  name: { firstName: null, lastName: null },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: {
    self: { href: '/api/me', method: 'GET' },
    changeMyName: link('/api/me/name'),
    changeMyContactDetails: link('/api/me/contact-details'),
  },
}

const json = (body: unknown, status: number) =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })

const refused = (status: number, reason: string) =>
  json({ type: 'about:blank', title: 'Refused', status, detail: `Refused: ${reason}`, reason }, status)

type Answer = () => Response

/** The api: my account answered in turn by `myAccount`, the last one kept; what I sign in with; nothing else. */
function theApiAnswers(...myAccount: Answer[]) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    if (pathname === '/api/me') {
      const answer = myAccount.length > 1 ? myAccount.shift() : myAccount[0]
      return answer ? answer() : refused(404, 'unknownMember')
    }
    if (pathname === '/api/me/sign-in') {
      return json({ loginEmail: 'ada@babbage.test' }, 200)
    }
    return refused(404, 'notFound')
  })
}

/** Signs in on the home page, rendered once the router loaded it: its routes, their code and their data. */
async function signIn() {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  const router = createRouter({ routeTree, context: { queryClient }, history: createMemoryHistory() })
  await router.load()
  render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )
}

const myProfileNotCreatedYet = () => refused(404, 'unknownPerson')
const myProfileCreated = () => json(myEmptyProfile, 200)

describe('the layout of a member signed in', () => {
  beforeEach(() => {
    theApi.mockReset()
  })

  it('tells a member whose profile is not created yet that it is being prepared, and lets them sign out', async () => {
    theApiAnswers(myProfileNotCreatedYet)
    await signIn()

    expect(await screen.findByRole('status')).toHaveTextContent('Votre profil est en cours de préparation…')
    expect(screen.getByRole('button', { name: 'Se déconnecter' })).toBeInTheDocument()
    expect(screen.queryByText('Une erreur est survenue')).not.toBeInTheDocument()
  })

  it('asks their name, without a reload, once their profile is created', async () => {
    theApiAnswers(myProfileNotCreatedYet, myProfileCreated)
    await signIn()
    await screen.findByRole('status')

    expect(
      await screen.findByRole('heading', { name: 'Complétez votre profil' }, { timeout: 3_000 }),
    ).toBeInTheDocument()
    expect(screen.getByLabelText('Prénom')).toBeInTheDocument()
  })

  it('shows any other failure to read the account as an error, offering to sign out', async () => {
    theApiAnswers(() => refused(404, 'unknownMember'))
    await signIn()

    expect(await screen.findByText('Une erreur est survenue')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Réessayer' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Se déconnecter' })).toBeInTheDocument()
  })
})
