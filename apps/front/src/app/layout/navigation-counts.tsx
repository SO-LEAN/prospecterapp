import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { campaignsQuery } from '@/features/campaigns/queries'
import { shownBy } from '@/features/campaigns/status-filter'
import { dashboardQuery } from '@/features/dashboard/queries'
import { cn } from '@/shared/lib/utils'

/** A count shown only from what the app already read: the navigation never asks the API for it. */
const ALREADY_READ = { enabled: false } as const

type Props = { className?: string }

/** The organizations I prospect, as the dashboard counted them. */
export function OrganizationsCount({ className }: Props) {
  const { t } = useTranslation()
  const { data } = useQuery({ ...dashboardQuery(), ...ALREADY_READ })
  const count = data?.stats.activeOrganizations

  return <Count count={count} description={t('nav.counts.organizations', { count })} className={className} />
}

/** The contacts I hold, as the dashboard counted them. */
export function ContactsCount({ className }: Props) {
  const { t } = useTranslation()
  const { data } = useQuery({ ...dashboardQuery(), ...ALREADY_READ })
  const count = data?.stats.contacts

  return <Count count={count} description={t('nav.counts.contacts', { count })} className={className} />
}

/** The campaigns still running, as their list counts them by default. */
export function CampaignsCount({ className }: Props) {
  const { t } = useTranslation()
  const { data } = useQuery({ ...campaignsQuery(), ...ALREADY_READ })
  const count = data?.items.filter((campaign) => shownBy('running', campaign)).length

  return <Count count={count} description={t('nav.counts.campaigns', { count })} className={className} />
}

function Count({
  count,
  description,
  className,
}: Props & { count: number | undefined; description: string }) {
  if (!count) return null

  return (
    <span className={cn('ms-auto text-sidebar-muted-foreground text-xs tabular-nums', className)}>
      <span aria-hidden>{count}</span>
      <span className="sr-only">{description}</span>
    </span>
  )
}
