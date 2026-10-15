import { useTranslation } from 'react-i18next'
import {
  LegalIdentityFields,
  legalIdentityFields,
  legalIdentitySchema,
  toLegalIdentity,
  toLegalIdentityValues,
} from '@/shared/forms/legal-identity'
import { SectionForm } from '@/shared/forms/section-form'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { useReviseLegalIdentity } from '../mutations'
import type { Organization } from '../queries'

export function LegalIdentitySection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const revise = useReviseLegalIdentity()
  const identity = organization.legalIdentity

  return (
    <EditableSection
      title={t('organizations.sections.legalIdentity')}
      editor={(close) => (
        <SectionForm
          schema={legalIdentitySchema}
          defaultValues={toLegalIdentityValues(identity)}
          fields={legalIdentityFields}
          save={(values) =>
            revise.mutateAsync({ params: { path: { id: organization.id } }, body: toLegalIdentity(values) })
          }
          onDone={close}
        >
          {(form) => <LegalIdentityFields form={form} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.corporateName')}>{identity.corporateName}</Detail>
      <Detail label={t('fields.form')}>{identity.form}</Detail>
      <Detail label={t('fields.type')}>{identity.type}</Detail>
    </EditableSection>
  )
}
