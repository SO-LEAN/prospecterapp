import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { FormField } from '@/shared/forms/form-field'
import { SectionForm } from '@/shared/forms/section-form'
import { orNull, text } from '@/shared/forms/text'
import { EditableSection } from '@/shared/ui/editable-section'
import { Textarea } from '@/shared/ui/textarea'
import { useWriteObservations } from '../mutations'
import type { Organization } from '../queries'

const observationsSchema = z.object({ observations: text(255) })

export function ObservationsSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const write = useWriteObservations()

  return (
    <EditableSection
      title={t('organizations.sections.observations')}
      editable={Boolean(organization._links.writeObservations)}
      editor={(close) => (
        <SectionForm
          schema={observationsSchema}
          defaultValues={{ observations: organization.observations ?? '' }}
          fields={['observations']}
          save={(values) =>
            write.mutateAsync({
              params: { path: { id: organization.id } },
              body: { observations: orNull(values.observations) },
            })
          }
          onDone={close}
        >
          {(form) => (
            <FormField
              form={form}
              name={'observations'}
              label={t('fields.observations')}
              className="sm:col-span-2"
            >
              <Textarea rows={5} {...form.register('observations')} />
            </FormField>
          )}
        </SectionForm>
      )}
    >
      <p className="whitespace-pre-line">
        {organization.observations || (
          <span className="text-muted-foreground">{t('common.notProvided')}</span>
        )}
      </p>
    </EditableSection>
  )
}
