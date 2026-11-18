import { useTranslation } from 'react-i18next'
import { FormField } from '@/shared/forms/form-field'
import { LegalIdentityFields } from '@/shared/forms/legal-identity'
import { SectionForm } from '@/shared/forms/section-form'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { Input } from '@/shared/ui/input'
import { useReviseLegalIdentity } from '../mutations'
import type { Organization } from '../queries'
import {
  organizationIdentityFields,
  organizationIdentitySchema,
  toOrganizationIdentity,
  toOrganizationIdentityValues,
} from '../schema'
import { establishmentPage, spacedSiret } from '../siret'
import { EstablishmentSearch } from './establishment-search'

export function LegalIdentitySection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const revise = useReviseLegalIdentity()
  const identity = organization.legalIdentity

  return (
    <EditableSection
      title={t('organizations.sections.legalIdentity')}
      editable={Boolean(organization._links.reviseLegalIdentity)}
      editor={(close) => (
        <SectionForm
          schema={organizationIdentitySchema}
          defaultValues={toOrganizationIdentityValues(identity)}
          fields={organizationIdentityFields}
          save={(values) =>
            revise.mutateAsync({
              params: { path: { id: organization.id } },
              body: toOrganizationIdentity(values),
            })
          }
          onDone={close}
        >
          {(form) => (
            <>
              <LegalIdentityFields form={form} />
              <FormField form={form} name="siret" label={t('fields.siret')} optional={t('common.optional')}>
                <Input {...form.register('siret')} inputMode="numeric" />
              </FormField>
              {organization._links.searchEstablishments && (
                <div className="flex items-end">
                  <EstablishmentSearch
                    organizationId={organization.id}
                    onChoose={(siret) => form.setValue('siret', siret, { shouldDirty: true })}
                  />
                </div>
              )}
            </>
          )}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.corporateName')}>{identity.corporateName}</Detail>
      <Detail label={t('fields.form')}>{identity.form}</Detail>
      <Detail label={t('fields.type')}>{identity.type}</Detail>
      <Detail label={t('fields.siret')}>
        {identity.siret && (
          <a
            href={establishmentPage(identity.siret)}
            target="_blank"
            rel="noreferrer"
            className="underline-offset-4 hover:underline"
          >
            {spacedSiret(identity.siret)}
          </a>
        )}
      </Detail>
    </EditableSection>
  )
}
