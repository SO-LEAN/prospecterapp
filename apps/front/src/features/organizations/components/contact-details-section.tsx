import { useTranslation } from 'react-i18next'
import { SectionForm } from '@/shared/forms/section-form'
import { countryName } from '@/shared/lib/countries'
import { telUrl } from '@/shared/lib/format'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { useChangeContactDetails } from '../mutations'
import {
  OrganizationContactDetailsFields,
  organizationContactDetailsFields,
  organizationContactDetailsSchema,
  toOrganizationContactDetails,
  toOrganizationContactDetailsValues,
} from '../organization-contact-details'
import type { Organization } from '../queries'
import { LinkedInPage, Website } from './organization-on-the-web'

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
          schema={organizationContactDetailsSchema}
          defaultValues={toOrganizationContactDetailsValues(contact)}
          fields={organizationContactDetailsFields}
          save={(values) =>
            change.mutateAsync({
              params: { path: { id: organization.id } },
              body: toOrganizationContactDetails(values),
            })
          }
          onDone={close}
        >
          {(form) => <OrganizationContactDetailsFields form={form} />}
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
          <a href={telUrl(contact.phoneNumber)} className="hover:underline">
            {contact.phoneNumber}
          </a>
        )}
      </Detail>
      <Detail label={t('fields.language')}>{countryName(contact.language)}</Detail>
      <Detail label={t('fields.linkedInPage')}>
        {contact.linkedInPage && <LinkedInPage page={contact.linkedInPage} />}
      </Detail>
      <Detail label={t('fields.website')}>{contact.website && <Website site={contact.website} />}</Detail>
    </EditableSection>
  )
}
