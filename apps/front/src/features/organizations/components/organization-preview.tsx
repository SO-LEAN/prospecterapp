import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { ArrowRight, Mail, MapPin, Phone } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { countryName } from '@/shared/lib/countries'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { Skeleton } from '@/shared/ui/skeleton'
import { organizationQuery } from '../queries'
import { OrganizationAvatar } from './organization-avatar'

type Props = { id: number; name: string; logoUrl?: string | null }

/**
 * Quick look at an organization (map popup). Loads the full organization, which also puts it in the
 * cache: opening its page afterwards is instant.
 */
export function OrganizationPreview({ id, name, logoUrl }: Props) {
  const { t } = useTranslation()
  const { data: organization, isPending } = useQuery(organizationQuery(id))
  const address = organization?.address
  const place = address
    ? [
        address.street,
        [address.postalCode, address.city].filter(Boolean).join(' '),
        countryName(address.country),
      ]
        .filter(Boolean)
        .join(', ')
    : ''

  return (
    <div className="w-64 space-y-3 p-4">
      <div className="flex items-center gap-3">
        <OrganizationAvatar name={name} logoUrl={logoUrl} className="size-10" />
        <div className="min-w-0">
          <p className="truncate font-medium">{name}</p>
          {organization?.type && (
            <Badge variant="outline" className="mt-0.5">
              {organization.type}
            </Badge>
          )}
        </div>
      </div>

      {isPending ? (
        <div className="space-y-2">
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-2/3" />
        </div>
      ) : (
        <div className="space-y-1.5 text-sm">
          {place && <Line icon={<MapPin />}>{place}</Line>}
          {organization?.email && (
            <Line icon={<Mail />}>
              <a href={`mailto:${organization.email}`} className="text-primary hover:underline">
                {organization.email}
              </a>
            </Line>
          )}
          {organization?.phoneNumber && (
            <Line icon={<Phone />}>
              <a href={`tel:${organization.phoneNumber}`} className="hover:underline">
                {organization.phoneNumber}
              </a>
            </Line>
          )}
        </div>
      )}

      <Button size="sm" className="w-full" asChild>
        <Link to="/organizations/$organizationId" params={{ organizationId: id }}>
          {t('organizations.preview.open')} <ArrowRight />
        </Link>
      </Button>
    </div>
  )
}

function Line({ icon, children }: { icon: ReactNode; children: ReactNode }) {
  return (
    <p className="flex gap-2 text-muted-foreground [&>svg]:mt-0.5 [&>svg]:size-3.5 [&>svg]:shrink-0">
      {icon}
      <span className="min-w-0 break-words">{children}</span>
    </p>
  )
}
