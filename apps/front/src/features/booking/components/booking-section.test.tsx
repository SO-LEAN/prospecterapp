import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { createMemoryHistory, createRootRoute, createRouter, RouterProvider } from '@tanstack/react-router'
import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mutationsToastingTheirRefusals } from '@/app/query-client'
import type { BookingPage } from '../mutations'
import { BookingSection } from './booking-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

vi.mock('qrcode', () => ({
  default: { toDataURL: async (text: string) => `data:image/png;base64,${btoa(text)}` },
}))

const CODE = 'AbCdEfGhIjKlMnOpQrStUv'
const PERIOD = '0190a1c0-0000-7000-8000-0000000000e1'
const link = (href: string, method: string) => ({ href, method })

const always = {
  self: link('/api/me/booking-page', 'GET'),
  setWindows: link('/api/me/booking-page/windows', 'PATCH'),
  addInactivePeriod: link('/api/me/booking-page/inactive-periods', 'POST'),
  offerChannels: link('/api/me/booking-page/channels', 'PATCH'),
}

const neverSet: BookingPage = {
  open: false,
  windows: [],
  timeZone: null,
  inactivePeriods: [],
  channels: [],
  code: null,
  calendarConnected: true,
  _links: { ...always, open: link('/api/me/booking-page/opening', 'POST') },
}

const open: BookingPage = {
  open: true,
  windows: [{ weekday: 'monday', from: '14:00', to: '17:00' }],
  timeZone: 'Europe/Paris',
  inactivePeriods: [
    {
      id: PERIOD,
      firstDay: '2026-12-21',
      lastDay: '2027-01-04',
      _links: { remove: link(`/api/me/booking-page/inactive-periods/${PERIOD}`, 'DELETE') },
    },
  ],
  channels: ['call'],
  code: CODE,
  calendarConnected: true,
  _links: {
    ...always,
    close: link('/api/me/booking-page/opening', 'DELETE'),
    changeLink: link('/api/me/booking-page/link', 'POST'),
  },
}

async function renderTheSection(page: BookingPage) {
  const queryClient = new QueryClient({ mutationCache: mutationsToastingTheirRefusals() })
  const rootRoute = createRootRoute({ component: () => <BookingSection page={page} /> })
  const router = createRouter({
    routeTree: rootRoute,
    history: createMemoryHistory({ initialEntries: ['/'] }),
  })
  await router.load()
  render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
      <Toaster />
    </QueryClientProvider>,
  )

  return userEvent.setup()
}

const sent = async (call = 0) => {
  const request = theApi.mock.calls[call]?.[0] as Request
  const body = await request.clone().text()

  return { path: new URL(request.url).pathname, method: request.method, body: body ? JSON.parse(body) : null }
}

const refused = (reason: string) =>
  new Response(JSON.stringify({ status: 422, title: 'Unprocessable Content', detail: reason, reason }), {
    status: 422,
    headers: { 'Content-Type': 'application/problem+json' },
  })

const unhandledRejections: unknown[] = []
const keepTheUnhandled = (reason: unknown) => {
  unhandledRejections.push(reason)
}

/** The rejections of the promises are told once the tasks of the moment are done: one task later. */
const rejectionsLeftUnhandled = async () => {
  await new Promise((resolve) => setTimeout(resolve, 0))

  return unhandledRejections
}

beforeEach(() => {
  unhandledRejections.length = 0
  process.on('unhandledRejection', keepTheUnhandled)
})

afterEach(() => {
  process.off('unhandledRejection', keepTheUnhandled)
  theApi.mockReset()
  toast.dismiss()
})

