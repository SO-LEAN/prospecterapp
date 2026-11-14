import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { CircleAlert } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { connectionsQuery, isAnyToReconnect } from '../queries'

/** A discreet line above every page while a provider refuses the access of one of my connections. */
export function ReconnectBanner() {
  const { t } = useTranslation()
  const { data: connections } = useQuery(connectionsQuery())

  if (!connections || !isAnyToReconnect(connections)) {
    return null
  }

  return (
    <div
      role="status"
      className="mb-6 flex items-center gap-2 rounded-lg border border-warning px-4 py-2 text-sm"
    >
      <CircleAlert aria-hidden className="size-4 shrink-0 text-warning" />
      <span>{t('settings.connectors.banner.text')}</span>
      <Link
        to="/settings"
        search={{ section: 'connectors' }}
        className="ml-auto font-medium text-primary hover:underline"
      >
        {t('settings.connectors.reconnect')}
      </Link>
    </div>
  )
}
