import { Link } from '@tanstack/react-router'
import { ChevronRight, CopyCheck, MapPin } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { countryName } from '@/shared/lib/countries'
import { UnlocatedBadge } from '@/shared/ui/unlocated-badge'
import type { OrganizationSummary } from '../queries'
import { OrganizationAvatar } from './organization-avatar'

/**
 * The whole card opens the organization: its link stretches over it. The organizations it may duplicate have their own
 * links beside it, because a link never nests another.
 */
export function OrganizationCard({ organization }: { organization: OrganizationSummary }) {
  const place = [organization.postalCode, organization.city].filter(Boolean).join(' ')
  const country = countryName(organization.country)
  const unlocated = Boolean(place || country) && !organization.location

  return (
    <div className="group relative rounded-xl border bg-card shadow-card transition focus-within:ring-2 focus-within:ring-ring hover:border-primary/40 hover:shadow-md">
      <Link
        to="/organizations/$organizationId"
        params={{ organizationId: organization.id }}
        className="flex items-center gap-4 p-4 after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none"
      >
        <OrganizationAvatar name={organization.name} logoUrl={organization.logo?.smallUrl} />
        <div className="min-w-0 flex-1">
          <p className="truncate font-medium">{organization.name}</p>
          {(place || country) && (
            <p className="flex items-center gap-1 truncate text-muted-foreground text-sm">
              <MapPin className="size-3.5 shrink-0" aria-hidden />
              {[place, country].filter(Boolean).join(', ')}
            </p>
          )}
          {unlocated && <UnlocatedBadge className="mt-1" />}
        </div>
        <ChevronRight
          className="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-foreground"
          aria-hidden
        />
      </Link>
      <PossibleDuplicatesOf organization={organization} />
    </div>
  )
}

function PossibleDuplicatesOf({ organization }: { organization: OrganizationSummary }) {
  const { t } = useTranslation()

  if (organization.possibleDuplicates.length === 0) {
    return null
  }

  return (
    <p className="-mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 px-4 pb-4 pl-18 text-sm">
      <CopyCheck className="size-3.5 shrink-0 text-warning" aria-hidden />
      <span className="text-muted-foreground">{t('organizations.possibleDuplicates.of')}</span>
      {organization.possibleDuplicates.map((duplicate) => (
        <Link
          key={duplicate.id}
          to="/organizations/$organizationId"
          params={{ organizationId: duplicate.id }}
          className="relative font-medium underline-offset-4 hover:underline"
        >
          {duplicate.name}
        </Link>
      ))}
    </p>
  )
}
