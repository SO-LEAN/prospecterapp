import { screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The page a public link of a booking leads to, opened by anyone, without signing in (ADR 0148, 0151). */

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null, signOut: async () => {} },
}))

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

afterEach(() => {
  theApi.mockReset()
})

describe('the public page of a booking', () => {
  it('says how its host meets the bookers while it is open', async () => {
    theApiAnswers(200, { open: true, channels: ['call', 'videoCall'], days: [] })

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('heading', { name: 'Prendre rendez-vous' })).toBeInTheDocument()
    expect(screen.getByText('Rencontre possible par : Appel, Visio')).toBeInTheDocument()
  })

  it('offers no slot when it shows nothing', async () => {
    theApiAnswers(200, { open: false, channels: [], days: [] })

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByText('Aucun créneau disponible pour le moment.')).toBeInTheDocument()
  })
})
