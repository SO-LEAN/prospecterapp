import { ExternalLink, Mail, MapPin, Phone } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { countryName } from '@/shared/lib/countries'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { GeoMap } from '@/shared/ui/map/geo-map'
import type { Organization } from '../queries'

export function OrganizationDetails({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const address = organization.address
  const addressLines = address
    ? [
        address.street,
        [address.postalCode, address.city].filter(Boolean).join(' '),
        countryName(address.country),
      ].filter(Boolean)
    : []
  const location = organization.location

  return (
    <div className="grid gap-6 lg:grid-cols-5">
      <div className="space-y-6 lg:col-span-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">{t('organizations.detail.contact')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <Line icon={<Mail />}>
              {organization.email ? (
                <a href={`mailto:${organization.email}`} className="text-primary hover:underline">
                  {organization.email}
                </a>
              ) : (
                <Missing />
              )}
            </Line>
            <Line icon={<Phone />}>
              {organization.phoneNumber ? (
                <a href={`tel:${organization.phoneNumber}`} className="hover:underline">
                  {organization.phoneNumber}
                </a>
              ) : (
                <Missing />
              )}
            </Line>
            <Line icon={<MapPin />}>
              {addressLines.length > 0 ? (
                <address className="not-italic">
                  {addressLines.map((line) => (
                    <div key={line}>{line}</div>
                  ))}
                </address>
              ) : (
                <Missing />
              )}
            </Line>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">{t('organizations.detail.observations')}</CardTitle>
          </CardHeader>
          <CardContent className="whitespace-pre-line text-sm">
            {organization.observations || <Missing />}
          </CardContent>
        </Card>
      </div>

      <Card className="overflow-hidden lg:col-span-3">
        <CardHeader>
          <CardTitle className="text-base">{t('organizations.detail.location')}</CardTitle>
          {location && addressLines.length > 0 && (
            <CardAction>
              <Button variant="outline" size="sm" asChild>
                <a
                  href={`https://www.openstreetmap.org/directions?to=${location.latitude},${location.longitude}`}
                  target="_blank"
                  rel="noreferrer"
                >
                  {t('organizations.detail.directions')} <ExternalLink />
                </a>
              </Button>
            </CardAction>
          )}
        </CardHeader>
        <CardContent>
          {location ? (
            <GeoMap
              className="h-80"
              markers={[
                {
                  id: organization.id,
                  latitude: location.latitude,
                  longitude: location.longitude,
                  label: organization.corporateName ?? '',
                  imageUrl: organization.logo?.thumbnailUrl,
                },
              ]}
              zoom={14}
            />
          ) : (
            <p className="grid h-80 place-items-center rounded-xl border border-dashed text-muted-foreground text-sm">
              {t('organizations.detail.noLocation')}
            </p>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

function Line({ icon, children }: { icon: ReactNode; children: ReactNode }) {
  return (
    <div className="flex gap-3">
      <span className="mt-0.5 text-muted-foreground [&>svg]:size-4" aria-hidden>
        {icon}
      </span>
      <div className="min-w-0 break-words">{children}</div>
    </div>
  )
}

function Missing() {
  const { t } = useTranslation()
  return <span className="text-muted-foreground">{t('common.notProvided')}</span>
}
