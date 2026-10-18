import type { components } from '@/shared/api/schema'

type Location = components['schemas']['LocationResource']

export const POSITION_PATIENCE_MS = 5_000
const RECENT_ENOUGH_MS = 5 * 60_000

/** Where the device is, when it shares it within the patience; nothing when refused, slow or unknown. */
export function findDevicePosition(): Promise<Location | null> {
  if (!navigator.geolocation) {
    return Promise.resolve(null)
  }
  return Promise.race([askTheDevice(navigator.geolocation), nothingAfter(POSITION_PATIENCE_MS)])
}

function askTheDevice(geolocation: Geolocation): Promise<Location | null> {
  return new Promise((resolve) => {
    geolocation.getCurrentPosition(
      ({ coords: { latitude, longitude } }) => resolve({ latitude, longitude }),
      () => resolve(null),
      { maximumAge: RECENT_ENOUGH_MS, timeout: POSITION_PATIENCE_MS },
    )
  })
}

function nothingAfter(milliseconds: number): Promise<null> {
  return new Promise((resolve) => setTimeout(() => resolve(null), milliseconds))
}
