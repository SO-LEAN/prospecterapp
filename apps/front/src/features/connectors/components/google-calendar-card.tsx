import { CircleAlert, CircleCheck, CircleSlash, CloudOff, Link2, RefreshCw, Unlink } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { formatMoment } from '@/shared/lib/format'
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
import { GoogleLogo } from '@/shared/ui/google-logo'
import { useConnectGoogle, useDisconnectGoogle, useRetryFailedPushes } from '../mutations'
import type { Connection } from '../queries'
import { GmailImport } from './gmail-import'

type Props = {
  connection: Connection | undefined
  /** False while no client of Prospecter at Google serves the account: Google is neither connected nor reconnected. */
  offered: boolean
  /** Google did not answer the check: the state of the connection is the one last seen. */
  unchecked?: boolean
  goTo?: (address: string) => void
}

/** Google Calendar: connected, to reconnect once Google refused its access, or to connect; unavailable unless offered. */
export function GoogleCalendarCard({ connection, offered, unchecked = false, goTo }: Props) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardHeader className="flex flex-row items-start gap-4 space-y-0">
        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-card">
          <GoogleLogo className="size-6" />
        </span>
        <div className="min-w-0 space-y-1.5">
          <CardTitle className="text-base">{t('settings.connectors.google.title')}</CardTitle>
          <CardDescription>{t('settings.connectors.google.description')}</CardDescription>
        </div>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {connection && <ConnectionState connection={connection} />}
        {connection && <ExchangesSynced connection={connection} />}
        {connection && unchecked && <Unchecked />}
        {!offered && <Unavailable />}
        <div className="flex flex-wrap gap-2">
          {offered && <ConnectButton connection={connection} goTo={goTo} />}
          {connection && <DisconnectButton />}
        </div>
        {connection && <GmailImport connection={connection} goTo={goTo} />}
      </CardContent>
    </Card>
  )
}

function ConnectionState({ connection }: { connection: Connection }) {
  const { t } = useTranslation()

  if (connection.state === 'toReconnect') {
    return (
      <p className="flex items-center gap-2">
        <CircleAlert aria-hidden className="size-4 shrink-0 text-warning" />
        <span>{t('settings.connectors.toReconnect', { account: connection.account })}</span>
      </p>
    )
  }

  return (
    <p className="flex items-center gap-2">
      <CircleCheck aria-hidden className="size-4 shrink-0 text-success" />
      <span>
        {t('settings.connectors.connected', {
          account: connection.account,
          since: formatMoment(connection.connectedAt),
        })}
      </span>
    </p>
  )
}

/** How many of my exchanges to come are in Google, and failed, pushed again all at once (ADR 0133). */
function ExchangesSynced({ connection }: { connection: Connection }) {
  const { t } = useTranslation()
  const retry = useRetryFailedPushes()
  const synced = t('settings.connectors.synced', { count: connection.synced })

  return (
    <div className="flex flex-wrap items-center gap-2">
      <span>
        {connection.failed > 0
          ? t('settings.connectors.syncedAndFailed', { synced, count: connection.failed })
          : synced}
      </span>
      {connection._links.retryFailed && (
        <Button variant="outline" size="sm" disabled={retry.isPending} onClick={() => retry.mutate({})}>
          <RefreshCw aria-hidden /> {t('settings.connectors.retryFailed')}
        </Button>
      )}
    </div>
  )
}

function Unchecked() {
  const { t } = useTranslation()

  return (
    <p className="flex items-center gap-2 text-muted-foreground">
      <CloudOff aria-hidden className="size-4 shrink-0" />
      <span>{t('settings.connectors.unchecked')}</span>
    </p>
  )
}

function Unavailable() {
  const { t } = useTranslation()

  return (
    <p className="flex items-center gap-2 text-muted-foreground">
      <CircleSlash aria-hidden className="size-4 shrink-0" />
      <span>{t('settings.connectors.unavailable')}</span>
    </p>
  )
}

function ConnectButton({ connection, goTo }: Pick<Props, 'connection' | 'goTo'>) {
  const { t } = useTranslation()
  const connect = useConnectGoogle(goTo)
  const ask = () => connect.mutate({})

  if (!connection) {
    return (
      <Button disabled={connect.isPending} onClick={ask}>
        <Link2 aria-hidden /> {t('settings.connectors.connect')}
      </Button>
    )
  }

  return (
    <Button
      variant={connection.state === 'toReconnect' ? 'default' : 'outline'}
      disabled={connect.isPending}
      onClick={ask}
    >
      <RefreshCw aria-hidden /> {t('settings.connectors.reconnect')}
    </Button>
  )
}

/** Disconnecting revokes the access at Google: it is confirmed first. */
function DisconnectButton() {
  const { t } = useTranslation()
  const disconnect = useDisconnectGoogle()
  const [open, setOpen] = useState(false)

  const confirm = async () => {
    await disconnect.mutateAsync({})
    setOpen(false)
    toast.success(t('settings.connectors.disconnected'))
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="ghost">
          <Unlink aria-hidden /> {t('settings.connectors.disconnect.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('settings.connectors.disconnect.title')}</DialogTitle>
          <DialogDescription>{t('settings.connectors.disconnect.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={disconnect.isPending} onClick={() => void confirm()}>
            {t('settings.connectors.disconnect.confirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
