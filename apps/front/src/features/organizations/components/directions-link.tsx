import { ExternalLink } from 'lucide-react'
import type { MouseEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import type { components } from '@/shared/api/schema'
import { i18n } from '@/shared/i18n'
import { directionsUrl } from '@/shared/lib/address'
import { findDevicePosition } from '@/shared/lib/device-position'
import { Button } from '@/shared/ui/button'

type Location = components['schemas']['LocationResource']

/**
 * The route to `to` on OpenStreetMap, from `start` (my organization) when known, else from where the device is,
 * asked only on the click.
 */
export function DirectionsLink({ to, start }: { to: Location; start?: Location | null }) {
  const { t } = useTranslation()

  const startFromTheDevice = (event: MouseEvent<HTMLAnchorElement>) => {
    event.preventDefault()
    void openTheRouteFromTheDevice(to)
  }

  return (
    <Button variant="outline" size="sm" asChild>
      <a
        href={directionsUrl(to, start)}
        target="_blank"
        rel="noreferrer"
        onClick={start ? undefined : startFromTheDevice}
      >
        {t('organizations.address.directions')} <ExternalLink />
      </a>
    </Button>
  )
}

/**
 * The position is asked from this tab, where the browser shows its question. A tab opened after a late answer is
 * blocked by the browser: the route is then offered, to be opened by a new click.
 */
async function openTheRouteFromTheDevice(to: Location) {
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
