import { zodResolver } from '@hookform/resolvers/zod'
import { CircleCheck, CircleSlash, Copy, KeyRound, Trash2 } from 'lucide-react'
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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shared/ui/dialog'
import { Input } from '@/shared/ui/input'
import { useConfigureGoogleClient, useRemoveGoogleClient } from '../mutations'
import type { GoogleClient } from '../queries'
import { GmailNotificationsForm } from './gmail-notifications-form'

const required = text(255).min(1, { error: () => i18n.t('validation.required') })
const clientSchema = z.object({ clientId: required, clientSecret: required })
type ClientValues = z.infer<typeof clientSchema>
const CLIENT_FIELDS = ['clientId', 'clientSecret'] as const

type Props = { client: GoogleClient }

/**
 * The OAuth client that the organization created in its Google Cloud. Its members connect through it (ADR 0125). The
 * card shows the redirect URI to declare in it and its id. It says whether the secret is set, but never shows it.
 */
export function GoogleClientCard({ client }: Props) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardHeader className="flex flex-row items-start gap-4 space-y-0">
        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-card">
          <KeyRound aria-hidden className="size-5" />
        </span>
        <div className="min-w-0 space-y-1.5">
          <CardTitle className="text-base">{t('settings.connectors.client.title')}</CardTitle>
          <CardDescription>{t('settings.connectors.client.description')}</CardDescription>
        </div>
      </CardHeader>
      <CardContent className="space-y-5 text-sm">
        <RedirectUri address={client.redirectUri} />
        <ClientState clientId={client.clientId} />
        <ClientForm key={client.clientId ?? ''} clientId={client.clientId ?? ''} />
        {client._links.remove && <RemoveButton />}
        <GmailNotificationsForm client={client} />
      </CardContent>
    </Card>
  )
}

function RedirectUri({ address }: { address: string }) {
  const { t } = useTranslation()
  const copy = async () => {
    await navigator.clipboard.writeText(address)
    toast.success(t('settings.connectors.client.copied'))
  }

  return (
    <div className="space-y-1.5">
      <p className="font-medium">{t('settings.connectors.client.redirectUri')}</p>
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

function ClientState({ clientId }: { clientId: string | null | undefined }) {
  const { t } = useTranslation()

  if (!clientId) {
    return (
      <p className="flex items-center gap-2 text-muted-foreground">
        <CircleSlash aria-hidden className="size-4 shrink-0" />
        <span>{t('settings.connectors.client.none')}</span>
      </p>
    )
  }

  return (
    <p className="flex items-center gap-2">
      <CircleCheck aria-hidden className="size-4 shrink-0 text-success" />
      <span>{t('settings.connectors.client.configured', { clientId })}</span>
    </p>
  )
}

/** The secret is typed again each time the client is saved, because the API never gives it back. */
function ClientForm({ clientId }: { clientId: string }) {
  const { t } = useTranslation()
  const configure = useConfigureGoogleClient()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<ClientValues>({
    resolver: zodResolver(clientSchema),
    defaultValues: { clientId, clientSecret: '' },
  })
  const { isSubmitting } = useFormState({ control: form.control })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await configure.mutateAsync({ body: values })
      form.reset({ clientId: values.clientId, clientSecret: '' })
      toast.success(t('settings.connectors.client.saved'))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, CLIENT_FIELDS))
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
        <FormField form={form} name="clientId" label={t('settings.connectors.client.clientId')}>
          <Input autoComplete="off" {...form.register('clientId')} />
        </FormField>
        <FormField
          form={form}
          name="clientSecret"
          label={t('settings.connectors.client.clientSecret')}
          hint={t('settings.connectors.client.secretHint')}
        >
          <Input type="password" autoComplete="off" {...form.register('clientSecret')} />
        </FormField>
      </div>
      <div className="flex justify-end">
        <Button type="submit" disabled={isSubmitting}>
          {t('settings.connectors.client.save')}
        </Button>
      </div>
    </form>
  )
}

/** Removing the client makes Google unavailable to the members, so the administrator confirms it first. */
function RemoveButton() {
  const { t } = useTranslation()
  const remove = useRemoveGoogleClient()
  const [open, setOpen] = useState(false)

  const confirm = async () => {
    await remove.mutateAsync({})
    setOpen(false)
    toast.success(t('settings.connectors.client.removed'))
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="ghost">
          <Trash2 aria-hidden /> {t('settings.connectors.client.remove.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('settings.connectors.client.remove.title')}</DialogTitle>
          <DialogDescription>{t('settings.connectors.client.remove.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={remove.isPending} onClick={() => void confirm()}>
            {t('settings.connectors.client.remove.confirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
