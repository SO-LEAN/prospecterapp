import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { LogOut, Monitor, MonitorOff, Moon, Network, Sun, UserRound } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { myName } from '@/features/account/me'
import { accountQuery } from '@/features/account/queries'
import { EVENT_STORMING_OVERVIEW, servedDocumentsQuery } from '@/shared/config/documents'
import { initials } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { type Theme, usePreferences } from '@/shared/stores/preferences'
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'
import { Skeleton } from '@/shared/ui/skeleton'
import { useSignOut } from './sign-out'

const themes = [
  { value: 'light', icon: Sun },
  { value: 'dark', icon: Moon },
  { value: 'system', icon: Monitor },
] as const

/** My avatar at the foot of the sidebar, and its menu: my account, the theme, the architecture, the sign-out. */
export function UserMenu({ collapsed }: { collapsed: boolean }) {
  const { t } = useTranslation()
  const { data: account, isPending } = useQuery(accountQuery())
  const signOut = useSignOut()
  const signOutEverywhere = useSignOut('everywhere')
  const theme = usePreferences((state) => state.theme)
  const setTheme = usePreferences((state) => state.setTheme)
  const { data: theDocumentsAreServed } = useQuery(servedDocumentsQuery())

  if (isPending) {
    return <Skeleton className="size-8 rounded-full" />
  }

  const fullName = account ? myName(account) : ''

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        aria-label={t('nav.userMenu', { name: fullName })}
        className={cn(
          'flex h-11 items-center gap-2.5 rounded-lg px-1.5 text-left outline-none transition-colors hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-ring',
          collapsed && 'justify-center px-0',
        )}
      >
        <Avatar className="size-8">
          {account?.picture && <AvatarImage src={account.picture.smallUrl} alt="" />}
          <AvatarFallback className="bg-sidebar-accent font-medium text-sidebar-foreground text-xs">
            {initials(fullName)}
          </AvatarFallback>
        </Avatar>
        {!collapsed && <span className="min-w-0 truncate font-medium text-sm">{fullName}</span>}
      </DropdownMenuTrigger>
      <DropdownMenuContent side="right" align="end" className="w-60">
        <DropdownMenuLabel className="font-normal">
          <p className="truncate font-medium text-sm">{fullName}</p>
          <p className="truncate text-muted-foreground text-xs">
            {account?.organization.legalIdentity.corporateName}
          </p>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
          <Link to="/account">
            <UserRound /> {t('nav.account')}
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuLabel className="text-muted-foreground text-xs">{t('theme.label')}</DropdownMenuLabel>
        <DropdownMenuRadioGroup value={theme} onValueChange={(value) => setTheme(value as Theme)}>
          {themes.map(({ value, icon: Icon }) => (
            <DropdownMenuRadioItem key={value} value={value}>
              <Icon /> {t(`theme.${value}`)}
            </DropdownMenuRadioItem>
          ))}
        </DropdownMenuRadioGroup>
        {theDocumentsAreServed && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
              <a href={EVENT_STORMING_OVERVIEW} target="_blank" rel="noreferrer">
                <Network /> {t('nav.architecture')}
              </a>
            </DropdownMenuItem>
          </>
        )}
        <DropdownMenuSeparator />
        <DropdownMenuItem onSelect={() => void signOut()}>
          <LogOut /> {t('nav.signOut')}
        </DropdownMenuItem>
        <DropdownMenuItem onSelect={() => void signOutEverywhere()}>
          <MonitorOff /> {t('nav.signOutEverywhere')}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
