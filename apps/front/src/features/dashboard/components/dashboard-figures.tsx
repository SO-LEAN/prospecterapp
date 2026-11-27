import { linkOptions } from '@tanstack/react-router'
import { Building2, CalendarDays, MapPinned, MessagesSquare, Users } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { ORGANIZATIONS_MAP } from '../organizations-map'
import type { DashboardStats } from '../queries'
import { StatCard } from './stat-card'

type Props = { stats: DashboardStats }

export function DashboardFigures({ stats }: Props) {
  const { t } = useTranslation()

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
      <StatCard
        icon={Building2}
        label={t('dashboard.stats.activeOrganizations')}
        value={stats.activeOrganizations}
        destination={{
          link: linkOptions({ to: '/organizations' }),
          name: t('dashboard.stats.destination.organizations'),
        }}
      />
      <StatCard
        icon={MapPinned}
        label={t('dashboard.stats.locatedOrganizations')}
        value={stats.locatedOrganizations}
        destination={{
          link: linkOptions({ to: '/', hash: ORGANIZATIONS_MAP }),
          name: t('dashboard.stats.destination.map'),
        }}
      />
      <StatCard
        icon={Users}
        label={t('dashboard.stats.contacts')}
        value={stats.contacts}
        destination={{
          link: linkOptions({ to: '/contacts' }),
          name: t('dashboard.stats.destination.contacts'),
        }}
      />
      <StatCard icon={MessagesSquare} label={t('dashboard.stats.exchanges')} value={stats.exchanges} />
      <StatCard
        icon={CalendarDays}
        label={t('dashboard.stats.exchangesThisMonth')}
        value={stats.exchangesThisMonth}
        destination={{
          link: linkOptions({ to: '/calendar', search: { view: 'month' } }),
          name: t('dashboard.stats.destination.calendar'),
        }}
      />
    </div>
  )
}
