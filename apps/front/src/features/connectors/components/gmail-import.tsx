import { CircleAlert, Mail, MailX } from 'lucide-react'
import { useId } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { formatMoment } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import { Label } from '@/shared/ui/label'
import { Switch } from '@/shared/ui/switch'
import { useImportGmail, useStopGmailImport } from '../mutations'
import type { Connection } from '../queries'

type Props = {
  connection: Connection
  goTo?: (address: string) => void
}

/**
 * My Gmail e-mail import (ADR 0154). It is offered while the account has a Gmail topic and I may manage personal data.
 * I activate it by asking Google for access to my mailbox. I stop it without touching my calendar.
 */
export function GmailImport({ connection, goTo }: Props) {
  const { t } = useTranslation()
  const switchId = useId()
  const activate = useImportGmail(goTo)
  const stop = useStopGmailImport()
  const { importGmail, stopGmailImport } = connection._links

  if (!importGmail && !stopGmailImport) {
    return null
  }

  const turn = (on: boolean) => {
    if (on) {
      activate.mutate({})
      return
    }

    stop.mutate({}, { onSuccess: () => toast.success(t('settings.connectors.gmail.stopped')) })
  }

  return (
    <div className="space-y-2 border-t pt-4">
      <div className="flex items-center justify-between gap-4">
        <Label htmlFor={switchId} className="flex items-center gap-2">
          <Mail aria-hidden className="size-4 shrink-0" />
          {t('settings.connectors.gmail.import')}
        </Label>
        <Switch
          id={switchId}
          checked={connection.gmailImport === 'active'}
          disabled={activate.isPending || stop.isPending}
          onCheckedChange={turn}
        />
      </div>
      <GmailImportState connection={connection} />
      {connection.gmailImport === 'toReconnect' && stopGmailImport && (
        <Button variant="ghost" size="sm" disabled={stop.isPending} onClick={() => turn(false)}>
          <MailX aria-hidden /> {t('settings.connectors.gmail.stop')}
        </Button>
      )}
    </div>
  )
}

function GmailImportState({ connection }: { connection: Connection }) {
  const { t } = useTranslation()

  if (connection.gmailImport === 'toReconnect') {
    return (
      <p className="flex items-center gap-2">
        <CircleAlert aria-hidden className="size-4 shrink-0 text-warning" />
        <span>{t('settings.connectors.gmail.toReconnect')}</span>
      </p>
    )
  }

  if (connection.gmailImport === 'off') {
    return null
  }

  return (
    <p className="text-muted-foreground">
      {connection.lastGmailNotificationAt
        ? t('settings.connectors.gmail.lastNotified', {
            at: formatMoment(connection.lastGmailNotificationAt),
          })
        : t('settings.connectors.gmail.neverNotified')}
    </p>
  )
}
