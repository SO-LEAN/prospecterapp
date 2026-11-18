import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { type Connection, type Connections, connectionsQuery } from '../queries'
import { GoogleCalendarCard } from './google-calendar-card'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const connection = (state: Connection['state'], synced = 0, failed = 0): Connection => ({
  provider: 'google',
  state,
  account: 'michel@exemple.fr',
  connectedAt: '2026-11-14T09:30:00+00:00',
  synced,
  failed,
  gmailImport: 'off',
  lastGmailNotificationAt: null,
  _links: {
    disconnect: { href: '/api/me/connections/google', method: 'DELETE' },
    reconnect: { href: '/api/me/connections/google/authorizations', method: 'POST' },
    ...(failed > 0 ? { retryFailed: { href: '/api/me/connections/google/retries', method: 'POST' } } : {}),
  },
})

const IMPORT_GMAIL = '/api/me/connections/google/gmail-import/authorizations'
const GMAIL_IMPORT = '/api/me/connections/google/gmail-import'

const importing = (
  gmailImport: Connection['gmailImport'],
  lastGmailNotificationAt: string | null = null,
): Connection => {
  const shown = connection('connected')

  return {
    ...shown,
    gmailImport,
    lastGmailNotificationAt,
    _links: {
      ...shown._links,
      ...(gmailImport === 'active' ? {} : { importGmail: { href: IMPORT_GMAIL, method: 'POST' } }),
      ...(gmailImport === 'off' ? {} : { stopGmailImport: { href: GMAIL_IMPORT, method: 'DELETE' } }),
    },
  }
}

const noConnection: Connections = {
  connections: [],
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
}

function renderTheCard(shown: Connection | undefined, offered = true) {
  const queryClient = new QueryClient()
  const goTo = vi.fn()
  render(
    <QueryClientProvider client={queryClient}>
      <GoogleCalendarCard connection={shown} offered={offered} goTo={goTo} />
      <Toaster />
    </QueryClientProvider>,
  )

  return { user: userEvent.setup(), queryClient, goTo }
}

const sent = () => {
  const request = theApi.mock.calls[0]?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method }
}

afterEach(() => {
  theApi.mockReset()
  toast.dismiss()
})

describe('GoogleCalendarCard, the import of my e-mails of Gmail', () => {
  it('is not offered without the right to manage the personal data or a topic of the account', () => {
    renderTheCard(connection('connected'))

    expect(screen.queryByRole('switch', { name: 'Importer mes e-mails Gmail' })).not.toBeInTheDocument()
  })

  it('activates by sending me to Google, which asks the reading of Gmail', async () => {
    theApi.mockResolvedValue(
      Response.json({ address: 'https://accounts.google.test/auth?gmail' }, { status: 201 }),
    )
    const { user, goTo } = renderTheCard(importing('off'))

    await user.click(screen.getByRole('switch', { name: 'Importer mes e-mails Gmail' }))

    expect(sent()).toEqual({ path: IMPORT_GMAIL, method: 'POST' })
    await waitFor(() => expect(goTo).toHaveBeenCalledWith('https://accounts.google.test/auth?gmail'))
  })

  it('shows the import active with the moment of the last notification, and stops it', async () => {
    theApi.mockResolvedValue(Response.json({ ...noConnection, connections: [importing('off')] }))
    const { user } = renderTheCard(importing('active', '2026-11-18T08:15:00+00:00'))

    expect(screen.getByRole('switch', { name: 'Importer mes e-mails Gmail' })).toBeChecked()
    expect(screen.getByText(/^Import actif, dernière notification reçue le /)).toBeInTheDocument()
    await user.click(screen.getByRole('switch', { name: 'Importer mes e-mails Gmail' }))

    expect(sent()).toEqual({ path: GMAIL_IMPORT, method: 'DELETE' })
    expect(await screen.findByText('L’import de vos e-mails Gmail est coupé.')).toBeInTheDocument()
  })

  it('says nothing more of an import off', () => {
    renderTheCard(importing('off'))

    expect(screen.getByRole('switch', { name: 'Importer mes e-mails Gmail' })).not.toBeChecked()
    expect(screen.queryByText(/^Import actif/)).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Couper l’import' })).not.toBeInTheDocument()
  })

  it('dates the last notification received', () => {
    renderTheCard(importing('active', '2026-11-18T08:15:00+00:00'))

    expect(
      screen.getByText(/^Import actif, dernière notification reçue le 18 nov\. 2026/),
    ).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Couper l’import' })).not.toBeInTheDocument()
  })

  it('keeps the connections the API answers once the import is stopped', async () => {
    const answered = { ...noConnection, connections: [importing('off')] }
    theApi.mockResolvedValue(Response.json(answered))
    const { user, queryClient } = renderTheCard(importing('active'))

    await user.click(screen.getByRole('switch', { name: 'Importer mes e-mails Gmail' }))

    await waitFor(() => expect(queryClient.getQueryData(connectionsQuery().queryKey)).toEqual(answered))
  })

  it('says when no notification was received yet', () => {
    renderTheCard(importing('active'))

    expect(screen.getByText('Import actif, aucune notification reçue pour le moment.')).toBeInTheDocument()
  })

  it('stops an import to reconnect', async () => {
    theApi.mockResolvedValue(Response.json({ ...noConnection, connections: [importing('off')] }))
    const { user } = renderTheCard(importing('toReconnect'))

    await user.click(screen.getByRole('button', { name: 'Couper l’import' }))

    expect(sent()).toEqual({ path: GMAIL_IMPORT, method: 'DELETE' })
    expect(await screen.findByText('L’import de vos e-mails Gmail est coupé.')).toBeInTheDocument()
  })

  it('says the import is to reconnect once Google refused to renew it', () => {
    renderTheCard(importing('toReconnect'))

    expect(screen.getByRole('switch', { name: 'Importer mes e-mails Gmail' })).not.toBeChecked()
    expect(
      screen.getByText('Google n’accorde plus la lecture de Gmail : réactivez l’import pour le reconnecter.'),
    ).toBeInTheDocument()
  })
})

