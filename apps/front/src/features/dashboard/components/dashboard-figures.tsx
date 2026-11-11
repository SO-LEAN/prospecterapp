import { Building2, CalendarDays, MapPinned, MessagesSquare, Users } from 'lucide-react'
import { useTranslation } from 'react-i18next'
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
      />
      <StatCard
        icon={MapPinned}
        label={t('dashboard.stats.locatedOrganizations')}
        value={stats.locatedOrganizations}
      />
      <StatCard icon={Users} label={t('dashboard.stats.contacts')} value={stats.contacts} />
      <StatCard icon={MessagesSquare} label={t('dashboard.stats.exchanges')} value={stats.exchanges} />
      <StatCard
        icon={CalendarDays}
        label={t('dashboard.stats.exchangesThisMonth')}
        value={stats.exchangesThisMonth}
      />
    </div>
  )
}
