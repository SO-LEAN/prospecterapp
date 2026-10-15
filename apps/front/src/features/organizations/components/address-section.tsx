import { ExternalLink } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import {
  AddressFields,
  addressFields,
  addressSchema,
  toAddress,
  toAddressValues,
} from '@/shared/forms/address'
import { SectionForm } from '@/shared/forms/section-form'
import { countryName } from '@/shared/lib/countries'
import { Button } from '@/shared/ui/button'
import { EditableSection } from '@/shared/ui/editable-section'
import { GeoMap } from '@/shared/ui/map/geo-map'
import { useRelocate } from '../mutations'
import type { Organization } from '../queries'

export function AddressSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const relocate = useRelocate()
  const { address, location } = organization
  const lines = address
    ? [
        address.street,
        [address.postalCode, address.city].filter(Boolean).join(' '),
        countryName(address.country),
      ].filter(Boolean)
    : []

  return (
    <EditableSection
      title={t('organizations.sections.address')}
      editable={Boolean(organization._links.relocate)}
      editor={(close) => (
        <SectionForm
          schema={addressSchema}
          defaultValues={toAddressValues(address)}
          fields={addressFields}
          save={(values) =>
            relocate.mutateAsync({ params: { path: { id: organization.id } }, body: toAddress(values) })
          }
          onDone={close}
        >
          {(form) => <AddressFields form={form} />}
        </SectionForm>
      )}
    >
      <div className="space-y-4">
        {lines.length > 0 ? (
          <address className="not-italic">
            {lines.map((line) => (
              <div key={line}>{line}</div>
            ))}
          </address>
        ) : (
          <p className="text-muted-foreground">{t('common.notProvided')}</p>
        )}
        {location ? (
          <>
            <GeoMap
              className="h-72"
              markers={[
                {
                  id: organization.id,
                  ...location,
                  label: organization.name,
                  imageUrl: organization.logo?.smallUrl,
                },
              ]}
              zoom={14}
            />
            <Button variant="outline" size="sm" asChild>
              <a
                href={`https://www.openstreetmap.org/directions?to=${location.latitude},${location.longitude}`}
                target="_blank"
                rel="noreferrer"
              >
                {t('organizations.address.directions')} <ExternalLink />
              </a>
            </Button>
          </>
        ) : (
          <p className="grid h-40 place-items-center rounded-xl border border-dashed text-muted-foreground">
            {t('organizations.address.noLocation')}
          </p>
        )}
      </div>
    </EditableSection>
  )
}
