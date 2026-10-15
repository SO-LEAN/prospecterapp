import { Link } from '@tanstack/react-router'
import {
  Building2,
  LayoutDashboard,
  type LucideIcon,
  PanelLeftClose,
  PanelLeftOpen,
  UserRound,
  UsersRound,
} from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { usePreferences } from '@/shared/stores/preferences'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'
import { BrandMark } from './brand'

type Item = {
  to: '/' | '/organizations' | '/contacts' | '/account'
  label: 'nav.dashboard' | 'nav.organizations' | 'nav.contacts' | 'nav.account'
  icon: LucideIcon
  exact?: boolean
}

const items: Item[] = [
  { to: '/', label: 'nav.dashboard', icon: LayoutDashboard, exact: true },
  { to: '/organizations', label: 'nav.organizations', icon: Building2 },
  { to: '/contacts', label: 'nav.contacts', icon: UsersRound },
  { to: '/account', label: 'nav.account', icon: UserRound },
]

type Props = { collapsed?: boolean; onNavigate?: () => void }

/** Brand, main links and the collapse toggle; used by the desktop sidebar and the mobile sheet. */
export function Navigation({ collapsed = false, onNavigate }: Props) {
  const { t } = useTranslation()
  const toggleSidebar = usePreferences((state) => state.toggleSidebar)

  return (
    <div className="flex h-full flex-col gap-6 p-3">
      <Link
        to="/"
        onClick={onNavigate}
        className="flex items-center gap-3 rounded-lg px-1.5 py-1 font-semibold tracking-tight"
      >
        <BrandMark />
        {!collapsed && <span className="text-[15px]">{t('app.name')}</span>}
      </Link>

      <nav aria-label={t('nav.mainNavigation')} className="flex flex-1 flex-col gap-1">
        {items.map(({ to, label, icon: Icon, exact }) => {
          const link = (
            <Link
              key={to}
              to={to}
              onClick={onNavigate}
              activeOptions={{ exact }}
              className={cn(
                'group flex h-9 items-center gap-3 rounded-lg px-2.5 font-medium text-muted-foreground text-sm transition-colors',
                'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                'data-[status=active]:bg-sidebar-accent data-[status=active]:text-foreground',
                collapsed && 'justify-center px-0',
              )}
            >
              <Icon className="size-[18px] shrink-0 group-data-[status=active]:text-primary" aria-hidden />
              {collapsed ? <span className="sr-only">{t(label)}</span> : t(label)}
            </Link>
          )
          return collapsed ? (
            <Tooltip key={to}>
              <TooltipTrigger asChild>{link}</TooltipTrigger>
              <TooltipContent side="right">{t(label)}</TooltipContent>
            </Tooltip>
          ) : (
            link
          )
        })}
      </nav>

      {!onNavigate && (
        <button
          type="button"
          onClick={toggleSidebar}
          className={cn(
            'flex h-9 items-center gap-3 rounded-lg px-2.5 text-muted-foreground text-sm transition-colors hover:bg-sidebar-accent hover:text-foreground',
            collapsed && 'justify-center px-0',
          )}
        >
          {collapsed ? (
            <PanelLeftOpen className="size-[18px]" aria-hidden />
          ) : (
            <PanelLeftClose className="size-[18px]" aria-hidden />
          )}
          <span className={cn(collapsed && 'sr-only')}>
            {collapsed ? t('nav.expand') : t('nav.collapse')}
          </span>
        </button>
      )}
    </div>
  )
}
