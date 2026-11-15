import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { type GoogleClient, googleClientQuery } from '../queries'
import { GoogleClientCard } from './google-client-card'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const THE_CLIENT = '/api/me/organization/clients/google'
const REDIRECT_URI = 'https://local.prospecter.io/settings/connectors/google'

const configured = (clientId: string): GoogleClient => ({
  clientId,
  secretSet: true,
  redirectUri: REDIRECT_URI,
  _links: {
    self: { href: THE_CLIENT, method: 'GET' },
    configure: { href: THE_CLIENT, method: 'PATCH' },
    remove: { href: THE_CLIENT, method: 'DELETE' },
  },
})

const none: GoogleClient = {
  clientId: null,
  secretSet: false,
  redirectUri: REDIRECT_URI,
  _links: { self: { href: THE_CLIENT, method: 'GET' }, configure: { href: THE_CLIENT, method: 'PATCH' } },
}

function renderTheCard(client: GoogleClient) {
  const queryClient = new QueryClient()
  render(
    <QueryClientProvider client={queryClient}>
      <GoogleClientCard client={client} />
      <Toaster />
    </QueryClientProvider>,
  )

  return { user: userEvent.setup(), queryClient }
}

const sent = async () => {
  const request = theApi.mock.calls[0]?.[0] as Request

  return {
    path: new URL(request.url).pathname,
    method: request.method,
    body: request.method === 'PATCH' ? await request.clone().json() : undefined,
  }
}

afterEach(() => {
  theApi.mockReset()
  toast.dismiss()
})

describe('GoogleClientCard', () => {
  it('gives the redirect URI to declare in the client at Google', () => {
    renderTheCard(none)

    expect(screen.getByText('Adresse de retour à déclarer dans le client')).toBeInTheDocument()
    expect(screen.getByText(REDIRECT_URI)).toBeInTheDocument()
  })

  it('says the members have no Google Calendar while the account has no client', () => {
    renderTheCard(none)

    expect(
      screen.getByText(/^Aucun client.: Google Agenda est indisponible pour les membres du compte\.$/),
    ).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Retirer le client' })).not.toBeInTheDocument()
  })

  it('shows the id of the client and its secret only as set, never its value', () => {
    renderTheCard(configured('client-of-solean'))

    expect(screen.getByText('Client client-of-solean, secret renseigné.')).toBeInTheDocument()
    expect(screen.getByLabelText('Identifiant du client')).toHaveValue('client-of-solean')
    expect(screen.getByLabelText('Secret du client')).toHaveValue('')
  })

  it('saves the id and the secret of the client, and keeps the client the API answers', async () => {
    theApi.mockResolvedValue(Response.json(configured('another-client')))
    const { user, queryClient } = renderTheCard(none)

    await user.type(screen.getByLabelText('Identifiant du client'), 'another-client')
    await user.type(screen.getByLabelText('Secret du client'), 'its-secret')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le client' }))

    await waitFor(async () =>
      expect(await sent()).toEqual({
        path: THE_CLIENT,
        method: 'PATCH',
        body: { clientId: 'another-client', clientSecret: 'its-secret' },
      }),
    )
    await waitFor(() =>
      expect(queryClient.getQueryData(googleClientQuery().queryKey)).toEqual(configured('another-client')),
    )
    expect(await screen.findByText('Le client Google du compte est enregistré.')).toBeInTheDocument()
  })

  it('asks the id and the secret before sending anything', async () => {
    const { user } = renderTheCard(none)

    await user.click(screen.getByRole('button', { name: 'Enregistrer le client' }))

    expect(await screen.findAllByText('Ce champ est obligatoire.')).toHaveLength(2)
    expect(theApi).not.toHaveBeenCalled()
  })

  it('removes the client once confirmed', async () => {
    theApi.mockResolvedValue(Response.json(none))
    const { user, queryClient } = renderTheCard(configured('client-of-solean'))

    await user.click(screen.getByRole('button', { name: 'Retirer le client' }))
    expect(theApi).not.toHaveBeenCalled()
    await user.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Retirer' }))

    await waitFor(async () => expect(await sent()).toMatchObject({ path: THE_CLIENT, method: 'DELETE' }))
    await waitFor(() => expect(queryClient.getQueryData(googleClientQuery().queryKey)).toEqual(none))
    expect(await screen.findByText('Le client Google du compte est retiré.')).toBeInTheDocument()
  })
})
