import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from '@/shared/forms/contact-details'
import { FormField } from '@/shared/forms/form-field'
import {
  PersonNameFields,
  personNameFields,
  personNameSchema,
  toPersonName,
  toPersonNameValues,
} from '@/shared/forms/person-name'
import { SectionForm } from '@/shared/forms/section-form'
import { orNull } from '@/shared/forms/text'
import { countryName } from '@/shared/lib/countries'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { Input } from '@/shared/ui/input'
import { useChangeContactDetailsOfContact, useChangeJobTitle, useRenameContact } from '../mutations'
import { type Contact, organizationsOfContactQuery } from '../queries'
import { jobTitleSchema } from '../schema'

export function NameSection({ contact }: { contact: Contact }) {
  const { t } = useTranslation()
  const rename = useRenameContact()

  return (
    <EditableSection
      title={t('contacts.sections.name')}
      editable={Boolean(contact._links.rename)}
      editor={(close) => (
        <SectionForm
          schema={personNameSchema}
          defaultValues={toPersonNameValues(contact.name)}
          fields={personNameFields}
          save={(values) =>
            rename.mutateAsync({ params: { path: { id: contact.id } }, body: toPersonName(values) })
          }
          onDone={close}
        >
          {(form) => <PersonNameFields form={form} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.firstName')}>{contact.name.firstName}</Detail>
      <Detail label={t('fields.lastName')}>{contact.name.lastName}</Detail>
    </EditableSection>
  )
}

export function JobTitleSection({ contact }: { contact: Contact }) {
  const { t } = useTranslation()
  const change = useChangeJobTitle()

  return (
    <EditableSection
      title={t('contacts.sections.jobTitle')}
      editable={Boolean(contact._links.changeJobTitle)}
      editor={(close) => (
        <SectionForm
          schema={jobTitleSchema}
          defaultValues={{ jobTitle: contact.jobTitle ?? '' }}
          fields={['jobTitle']}
          save={(values) =>
            change.mutateAsync({
              params: { path: { id: contact.id } },
              body: { jobTitle: orNull(values.jobTitle) },
            })
          }
          onDone={close}
        >
          {(form) => (
            <FormField form={form} name="jobTitle" label={t('fields.jobTitle')}>
              <Input autoComplete="organization-title" {...form.register('jobTitle')} />
            </FormField>
          )}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.jobTitle')}>{contact.jobTitle}</Detail>
    </EditableSection>
  )
}

export function ContactDetailsSection({ contact }: { contact: Contact }) {
  const { t } = useTranslation()
  const change = useChangeContactDetailsOfContact()
  const details = contact.contactDetails

  return (
    <EditableSection
      title={t('contacts.sections.contactDetails')}
      editable={Boolean(contact._links.changeContactDetails)}
      editor={(close) => (
        <SectionForm
          schema={contactDetailsSchema}
          defaultValues={toContactDetailsValues(details)}
          fields={contactDetailsFields}
          save={(values) =>
            change.mutateAsync({ params: { path: { id: contact.id } }, body: toContactDetails(values) })
          }
          onDone={close}
        >
          {(form) => <ContactDetailsFields form={form} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.email')}>
        {details.email &&
          (contact.masked ? (
            details.email
          ) : (
            <a href={`mailto:${details.email}`} className="text-primary hover:underline">
              {details.email}
            </a>
          ))}
      </Detail>
      <Detail label={t('fields.phoneNumber')}>{details.phoneNumber}</Detail>
      <Detail label={t('fields.language')}>{countryName(details.language)}</Detail>
    </EditableSection>
  )
}

/** The organizations the contact works for; a contact is attached or removed from the organization. */
export function OrganizationsSection({ contact }: { contact: Contact }) {
  const { t } = useTranslation()
  const organizations = useQuery(organizationsOfContactQuery(contact.id))

  return (
    <EditableSection title={t('contacts.sections.organizations')} editable={false} editor={() => null}>
      <ul className="space-y-1">
        {organizations.data?.items.map((organization) => (
          <li key={organization.id}>
            <Link
              to="/organizations/$organizationId"
              params={{ organizationId: organization.id }}
              className="text-primary hover:underline"
            >
              {organization.name}
            </Link>
          </li>
        ))}
      </ul>
    </EditableSection>
  )
}
