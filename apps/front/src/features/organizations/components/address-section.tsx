import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import {
  AddressFields,
  addressFields,
  addressSchema,
  toAddress,
  toAddressValues,
} from '@/shared/forms/address'
import { SectionForm } from '@/shared/forms/section-form'
import { addressLines } from '@/shared/lib/address'
import { EditableSection } from '@/shared/ui/editable-section'
import { GeoMap } from '@/shared/ui/map/geo-map'
import { useRelocate } from '../mutations'
import type { Organization } from '../queries'
import { DirectionsLink } from './directions-link'

type Location = components['schemas']['LocationResource']

/** `start`: where the routes to the organization start from, the place of my organization when known. */
export function AddressSection({
  organization,
  start,
}: {
  organization: Organization
  start?: Location | null
}) {
  const { t } = useTranslation()
  const relocate = useRelocate()
  const { address, location } = organization
  const lines = addressLines(address)

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
            <DirectionsLink to={location} start={start} />
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