describe('GoogleCalendarCard', () => {
  it('connects my account by sending me to the address of Google', async () => {
    theApi.mockResolvedValue(
      Response.json({ address: 'https://accounts.google.test/auth?state=s' }, { status: 201 }),
    )
    const { user, goTo } = renderTheCard(undefined)

    await user.click(screen.getByRole('button', { name: 'Connecter' }))

    expect(sent()).toEqual({ path: '/api/me/connections/google/authorizations', method: 'POST' })
    await waitFor(() => expect(goTo).toHaveBeenCalledWith('https://accounts.google.test/auth?state=s'))
  })

  it('shows the account connected and since when, to reconnect or disconnect', () => {
    renderTheCard(connection('connected'))

    expect(screen.getByText('Google Agenda')).toBeInTheDocument()
    expect(screen.getByText('Vos échanges planifiés sont ajoutés à votre agenda Google.')).toBeInTheDocument()
    expect(screen.getByText(/Connecté au compte michel@exemple\.fr depuis le/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Reconnecter' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Déconnecter' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Connecter' })).not.toBeInTheDocument()
  })

  it('says a connection Google refuses is to reconnect', () => {
    renderTheCard(connection('toReconnect'))

    expect(screen.getByText(/Google n’accorde plus l’accès au compte michel@exemple\.fr/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Reconnecter' })).toBeInTheDocument()
  })

  it('disconnects my account once confirmed, and keeps the connections the API answers', async () => {
    theApi.mockResolvedValue(Response.json(noConnection))
    const { user, queryClient } = renderTheCard(connection('connected'))

    await user.click(screen.getByRole('button', { name: 'Déconnecter' }))
    expect(theApi).not.toHaveBeenCalled()
    await user.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Déconnecter' }))

    await waitFor(() => expect(sent()).toEqual({ path: '/api/me/connections/google', method: 'DELETE' }))
    await waitFor(() => expect(queryClient.getQueryData(connectionsQuery().queryKey)).toEqual(noConnection))
    expect(await screen.findByText('Votre compte Google est déconnecté.')).toBeInTheDocument()
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('counts my exchanges to come synced and failed, and pushes the failed again', async () => {
    const allSynced = { ...noConnection, connections: [connection('connected', 13, 0)] }
    theApi.mockResolvedValue(Response.json(allSynced))
    const { user, queryClient } = renderTheCard(connection('connected', 12, 1))

    expect(screen.getByText('12 échanges synchronisés, 1 en échec')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Tout réessayer' }))

    await waitFor(() =>
      expect(sent()).toEqual({ path: '/api/me/connections/google/retries', method: 'POST' }),
    )
    await waitFor(() => expect(queryClient.getQueryData(connectionsQuery().queryKey)).toEqual(allSynced))
  })

  it('counts nothing failed and offers no retry while every exchange is synced', () => {
    renderTheCard(connection('connected', 1, 0))

    expect(screen.getByText('1 échange synchronisé')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Tout réessayer' })).not.toBeInTheDocument()
  })

  it('says Google is unavailable and offers no connection while it is not offered', () => {
    renderTheCard(undefined, false)

    expect(screen.getByText('Google Agenda n’est pas disponible pour le moment.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Connecter' })).not.toBeInTheDocument()
  })

  it('keeps disconnecting, but no longer reconnecting, a connection while Google is not offered', () => {
    renderTheCard(connection('connected'), false)

    expect(screen.getByText(/Connecté au compte michel@exemple\.fr/)).toBeInTheDocument()
    expect(screen.getByText('Google Agenda n’est pas disponible pour le moment.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Reconnecter' })).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Déconnecter' })).toBeInTheDocument()
  })
})
