import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { DirectionsLink } from './directions-link'

const colmar = { latitude: 48.0794, longitude: 7.3585 }
const strasbourg = { latitude: 48.5834, longitude: 7.7456 }
const lyon = { latitude: 45.764, longitude: 4.8357 }
const ROUTE_TO_COLMAR = 'https://www.openstreetmap.org/directions?to=48.0794%2C7.3585'
const ROUTE_FROM_LYON = 'https://www.openstreetmap.org/directions?from=45.764%2C4.8357&to=48.0794%2C7.3585'

function givenTheDevice(getCurrentPosition: Geolocation['getCurrentPosition']) {
  const geolocation = { getCurrentPosition: vi.fn(getCurrentPosition) }
  vi.stubGlobal('navigator', { ...navigator, geolocation })
  return geolocation
}

const sharingLyon: Geolocation['getCurrentPosition'] = (success) =>
  success({ coords: lyon } as GeolocationPosition)

function givenTheBrowserOpensTabs() {
  const tab = { opener: window }
  return { tab, open: vi.spyOn(window, 'open').mockReturnValue(tab as unknown as Window) }
}

function renderTheLink(start: typeof strasbourg | null) {
  render(
    <>
      <DirectionsLink to={colmar} start={start} />
      <Toaster />
    </>,
  )
  return userEvent.setup()
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.restoreAllMocks()
})

describe('DirectionsLink', () => {
  it('starts from my organization, without asking the device', async () => {
    const device = givenTheDevice(sharingLyon)
    const { open } = givenTheBrowserOpensTabs()
    const user = renderTheLink(strasbourg)

    const link = screen.getByRole('link', { name: 'Itinéraire' })
    await user.click(link)

    expect(link).toHaveAttribute(
      'href',
      'https://www.openstreetmap.org/directions?from=48.5834%2C7.7456&to=48.0794%2C7.3585',
    )
    expect(device.getCurrentPosition).not.toHaveBeenCalled()
    expect(open, 'the link opens the route itself').not.toHaveBeenCalled()
  })

  it('without my organization, starts from the position the device shares', async () => {
    givenTheDevice(sharingLyon)
    const { open, tab } = givenTheBrowserOpensTabs()
    const user = renderTheLink(null)

    await user.click(screen.getByRole('link', { name: 'Itinéraire' }))

    await vi.waitFor(() => expect(open).toHaveBeenCalledWith(ROUTE_FROM_LYON, '_blank'))
    expect(tab.opener).toBeNull()
  })

  it('without any start, opens the route with the arrival only', async () => {
    givenTheDevice((_success, error) => error?.({ code: 1 } as GeolocationPositionError))
    const { open } = givenTheBrowserOpensTabs()
    const user = renderTheLink(null)

    await user.click(screen.getByRole('link', { name: 'Itinéraire' }))

    await vi.waitFor(() => expect(open).toHaveBeenCalledWith(ROUTE_TO_COLMAR, '_blank'))
  })

  it('offers the route when the browser blocked its tab, the position having come late', async () => {
    givenTheDevice(sharingLyon)
    const { open } = givenTheBrowserOpensTabs()
    open.mockReturnValueOnce(null)
    const user = renderTheLink(null)

    await user.click(screen.getByRole('link', { name: 'Itinéraire' }))
    await user.click(await screen.findByRole('button', { name: "Ouvrir l'itinéraire" }))

    expect(open).toHaveBeenLastCalledWith(ROUTE_FROM_LYON, '_blank')
  })

  it('while the position is awaited, says so and opens one route only', async () => {
    const questions: PositionCallback[] = []
    givenTheDevice((success) => {
      questions.push(success)
    })
    const { open } = givenTheBrowserOpensTabs()
    const user = renderTheLink(null)
    const link = screen.getByRole('link', { name: 'Itinéraire' })

    await user.click(link)
    await user.click(link)

    expect(link).toHaveAttribute('aria-busy', 'true')
    for (const answer of questions) {
      answer({ coords: lyon } as GeolocationPosition)
    }
    await vi.waitFor(() => expect(link).not.toHaveAttribute('aria-busy', 'true'))
    expect(open).toHaveBeenCalledTimes(1)
  })

  it('asks the device only on the click', () => {
    const device = givenTheDevice(sharingLyon)

    renderTheLink(null)

    expect(device.getCurrentPosition).not.toHaveBeenCalled()
  })
})
