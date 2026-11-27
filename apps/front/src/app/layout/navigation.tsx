import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import {
  Building2,
  CalendarDays,
  LayoutDashboard,
  type LucideIcon,
  Megaphone,
  PanelLeftClose,
  PanelLeftOpen,
  SlidersHorizontal,
  UsersRound,
} from 'lucide-react'
import type { ComponentType, ReactElement } from 'react'
import { useTranslation } from 'react-i18next'
import type { Account } from '@/features/account/mutations'
import { accountQuery } from '@/features/account/queries'
import { ToProcessCount } from '@/features/exchanges/to-process/components/to-process-count'
import { cn } from '@/shared/lib/utils'
import { usePreferences } from '@/shared/stores/preferences'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'
import { BrandMark } from './brand'
import { CampaignsCount, ContactsCount, OrganizationsCount } from './navigation-counts'
import { UserMenu } from './user-menu'

type Item = {
  to: '/' | '/organizations' | '/contacts' | '/calendar' | '/campaigns'
  label: 'nav.dashboard' | 'nav.organizations' | 'nav.contacts' | 'nav.calendar' | 'nav.campaigns'
  icon: LucideIcon
  exact?: boolean
  /** What there is there, counted beside the link from what the app already read. */
  count?: ComponentType<{ className?: string }>
  /** Shown only when my account carries this link. The API says what I may reach. */
  link?: keyof Account['_links']
}

export const MAIN_ENTRIES: Item[] = [
  { to: '/', label: 'nav.dashboard', icon: LayoutDashboard, exact: true, count: ToProcessCount },
  { to: '/organizations', label: 'nav.organizations', icon: Building2, count: OrganizationsCount },
  { to: '/contacts', label: 'nav.contacts', icon: UsersRound, count: ContactsCount },
  { to: '/calendar', label: 'nav.calendar', icon: CalendarDays },
  { to: '/campaigns', label: 'nav.campaigns', icon: Megaphone, link: 'campaigns', count: CampaignsCount },
]

/** An entry of the ink sidebar. The gold mark at its left says the page is the one shown. */
const ENTRY = cn(
  'group relative flex h-9 items-center gap-3 rounded-lg px-2.5 font-medium text-sidebar-muted-foreground text-sm transition-colors',
  'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
  'data-[status=active]:bg-sidebar-accent data-[status=active]:text-sidebar-foreground',
  'before:absolute before:top-2 before:bottom-2 before:-left-3 before:w-[3px] before:rounded-r-full before:bg-gold before:opacity-0 data-[status=active]:before:opacity-100',
)

type Props = { collapsed?: boolean; onNavigate?: () => void }

/** Brand, my account, the main links, then the settings and my avatar: the desktop sidebar and the mobile sheet. */
export function Navigation({ collapsed = false, onNavigate }: Props) {
  const { t } = useTranslation()
  const toggleSidebar = usePreferences((state) => state.toggleSidebar)
  const account = useQuery(accountQuery())
  const reachable = MAIN_ENTRIES.filter((item) => !item.link || account.data?._links[item.link] !== undefined)

  return (
    <div className="flex h-full flex-col gap-6 p-3 text-sidebar-foreground">
      <div className={cn('flex items-center gap-3 px-1.5 py-1', collapsed && 'justify-center px-0')}>
        <Link to="/" onClick={onNavigate} aria-label={t('app.name')}>
          <BrandMark />
        </Link>
        {!collapsed && (
          <div className="min-w-0 leading-tight">
            <p className="font-semibold text-[15px] tracking-tight">{t('app.name')}</p>
            {account.data && (
              <p className="truncate text-sidebar-muted-foreground text-xs">
                {account.data.organization.legalIdentity.corporateName}
              </p>
            )}
          </div>
        )}
      </div>

      <nav aria-label={t('nav.mainNavigation')} className="flex flex-1 flex-col gap-1">
        {reachable.map(({ to, label, icon: Icon, exact, count: Count }) => (
          <Explained key={to} collapsed={collapsed} label={t(label)}>
            <Link
              to={to}
              onClick={onNavigate}
              activeOptions={{ exact }}
              className={cn(ENTRY, collapsed && 'justify-center px-0')}
            >
              <Icon className="size-[18px] shrink-0 group-data-[status=active]:text-gold" aria-hidden />
              {collapsed ? <span className="sr-only">{t(label)}</span> : t(label)}
              {Count && (
                <Count className={cn(collapsed && 'absolute -top-1 -right-1 h-4 min-w-4 px-1 text-[10px]')} />
              )}
            </Link>
          </Explained>
        ))}
      </nav>

      <div className="flex flex-col gap-1">
        <Explained collapsed={collapsed} label={t('nav.settings')}>
          <Link to="/settings" onClick={onNavigate} className={cn(ENTRY, collapsed && 'justify-center px-0')}>
            <SlidersHorizontal
              className="size-[18px] shrink-0 group-data-[status=active]:text-gold"
              aria-hidden
            />
            <span className={cn(collapsed && 'sr-only')}>{t('nav.settings')}</span>
          </Link>
        </Explained>
        <UserMenu collapsed={collapsed} />
        {!onNavigate && (
          <button
            type="button"
            onClick={toggleSidebar}
            className={cn(ENTRY, 'font-normal', collapsed && 'justify-center px-0')}
          >
            {collapsed ? (
              <PanelLeftOpen className="size-[18px]" aria-hidden />
            ) : (
              <PanelLeftClose className="size-[18px]" aria-hidden />
            )}
            <span className={cn(collapsed && 'sr-only')}>{collapsed ? t('nav.unfold') : t('nav.fold')}</span>
          </button>
        )}
      </div>
    </div>
  )
}

/** A folded entry shows its icon only: a tooltip names it. */
function Explained({
  collapsed,
  label,
  children,
}: {
  collapsed: boolean
  label: string
  children: ReactElement
}) {
  if (!collapsed) return children

  return (
    <Tooltip>
      <TooltipTrigger asChild>{children}</TooltipTrigger>
      <TooltipContent side="right">{label}</TooltipContent>
    </Tooltip>
  )
}
