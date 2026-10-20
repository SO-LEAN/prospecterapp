import { Link } from '@tanstack/react-router'
import { ChevronRight, MapPin } from 'lucide-react'
import { countryName } from '@/shared/lib/countries'
import { UnlocatedBadge } from '@/shared/ui/unlocated-badge'
import type { OrganizationSummary } from '../queries'
import { OrganizationAvatar } from './organization-avatar'

export function OrganizationCard({ organization }: { organization: OrganizationSummary }) {
  const place = [organization.postalCode, organization.city].filter(Boolean).join(' ')
  const country = countryName(organization.country)
  const unlocated = Boolean(place || country) && !organization.location

  return (
    <Link
      to="/organizations/$organizationId"
      params={{ organizationId: organization.id }}
      className="group flex items-center gap-4 rounded-xl border bg-card p-4 shadow-card transition hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
  )
}
