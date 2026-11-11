import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, render, screen } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '../mutations'
import { accountQuery } from '../queries'
import { MyProfileBeingPrepared } from './my-profile-being-prepared'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const myAccount: Account = {
  name: { firstName: null, lastName: null },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const json = (body: unknown, status: number) =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })

const myProfileNotCreatedYet = () =>
  json(
    {
      type: 'about:blank',
      title: 'Not Found',
      status: 404,
      detail: 'Person not found',
      reason: 'unknownPerson',
    },
    404,
  )

const myProfileCreated = () => json(myAccount, 200)

/** The member signed in, the first reading of their account refused, as the guard of the app made it. */
async function renderWhileMyProfileIsPrepared() {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  theApi.mockImplementationOnce(async () => myProfileNotCreatedYet())
  await queryClient.prefetchQuery(accountQuery())
  render(
    <QueryClientProvider client={queryClient}>
      <MyProfileBeingPrepared>
        <button type="button">Se déconnecter</button>
      </MyProfileBeingPrepared>
    </QueryClientProvider>,
  )

  return queryClient
}

const waitFor = (ms: number) => act(() => vi.advanceTimersByTimeAsync(ms))

const readings = () => theApi.mock.calls.length

describe('MyProfileBeingPrepared', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout', 'setInterval', 'clearInterval', 'Date'] })
    theApi.mockReset()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('tells the member their profile is being prepared, and lets them sign out', async () => {
    theApi.mockImplementation(async () => myProfileNotCreatedYet())
    await renderWhileMyProfileIsPrepared()

    expect(screen.getByRole('status')).toHaveTextContent('Votre profil est en cours de préparation…')
    expect(screen.getByRole('button', { name: 'Se déconnecter' })).toBeInTheDocument()
  })

  it('reads the profile again, a second later, then two, until it is created', async () => {
    theApi.mockImplementationOnce(async () => myProfileNotCreatedYet())
    theApi.mockImplementation(async () => myProfileCreated())
    const queryClient = await renderWhileMyProfileIsPrepared()

    await waitFor(1_000)
    expect(readings()).toBe(2)
    expect(queryClient.getQueryData(accountQuery().queryKey)).toBeUndefined()

    await waitFor(2_000)
    expect(readings()).toBe(3)
    expect(queryClient.getQueryData(accountQuery().queryKey)).toEqual(myAccount)
  })

  it('asks to try again later once the member waited a minute, and reads the profile no more', async () => {
    theApi.mockImplementation(async () => myProfileNotCreatedYet())
    await renderWhileMyProfileIsPrepared()

    await waitFor(59_000)
    expect(screen.getByRole('status')).toHaveTextContent('Votre profil est en cours de préparation…')

    await waitFor(1_000)
    await vi.waitFor(() =>
      expect(screen.getByRole('heading', { name: 'Votre profil n’est pas encore prêt' })).toBeInTheDocument(),
    )
    expect(screen.getByText('Réessayez plus tard.')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Se déconnecter' })).toBeInTheDocument()

    const readingsWhenGivenUp = readings()
    await waitFor(60_000)
    expect(readings()).toBe(readingsWhenGivenUp)
  })
})
