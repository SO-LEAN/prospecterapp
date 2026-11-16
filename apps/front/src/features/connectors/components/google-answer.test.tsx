import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import { StrictMode } from 'react'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { type Connections, connectionsQuery } from '../queries'
import { type GoogleAnswer, GoogleAnswerPage } from './google-answer'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const connected: Connections = {
  connections: [
    {
      provider: 'google',
      state: 'connected',
      account: 'michel@exemple.fr',
      connectedAt: '2026-11-14T09:30:00+00:00',
      synced: 0,
      failed: 0,
      _links: {
        disconnect: { href: '/api/me/connections/google', method: 'DELETE' },
        reconnect: { href: '/api/me/connections/google/authorizations', method: 'POST' },
      },
    },
  ],
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
}

function renderTheAnswer(answer: GoogleAnswer) {
  const queryClient = new QueryClient()
  const onDone = vi.fn()
  render(
    <StrictMode>
      <QueryClientProvider client={queryClient}>
        <GoogleAnswerPage answer={answer} onDone={onDone} />
        <Toaster />
      </QueryClientProvider>
    </StrictMode>,
  )

  return { queryClient, onDone }
}

afterEach(() => {
  theApi.mockReset()
  toast.dismiss()
})

describe('GoogleAnswerPage', () => {
  it('gives the API the code Google brought back, once, and says my account is connected', async () => {
    theApi.mockResolvedValue(Response.json(connected, { status: 201 }))
    const { queryClient, onDone } = renderTheAnswer({ code: 'a-code', state: 'a-state' })

    expect(await screen.findByText('Votre compte Google est connecté.')).toBeInTheDocument()
    expect(theApi).toHaveBeenCalledTimes(1)
    const request = theApi.mock.calls[0]?.[0] as Request
    expect([new URL(request.url).pathname, request.method, await request.clone().json()]).toEqual([
      '/api/me/connections/google',
      'POST',
      { code: 'a-code', state: 'a-state' },
    ])
    expect(queryClient.getQueryData(connectionsQuery().queryKey)).toEqual(connected)
    await waitFor(() => expect(onDone).toHaveBeenCalled())
  })

  it('connects nothing when I refused the access at Google', async () => {
    const { onDone } = renderTheAnswer({ error: 'access_denied', state: 'a-state' })

    expect(
      await screen.findByText('La connexion à Google a été annulée : rien n’est connecté.'),
    ).toBeInTheDocument()
    expect(theApi).not.toHaveBeenCalled()
    expect(onDone).toHaveBeenCalled()
  })

  it('says why the API refused the answer', async () => {
    theApi.mockResolvedValue(
      Response.json(
        {
          type: 'about:blank',
          title: 'Not found',
          status: 404,
          reason: 'unknownAuthorization',
          detail: 'No authorization is waiting for this answer',
        },
        { status: 404, headers: { 'Content-Type': 'application/problem+json' } },
      ),
    )
    const { onDone } = renderTheAnswer({ code: 'a-code', state: 'an-expired-state' })

    expect(
      await screen.findByText('La demande de connexion a expiré : connectez votre compte à nouveau.'),
    ).toBeInTheDocument()
    await waitFor(() => expect(onDone).toHaveBeenCalled())
  })
})
