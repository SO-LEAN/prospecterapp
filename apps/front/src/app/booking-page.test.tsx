import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The page a public link of a booking leads to, opened by anyone, without signing in (ADR 0148, 0151, 0153). */

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

const theVisitor = vi.hoisted(() => ({ timeZone: 'Europe/Paris' }))

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null, signOut: async () => {}, signOutEverywhere: async () => {} },
}))

vi.mock('@/shared/lib/time-zone', () => ({ readerTimeZone: () => theVisitor.timeZone }))

const CODE = 'AbCdEfGhIjKlMnOpQrStUv'

const theApiAnswers = (status: number, body: unknown) =>
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    expect(new URL(request.url).pathname).toBe(`/api/booking-pages/${CODE}`)
    return Response.json(body, {
      status,
      headers: status >= 400 ? { 'Content-Type': 'application/problem+json' } : {},
    })
  })

const anOpenPage = (channels: string[] = ['call', 'videoCall']) => ({
  open: true,
  host: {
    firstName: 'Camille',
    lastName: 'Martin',
    picture: {
      url: 'https://static.test/camille.jpg',
      smallUrl: 'https://static.test/small/camille.jpg',
      mediumUrl: 'https://static.test/medium/camille.jpg',
    },
    organization: 'SOLEAN IT',
  },
  channels,
  timeZone: 'Europe/Paris',
  days: [
    { day: '2026-11-24', slots: ['2026-11-24T09:00:00+01:00', '2026-11-24T09:15:00+01:00'] },
    { day: '2026-11-25', slots: [] },
  ],
})

afterEach(() => {
  theApi.mockReset()
  theVisitor.timeZone = 'Europe/Paris'
})

describe('the public page of a booking', () => {
  it('says whom the booker meets, for which organization, for how long', async () => {
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('heading', { name: 'Camille Martin' })).toBeInTheDocument()
    expect(screen.getByText('SOLEAN IT')).toBeInTheDocument()
    expect(screen.getByText('Rendez-vous de 15 minutes')).toBeInTheDocument()
  })

  it('shows the free slots of each day on the clock of Paris, a day without slot greyed', async () => {
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    const tuesday = await screen.findByRole('group', { name: 'mar. 24 nov.' })
    expect(
      within(tuesday)
        .getAllByRole('button')
        .map((slot) => slot.textContent),
    ).toEqual(['09:00', '09:15'])
    expect(
      within(screen.getByRole('group', { name: 'mer. 25 nov.' })).getByText('Aucun créneau'),
    ).toBeInTheDocument()
    expect(screen.getByText('Heures de Paris')).toBeInTheDocument()
  })

  it('converts the slots to the clock of a visitor elsewhere', async () => {
    theVisitor.timeZone = 'Europe/London'
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    const tuesday = await screen.findByRole('group', { name: 'mar. 24 nov.' })
    expect(
      within(tuesday)
        .getAllByRole('button')
        .map((slot) => slot.textContent),
    ).toEqual(['08:00', '08:15'])
    expect(screen.getByText('Heures de London, converties depuis l’heure de Paris')).toBeInTheDocument()
  })

  it('offers the channels of its host only, then a slot of the channel chosen', async () => {
    theApiAnswers(200, anOpenPage(['videoCall']))

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('radio', { name: 'Visio' })).toBeChecked()
    expect(screen.queryByRole('radio', { name: 'Appel' })).not.toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: '09:15' }))
    expect(screen.getByRole('button', { name: '09:15' })).toHaveAttribute('aria-pressed', 'true')
    expect(screen.getByText('Visio le mar. 24 nov. à 09:15')).toBeInTheDocument()
  })

  it('waits for a channel before a slot when its host offers several', async () => {
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('button', { name: '09:00' })).toBeDisabled()
    await userEvent.click(screen.getByRole('radio', { name: 'Appel' }))
    expect(screen.getByRole('button', { name: '09:00' })).toBeEnabled()
  })

  it('says no slot is available when it offers none, without saying why', async () => {
    theApiAnswers(200, { open: false, host: null, channels: [], timeZone: null, days: [] })

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByText('Aucun créneau n’est disponible pour le moment.')).toBeInTheDocument()
  })
})
