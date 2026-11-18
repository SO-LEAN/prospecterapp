import { zodResolver } from '@hookform/resolvers/zod'
import { Copy, ExternalLink, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { z } from 'zod'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { useConfigureGmailNotifications, useRemoveGmailNotifications } from '../mutations'
import type { GoogleClient } from '../queries'

/** The steps an administrator follows in the Google Cloud of the organization (ADR 0154). */
const PROCEDURE =
  'https://github.com/SO-LEAN/prospecterapp/blob/main/docs/google-client.md#2-the-import-of-gmail'

const required = text(255).min(1, { error: () => i18n.t('validation.required') })
const notificationsSchema = z.object({ topic: required, pushAccount: required })
type NotificationsValues = z.infer<typeof notificationsSchema>
const NOTIFICATIONS_FIELDS = ['topic', 'pushAccount'] as const

type Props = { client: GoogleClient }

/**
 * The topic of the Google Cloud of the organization its mailboxes notify Prospecter through, and the account of
 * service its subscription pushes them with, for the import of the e-mails of Gmail (ADR 0154); the address the
 * subscription pushes to.
 */
export function GmailNotificationsForm({ client }: Props) {
  const { t } = useTranslation()

  if (!client._links.configureGmailNotifications) {
    return null
  }

  return (
    <div className="space-y-4 border-t pt-4">
      <div className="space-y-1">
        <p className="font-medium">{t('settings.connectors.client.gmail.title')}</p>
        <a
          href={PROCEDURE}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-1 text-primary underline-offset-4 hover:underline"
        >
          {t('settings.connectors.client.gmail.procedure')} <ExternalLink aria-hidden className="size-3" />
        </a>
      </div>
      <NotificationEndpoint address={client.gmailNotificationEndpoint} />
      <NotificationsForm key={client.gmailNotifications?.topic ?? ''} client={client} />
      {client._links.removeGmailNotifications && <RemoveTopicButton />}
    </div>
  )
}

function NotificationEndpoint({ address }: { address: string }) {
  const { t } = useTranslation()
  const copy = async () => {
    await navigator.clipboard.writeText(address)
    toast.success(t('settings.connectors.client.copied'))
  }

  return (
    <div className="space-y-1.5">
      <p className="font-medium">{t('settings.connectors.client.gmail.endpoint')}</p>
      <div className="flex items-center gap-2">
        <code className="min-w-0 break-all rounded-md bg-muted px-2 py-1 text-xs">{address}</code>
        <Button
          variant="ghost"
          size="icon"
          aria-label={t('settings.connectors.client.copy')}
          onClick={() => void copy()}
        >
          <Copy aria-hidden />
        </Button>
      </div>
    </div>
  )
}

function NotificationsForm({ client }: Props) {
  const { t } = useTranslation()
  const configure = useConfigureGmailNotifications()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<NotificationsValues>({
    resolver: zodResolver(notificationsSchema),
    defaultValues: {
      topic: client.gmailNotifications?.topic ?? '',
      pushAccount: client.gmailNotifications?.pushAccount ?? '',
    },
  })
  const { isSubmitting } = useFormState({ control: form.control })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await configure.mutateAsync({ body: values })
      toast.success(t('settings.connectors.client.gmail.saved'))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, NOTIFICATIONS_FIELDS))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormField form={form} name="topic" label={t('settings.connectors.client.gmail.topic')}>
          <Input
            autoComplete="off"
            placeholder={t('settings.connectors.client.gmail.topicExample')}
            {...form.register('topic')}
          />
        </FormField>
        <FormField form={form} name="pushAccount" label={t('settings.connectors.client.gmail.pushAccount')}>
          <Input type="email" autoComplete="off" {...form.register('pushAccount')} />
        </FormField>
      </div>
      <div className="flex justify-end">
        <Button type="submit" disabled={isSubmitting}>
          {t('settings.connectors.client.gmail.save')}
        </Button>
      </div>
    </form>
  )
}

/** The import is no longer offered once its topic is removed, the watches expiring within their week. */
function RemoveTopicButton() {
  const { t } = useTranslation()
  const remove = useRemoveGmailNotifications()

  const removeIt = async () => {
    await remove.mutateAsync({})
    toast.success(t('settings.connectors.client.gmail.removed'))
  }

  return (
    <Button variant="ghost" disabled={remove.isPending} onClick={() => void removeIt()}>
      <Trash2 aria-hidden /> {t('settings.connectors.client.gmail.remove')}
    </Button>
  )
}
