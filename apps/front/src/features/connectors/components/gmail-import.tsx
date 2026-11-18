import { CircleAlert, Mail } from 'lucide-react'
import { useId } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { formatMoment } from '@/shared/lib/format'
import { Label } from '@/shared/ui/label'
import { Switch } from '@/shared/ui/switch'
import { useImportGmail, useStopGmailImport } from '../mutations'
import type { Connection } from '../queries'

type Props = {
  connection: Connection
  goTo?: (address: string) => void
}

/**
 * The import of my e-mails of Gmail (ADR 0154): offered while the account has a topic of Gmail and I may manage the
 * personal data, activated by asking Google the reading of my mailbox, stopped without touching my calendar.
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

  const turn = async (on: boolean) => {
    if (on) {
      activate.mutate({})
      return
    }

    await stop.mutateAsync({})
    toast.success(t('settings.connectors.gmail.stopped'))
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
          onCheckedChange={(on) => void turn(on)}
        />
      </div>
      <GmailImportState connection={connection} />
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
