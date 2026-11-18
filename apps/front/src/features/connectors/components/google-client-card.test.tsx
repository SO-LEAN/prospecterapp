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
const REDIRECT_URI = 'https://prospecter.localhost/settings/connectors/google'
const NOTIFICATION_ENDPOINT = 'https://api.prospecter.localhost/api/accounts/an-account/gmail-notifications'

const configured = (clientId: string): GoogleClient => ({
  clientId,
  secretSet: true,
  redirectUri: REDIRECT_URI,
  gmailNotificationEndpoint: NOTIFICATION_ENDPOINT,
  _links: {
    self: { href: THE_CLIENT, method: 'GET' },
    configure: { href: THE_CLIENT, method: 'PATCH' },
    remove: { href: THE_CLIENT, method: 'DELETE' },
  },
})

const GMAIL_NOTIFICATIONS = '/api/me/organization/clients/google/gmail-notifications'
const TOPIC = 'projects/solean/topics/gmail'
const PUSH_ACCOUNT = 'push@solean.iam.gserviceaccount.com'

const notifyingGmail = (): GoogleClient => {
  const client = configured('client-of-solean')

  return {
    ...client,
    gmailNotifications: { topic: TOPIC, pushAccount: PUSH_ACCOUNT },
    _links: {
      ...client._links,
      configureGmailNotifications: { href: GMAIL_NOTIFICATIONS, method: 'PUT' },
      removeGmailNotifications: { href: GMAIL_NOTIFICATIONS, method: 'DELETE' },
    },
  }
}

const withoutTopic = (): GoogleClient => {
  const client = configured('client-of-solean')

  return {
    ...client,
    _links: { ...client._links, configureGmailNotifications: { href: GMAIL_NOTIFICATIONS, method: 'PUT' } },
  }
}

const none: GoogleClient = {
  clientId: null,
  secretSet: false,
  redirectUri: REDIRECT_URI,
  gmailNotificationEndpoint: NOTIFICATION_ENDPOINT,
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
    body: ['PATCH', 'PUT'].includes(request.method) ? await request.clone().json() : undefined,
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

  it('says the client could not be saved while the secrets of the account are out of reach', async () => {
    theApi.mockResolvedValue(
      Response.json(
        {
          type: 'about:blank',
          title: 'Service Unavailable',
          status: 503,
          reason: 'secretsUnavailable',
          detail: 'The secrets of the accounts cannot be kept nor read for now',
        },
        { status: 503, headers: { 'Content-Type': 'application/problem+json' } },
      ),
    )
    const { user } = renderTheCard(none)

    await user.type(screen.getByLabelText('Identifiant du client'), 'another-client')
    await user.type(screen.getByLabelText('Secret du client'), 'its-secret')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le client' }))

    expect(
      await screen.findByText(
        /^Le secret du client ne peut être ni enregistré ni lu pour le moment.: réessayez plus tard\.$/,
      ),
    ).toBeInTheDocument()
    expect(screen.queryByText('Le client Google du compte est enregistré.')).not.toBeInTheDocument()
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

describe('GoogleClientCard, the topic of Gmail', () => {
  it('is not offered while the account has no client', () => {
    renderTheCard(none)

    expect(screen.queryByLabelText('Topic Pub/Sub (import Gmail)')).not.toBeInTheDocument()
  })

  it('gives the address of the notifications to declare in the subscription, and the procedure', () => {
    renderTheCard(withoutTopic())

    expect(screen.getByText('Adresse des notifications à déclarer dans l’abonnement')).toBeInTheDocument()
    expect(screen.getByText(NOTIFICATION_ENDPOINT)).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'La procédure dans Google Cloud' })).toHaveAttribute(
      'href',
      'https://github.com/SO-LEAN/prospecterapp/blob/main/docs/google-client.md#2-the-import-of-gmail',
    )
  })

  it('names the topic and the account of service pushing its notifications', async () => {
    theApi.mockResolvedValue(Response.json(notifyingGmail()))
    const { user, queryClient } = renderTheCard(withoutTopic())

    await user.type(screen.getByLabelText('Topic Pub/Sub (import Gmail)'), TOPIC)
    await user.type(screen.getByLabelText('Compte de service des notifications'), PUSH_ACCOUNT)
    await user.click(screen.getByRole('button', { name: 'Enregistrer le topic' }))

    await waitFor(async () =>
      expect(await sent()).toEqual({
        path: GMAIL_NOTIFICATIONS,
        method: 'PUT',
        body: { topic: TOPIC, pushAccount: PUSH_ACCOUNT },
      }),
    )
    await waitFor(() =>
      expect(queryClient.getQueryData(googleClientQuery().queryKey)).toEqual(notifyingGmail()),
    )
    expect(await screen.findByText('Le topic de l’import Gmail est enregistré.')).toBeInTheDocument()
  })

  it('shows the topic named, and removes it', async () => {
    theApi.mockResolvedValue(Response.json(withoutTopic()))
    const { user } = renderTheCard(notifyingGmail())

    expect(screen.getByLabelText('Topic Pub/Sub (import Gmail)')).toHaveValue(TOPIC)
    expect(screen.getByLabelText('Compte de service des notifications')).toHaveValue(PUSH_ACCOUNT)
    await user.click(screen.getByRole('button', { name: 'Retirer le topic' }))

    await waitFor(async () =>
      expect(await sent()).toMatchObject({ path: GMAIL_NOTIFICATIONS, method: 'DELETE' }),
    )
    expect(await screen.findByText('Le topic de l’import Gmail est retiré.')).toBeInTheDocument()
  })
})
