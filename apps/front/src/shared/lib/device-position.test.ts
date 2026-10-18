import { afterEach, describe, expect, it, vi } from 'vitest'
import { findDevicePosition, POSITION_PATIENCE_MS } from './device-position'

const lyon = { latitude: 45.764, longitude: 4.8357 }

function givenTheDevice(getCurrentPosition: Geolocation['getCurrentPosition']) {
  vi.stubGlobal('navigator', { geolocation: { getCurrentPosition } })
}

function sharing(place: typeof lyon): Geolocation['getCurrentPosition'] {
  return (success) => success({ coords: place } as GeolocationPosition)
}

const refusing: Geolocation['getCurrentPosition'] = (_success, error) =>
  error?.({ code: 1, message: 'User denied Geolocation' } as GeolocationPositionError)

const silent: Geolocation['getCurrentPosition'] = () => {}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.useRealTimers()
})

describe('findDevicePosition', () => {
  it('gives the position the device shares', async () => {
    givenTheDevice(sharing(lyon))

    expect(await findDevicePosition()).toEqual(lyon)
  })

  it('gives nothing when the position is refused', async () => {
    givenTheDevice(refusing)

    expect(await findDevicePosition()).toBeNull()
  })

  it('gives nothing when the position does not come in time', async () => {
    vi.useFakeTimers()
    givenTheDevice(silent)

    const position = findDevicePosition()
    await vi.advanceTimersByTimeAsync(POSITION_PATIENCE_MS)

    expect(await position).toBeNull()
  })

  it('gives nothing on a device without geolocation', async () => {
    vi.stubGlobal('navigator', {})

    expect(await findDevicePosition()).toBeNull()
  })
})
