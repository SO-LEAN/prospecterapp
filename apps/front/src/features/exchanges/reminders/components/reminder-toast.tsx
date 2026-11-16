import { AlarmClock, BellRing } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { DirectionsLink } from '@/shared/ui/directions-link'
import { CallTheInterlocutor } from '../../components/call-the-interlocutor'
import { JoinTheVideoCall } from '../../components/join-the-video-call'
import type { DayExchange } from '../../my-day/queries'

type Props = {
  item: DayExchange
  /** What the reminder says: its channel, who it is with and its hour. */
  title: string
  onOpen: () => void
  onRemindAgain: () => void
  onClose: () => void
}

/**
 * The reminder of an exchange, until it is closed: a click opens it; it offers to join, call or go there, to be
 * reminded again five minutes later.
 */
export function ReminderToast({ item, title, onOpen, onRemindAgain, onClose }: Props) {
  const { t } = useTranslation()
  const { exchange, organization } = item

  return (
    <div className="w-(--width) max-w-full space-y-3 rounded-lg border bg-popover p-4 text-popover-foreground text-sm shadow-lg">
      <button
        type="button"
        onClick={onOpen}
        className="flex w-full items-start gap-3 rounded-md text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
      >
        <BellRing aria-hidden className="mt-0.5 size-4 shrink-0 text-primary" />
        <span className="min-w-0">
          <span className="block font-medium">{title}</span>
          {organization && <span className="block truncate text-muted-foreground">{organization.name}</span>}
        </span>
      </button>
      <div className="flex flex-wrap gap-2">
        <JoinTheVideoCall exchange={exchange} />
        {exchange.channel === 'call' && <CallTheInterlocutor exchangeId={exchange.id} />}
        {exchange.channel === 'meeting' && exchange.place && <DirectionsLink to={exchange.place} />}
        <Button variant="outline" size="sm" onClick={onRemindAgain}>
          <AlarmClock aria-hidden /> {t('reminders.again')}
        </Button>
        <Button variant="ghost" size="sm" onClick={onClose}>
          {t('common.close')}
        </Button>
      </div>
    </div>
  )
}
