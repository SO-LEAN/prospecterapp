import { useQuery } from '@tanstack/react-query'
import { Link, useRouterState } from '@tanstack/react-router'
import { ChevronRight } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { accountQuery } from '@/features/account/queries'
import { MAIN_ENTRIES } from './navigation'

type Section = { to: string; label: (typeof MAIN_ENTRIES)[number]['label'] | 'nav.settings' | 'nav.account' }

const SECTIONS: Section[] = [
  ...MAIN_ENTRIES,
  { to: '/settings', label: 'nav.settings' },
  { to: '/account', label: 'nav.account' },
]

/** Where the page shown sits: my account, then its section, a link back to it from a page inside it. */
export function Breadcrumb() {
  const { t } = useTranslation()
  const pathname = useRouterState({ select: (state) => state.location.pathname })
  const account = useQuery(accountQuery())
  const section = sectionOf(pathname)

  return (
    <nav aria-label={t('nav.breadcrumb')} className="flex min-w-0 items-center gap-1.5 text-sm">
      <span className="truncate text-muted-foreground">
        {account.data?.organization.legalIdentity.corporateName}
      </span>
      {section && (
        <>
          <ChevronRight className="size-3.5 shrink-0 text-muted-foreground" aria-hidden />
          {pathname === section.to ? (
            <span aria-current="page" className="truncate font-medium">
              {t(section.label)}
            </span>
          ) : (
            <Link to={section.to} className="truncate text-muted-foreground hover:text-foreground">
              {t(section.label)}
            </Link>
          )}
        </>
      )}
    </nav>
  )
}

function sectionOf(pathname: string): Section | undefined {
  return SECTIONS.find(({ to }) =>
    to === '/' ? pathname === to : pathname === to || pathname.startsWith(`${to}/`),
  )
}