describe('BookingSection', () => {
  it('asks to connect Google before opening, nothing to open without it', async () => {
    await renderTheSection({ ...neverSet, calendarConnected: false })

    expect(screen.getByText('Connectez Google pour activer la prise de rendez-vous')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Aller aux connecteurs' })).toHaveAttribute(
      'href',
      '/settings?section=connectors',
    )
    expect(screen.getByRole('switch', { name: 'Lien de prise de rendez-vous coupé' })).toBeDisabled()
  })

  it('opens my link by its switch', async () => {
    theApi.mockResolvedValue(Response.json(open))
    const user = await renderTheSection(neverSet)

    await user.click(screen.getByRole('switch', { name: 'Lien de prise de rendez-vous coupé' }))

    expect(await sent()).toEqual({ path: '/api/me/booking-page/opening', method: 'POST', body: null })
    expect(await screen.findByText('Votre lien de prise de rendez-vous est actif.')).toBeInTheDocument()
  })

  it('closes my link at once by its switch', async () => {
    theApi.mockResolvedValue(Response.json({ ...open, open: false }))
    const user = await renderTheSection(open)

    await user.click(screen.getByRole('switch', { name: 'Lien de prise de rendez-vous actif' }))

    expect(await sent()).toEqual({ path: '/api/me/booking-page/opening', method: 'DELETE', body: null })
  })

  it('copies the window of my first day to the working days, then saves them in my time zone', async () => {
    theApi.mockResolvedValue(Response.json(open))
    const user = await renderTheSection(open)

    await user.click(screen.getByRole('button', { name: 'Copier sur les jours ouvrés' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer les plages' }))

    const { body } = await sent()
    expect(body.windows).toEqual(
      ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].map((weekday) => ({
        weekday,
        from: '14:00',
        to: '17:00',
      })),
    )
    expect(body.timeZone).toBe(Intl.DateTimeFormat().resolvedOptions().timeZone)
  })

  it('tells a window refused, leaving no rejection unhandled', async () => {
    theApi.mockResolvedValue(refused('windowTooShort'))
    const user = await renderTheSection(open)

    await user.click(screen.getByRole('button', { name: 'Enregistrer les plages' }))

    expect(await screen.findByText('Une plage dure au moins 15 minutes.')).toBeInTheDocument()
    expect(await rejectionsLeftUnhandled()).toEqual([])
  })

  it('turns a day on and sets its window', async () => {
    theApi.mockResolvedValue(Response.json(open))
    const user = await renderTheSection(neverSet)

    await user.click(screen.getByRole('switch', { name: 'Jeudi\u00a0: on peut me réserver' }))
    await user.click(screen.getByRole('combobox', { name: 'Jeudi, à' }))
    await user.click(await screen.findByRole('option', { name: '11:30' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer les plages' }))

    expect((await sent()).body.windows).toEqual([{ weekday: 'thursday', from: '09:00', to: '11:30' }])
  })

  it('adds an inactive period', async () => {
    theApi.mockResolvedValue(Response.json(open, { status: 201 }))
    const user = await renderTheSection(neverSet)

    await user.type(screen.getByLabelText('Du'), '2026-12-21')
    await user.type(screen.getByLabelText('Au'), '2027-01-04')
    await user.click(screen.getByRole('button', { name: 'Ajouter' }))

    expect(await sent()).toEqual({
      path: '/api/me/booking-page/inactive-periods',
      method: 'POST',
      body: { firstDay: '2026-12-21', lastDay: '2027-01-04' },
    })
  })

  it('removes an inactive period', async () => {
    theApi.mockResolvedValue(Response.json({ ...open, inactivePeriods: [] }))
    const user = await renderTheSection(open)

    await user.click(screen.getByRole('button', { name: 'Supprimer la période du 21/12/2026 au 04/01/2027' }))

    expect(await sent()).toEqual({
      path: `/api/me/booking-page/inactive-periods/${PERIOD}`,
      method: 'DELETE',
      body: null,
    })
  })

  it('offers the video call too, saved at once', async () => {
    theApi.mockResolvedValue(Response.json({ ...open, channels: ['call', 'videoCall'] }))
    const user = await renderTheSection(open)

    await user.click(screen.getByRole('checkbox', { name: 'Visio' }))

    expect(await sent()).toEqual({
      path: '/api/me/booking-page/channels',
      method: 'PATCH',
      body: { channels: ['call', 'videoCall'] },
    })
  })

  it('shows my link, to copy, to preview and as a QR code to download', async () => {
    const user = await renderTheSection(open)
    const address = `${window.location.origin}/booking/${CODE}`

    expect(screen.getByRole('textbox', { name: 'Adresse de mon lien' })).toHaveValue(address)
    await user.click(screen.getByRole('button', { name: 'Copier' }))
    expect(await navigator.clipboard.readText()).toBe(address)
    expect(screen.getByRole('link', { name: 'Aperçu' })).toHaveAttribute('href', address)

    await user.click(screen.getByRole('button', { name: 'QR code' }))
    const dialog = await screen.findByRole('dialog')
    expect(
      await within(dialog).findByRole('img', { name: 'QR code de mon lien de prise de rendez-vous' }),
    ).toHaveAttribute('src', `data:image/png;base64,${btoa(address)}`)
    expect(within(dialog).getByRole('link', { name: 'Télécharger en PNG' })).toHaveAttribute('download')
  })

  it('has no link before its first opening', async () => {
    await renderTheSection(neverSet)

    expect(screen.getByText('Activez la prise de rendez-vous pour obtenir votre lien.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Changer de lien' })).not.toBeInTheDocument()
  })

  it('changes my link once I confirm the former one will no longer work', async () => {
    theApi.mockResolvedValue(Response.json({ ...open, code: 'ZyXwVuTsRqPoNmLkJiHgFe' }))
    const user = await renderTheSection(open)

    await user.click(screen.getByRole('button', { name: 'Changer de lien' }))
    const dialog = await screen.findByRole('dialog')
    expect(within(dialog).getByText(/L’ancien lien ne fonctionnera plus/)).toBeInTheDocument()
    await user.click(within(dialog).getByRole('button', { name: 'Changer de lien' }))

    expect(await sent()).toEqual({ path: '/api/me/booking-page/link', method: 'POST', body: null })
    await waitFor(() => expect(screen.getByText('Votre lien a changé.')).toBeInTheDocument())
  })
})
