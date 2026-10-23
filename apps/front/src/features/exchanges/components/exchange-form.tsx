import { zodResolver } from '@hookform/resolvers/zod'
import { ArrowDownLeft, ArrowUpRight, type LucideIcon, Paperclip, X } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import {
  type DefaultValues,
  type UseFormRegisterReturn,
  useForm,
  useFormState,
  useWatch,
} from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { formatSize } from '@/shared/lib/format'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { FileDropZone } from '@/shared/ui/file-drop-zone'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import { CHANNELS, DIRECTIONS, type ExchangeFacts } from '../queries'
import {
  type ExchangeValues,
  exchangeFields,
  exchangeSchema,
  ITSELF,
  localMoment,
  newExchange,
  personIdOf,
  toExchangeFacts,
} from '../schema'
import { CHANNEL_ICONS, INTERLOCUTOR_ICONS } from './exchange-line'
import { MadeConformField } from './made-conform-field'

/** Who may be chosen from the card of an organization: itself, first and by default, or one of its contacts. */
export type Interlocutors = { organization: string; contacts: { id: string; name: string }[] }

type Props = {
  /** The person chosen, null for the organization itself or on the card of a contact; the files to join to it. */
  onSubmit: (facts: ExchangeFacts, personId: string | null, files: File[]) => Promise<unknown>
  onCancel: () => void
  interlocutors?: Interlocutors
  /** The exchange to correct; a new one is received, now, by default. */
  defaultValues?: DefaultValues<ExchangeValues>
  /** A new exchange comes with the files received or sent with it; those of an exchange noted are in its detail. */
  withFiles?: boolean
  /** Its contact forgotten, the correction may declare it made conform. */
  mayDeclareConform?: boolean
}

const DIRECTION_ICONS = { inbound: ArrowDownLeft, outbound: ArrowUpRight }

/** A file chosen to join, the same file possibly chosen twice. */
type ChosenFile = { key: string; file: File }

/** Noted in a few seconds: received and now by default, the channel to choose, then save; or corrected. */
export function ExchangeForm({
  onSubmit,
  onCancel,
  interlocutors,
  defaultValues,
  withFiles = false,
  mayDeclareConform = false,
}: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const [files, setFiles] = useState<ChosenFile[]>([])
  const form = useForm<ExchangeValues>({
    resolver: zodResolver(exchangeSchema),
    defaultValues: defaultValues ?? newExchange(new Date()),
  })
  const { errors, isSubmitting } = useFormState({ control: form.control })
  const channel = useWatch({ control: form.control, name: 'channel' })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(
        toExchangeFacts(values),
        personIdOf(values),
        files.map(({ file }) => file),
      )
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, exchangeFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      {interlocutors && (
        <Choices legend={t('exchanges.interlocutor')} error={errors.personId?.message}>
          <Choice
            icon={INTERLOCUTOR_ICONS.organization}
            label={interlocutors.organization}
            value={ITSELF}
            {...form.register('personId')}
          />
          {interlocutors.contacts.map((contact) => (
            <Choice
              key={contact.id}
              icon={INTERLOCUTOR_ICONS.person}
              label={contact.name}
              value={contact.id}
              {...form.register('personId')}
            />
          ))}
        </Choices>
      )}

      <Choices legend={t('exchanges.channel')} error={errors.channel?.message}>
        {CHANNELS.map((value) => (
          <Choice
            key={value}
            icon={CHANNEL_ICONS[value]}
            label={t(`exchanges.channels.${value}`)}
            value={value}
            {...form.register('channel')}
          />
        ))}
      </Choices>

      <Choices legend={t('exchanges.direction')}>
        {DIRECTIONS.map((value) => (
          <Choice
            key={value}
            icon={DIRECTION_ICONS[value]}
            label={t(`exchanges.directions.${value}`)}
            value={value}
            {...form.register('direction')}
          />
        ))}
      </Choices>

      {channel === 'call' && (
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" className="size-4 accent-primary" {...form.register('missed')} />
          {t('exchanges.missedLabel')}
        </label>
      )}

      <FormField form={form} name="occurredAt" label={t('exchanges.occurredAt')}>
        <Input type="datetime-local" max={localMoment(new Date())} {...form.register('occurredAt')} />
      </FormField>
      <FormField
        form={form}
        name="text"
        label={t('exchanges.text')}
        optional={t('common.optional')}
        hint={t('exchanges.textHint')}
      >
        <Textarea rows={3} {...form.register('text')} />
      </FormField>
      {mayDeclareConform && <MadeConformField {...form.register('madeConform')} />}

      {withFiles && (
        <fieldset className="space-y-2">
          <legend className="font-medium text-sm">{t('exchanges.attachments.title')}</legend>
          {files.length > 0 && (
            <ul className="space-y-1 text-sm">
              {files.map(({ key, file }) => (
                <li key={key} className="flex items-center gap-2">
                  <Paperclip aria-hidden className="size-4 shrink-0 text-muted-foreground" />
                  <span className="min-w-0 flex-1 truncate">{file.name}</span>
                  <span className="shrink-0 text-muted-foreground text-xs">{formatSize(file.size)}</span>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={t('exchanges.attachments.remove', { name: file.name })}
                    onClick={() => setFiles(files.filter((chosen) => chosen.key !== key))}
                  >
                    <X aria-hidden />
                  </Button>
                </li>
              ))}
            </ul>
          )}
          <FileDropZone
            invitation={t('exchanges.attachments.drop')}
            choose={t('exchanges.attachments.choose')}
            hint={t('exchanges.attachments.heaviest')}
            onFiles={(added) =>
              setFiles([...files, ...added.map((file) => ({ key: crypto.randomUUID(), file }))])
            }
          />
        </fieldset>
      )}

      <div className="flex justify-end gap-2">
        <Button type="button" variant="ghost" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? t('common.saving') : t('common.save')}
        </Button>
      </div>
    </form>
  )
}

function Choices({ legend, error, children }: { legend: string; error?: string; children: ReactNode }) {
  return (
    <fieldset className="space-y-2" aria-invalid={error ? true : undefined}>
      <legend className="font-medium text-sm">{legend}</legend>
      <div className="flex flex-wrap gap-2">{children}</div>
      {error && <p className="text-destructive text-xs">{error}</p>}
    </fieldset>
  )
}

type ChoiceProps = UseFormRegisterReturn & { icon: LucideIcon; label: string; value: string }

/** A radio button looking like a button: the input, transparent over its label, is what is clicked and read. */
function Choice({ icon: Icon, label, ...radio }: ChoiceProps) {
  return (
    <label className="relative inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-primary has-checked:text-primary-foreground has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50">
      <input type="radio" className="absolute inset-0 cursor-pointer appearance-none opacity-0" {...radio} />
      <Icon aria-hidden className="size-4" />
      {label}
    </label>
  )
}
