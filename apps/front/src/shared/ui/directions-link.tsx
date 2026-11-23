import { ExternalLink, Loader2 } from 'lucide-react'
import { type MouseEvent, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import type { components } from '@/shared/api/schema'
import { i18n } from '@/shared/i18n'
import { type Destination, directionsUrl } from '@/shared/lib/address'
import { findDevicePosition } from '@/shared/lib/device-position'
import { Button } from '@/shared/ui/button'

type Location = components['schemas']['LocationResource']

/**
 * The route on OpenStreetMap to `to`, a position or a free-form address. The route starts from `start` (my
 * organization) when it is known, or else from the device's position. The link asks for that position only on a click.
 */
export function DirectionsLink({ to, start }: { to: Destination; start?: Location | null }) {
  const { t } = useTranslation()
  const [locating, setLocating] = useState(false)

  const startFromTheDevice = async (event: MouseEvent<HTMLAnchorElement>) => {
    event.preventDefault()
    if (locating) {
      return
    }
    setLocating(true)
    await openTheRouteFromTheDevice(to)
    setLocating(false)
  }

  return (
    <Button variant="outline" size="sm" asChild>
      <a
        href={directionsUrl(to, start)}
        target="_blank"
        rel="noreferrer"
        onClick={start ? undefined : startFromTheDevice}
        aria-busy={locating}
      >
        {t('organizations.address.directions')}{' '}
        {locating ? <Loader2 className="animate-spin" aria-hidden /> : <ExternalLink />}
      </a>
    </Button>
  )
}

/**
 * This tab asks for the position, and the browser shows its question here. The browser blocks a tab opened after a
 * late answer, so the link then offers the route for a new click.
 */
async function openTheRouteFromTheDevice(to: Destination) {
  const route = directionsUrl(to, await findDevicePosition())
  if (!openInANewTab(route)) {
    toast(i18n.t('organizations.address.directionsReady'), {
      action: { label: i18n.t('organizations.address.openDirections'), onClick: () => openInANewTab(route) },
    })
  }
}

function openInANewTab(url: string): boolean {
  const tab = window.open(url, '_blank')
  if (tab === null) {
    return false
  }
  tab.opener = null
  return true
}
