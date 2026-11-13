import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { LogOut, Monitor, Moon, Settings, Sun, UserRound } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { myName } from '@/features/account/me'
import { accountQuery } from '@/features/account/queries'
import { initials } from '@/shared/lib/format'
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

export function UserMenu() {
  const { t } = useTranslation()
  const { data: account, isPending } = useQuery(accountQuery())
  const signOut = useSignOut()
  const theme = usePreferences((state) => state.theme)
  const setTheme = usePreferences((state) => state.setTheme)

  if (isPending) {
    return <Skeleton className="size-9 rounded-full" />
  }

  const fullName = account ? myName(account) : ''

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        aria-label={t('nav.userMenu', { name: fullName })}
        className="flex items-center gap-2.5 rounded-full p-0.5 pr-2 outline-none transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring"
      >
        <Avatar className="size-8">
          {account?.picture && <AvatarImage src={account.picture.smallUrl} alt="" />}
          <AvatarFallback className="bg-primary/10 font-medium text-primary text-xs">
            {initials(fullName)}
          </AvatarFallback>
        </Avatar>
        <span className="hidden max-w-40 truncate font-medium text-sm sm:block">{fullName}</span>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-60">
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
        <DropdownMenuItem asChild>
          <Link to="/settings">
            <Settings /> {t('nav.settings')}
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
        <DropdownMenuSeparator />
        <DropdownMenuItem onSelect={() => void signOut()}>
          <LogOut /> {t('nav.signOut')}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
