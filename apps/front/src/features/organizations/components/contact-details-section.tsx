import { useTranslation } from 'react-i18next'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from '@/shared/forms/contact-details'
import { SectionForm } from '@/shared/forms/section-form'
import { countryName } from '@/shared/lib/countries'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { useChangeContactDetails } from '../mutations'
import type { Organization } from '../queries'

export function ContactDetailsSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const change = useChangeContactDetails()
  const contact = organization.contactDetails

  return (
    <EditableSection
      title={t('organizations.sections.contactDetails')}
      editable={Boolean(organization._links.changeContactDetails)}
      editor={(close) => (
        <SectionForm
          schema={contactDetailsSchema}
          defaultValues={toContactDetailsValues(contact)}
          fields={contactDetailsFields}
          save={(values) =>
            change.mutateAsync({ params: { path: { id: organization.id } }, body: toContactDetails(values) })
          }
          onDone={close}
        >
          {(form) => <ContactDetailsFields form={form} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.email')}>
        {contact.email && (
          <a href={`mailto:${contact.email}`} className="text-primary hover:underline">
            {contact.email}
          </a>
        )}
      </Detail>
      <Detail label={t('fields.phoneNumber')}>
        {contact.phoneNumber && (
          <a href={`tel:${contact.phoneNumber}`} className="hover:underline">
            {contact.phoneNumber}
          </a>
        )}
      </Detail>
      <Detail label={t('fields.language')}>{countryName(contact.language)}</Detail>
    </EditableSection>
  )
}
