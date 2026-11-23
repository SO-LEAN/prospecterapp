import { useNavigate } from '@tanstack/react-router'
import type { TFunction } from 'i18next'
import { useEffect, useEffectEvent, useReducer } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { blinkTheTitleWhileHidden } from '@/shared/lib/blinking-title'
import { notificationsPermission, notify } from '@/shared/lib/browser-notifications'
import { formatHour } from '@/shared/lib/format'
import { addDays, startOfDay } from '../../calendar/dates'
import type { DayExchange } from '../../my-day/queries'
import { markShown, readReminded, remindAgain, whenRemindedInAnotherTab } from '../reminded'
import { nextReminderAt, type ReminderSettings, reminderOf, remindersDue, SNOOZE_MS } from '../schedule'
import { useMyDayToRemind } from '../use-my-day-to-remind'
import { ReminderToast } from './reminder-toast'

/** A background tab lets a foreground tab show a reminder due in both. So the member sees it where they are. */
const HIDDEN_TAB_LETS_THE_OTHERS_MS = 2_000

const MINUTE_MS = 60_000

type Props = { settings: ReminderSettings }

/**
 * The reminders of the exchanges I planned, `minutesBefore` each one, while the application is open. Each one shows
 * a toast until it is closed, once, however many tabs are open. In the background, it also shows a browser
 * notification and a blinking title.
 */
export function ExchangeReminders({ settings }: Props) {
  const today = startOfDay(new Date())
  const myDay = useMyDayToRemind(today)
  const [wakings, wakeUp] = useReducer((count: number) => count + 1, 0)
  const remind = useRemind(wakeUp)
  const remindOfThoseDue = useEffectEvent(() => {
    const now = new Date()
    for (const item of remindersDue(toComeOf(myDay), settings, readReminded(), now)) {
      markShown(item.exchange, now)
      remind(item)
    }
  })
  const nextWaking = useEffectEvent(() => {
    const now = new Date()
    return nextWakingOf(nextReminderAt(toComeOf(myDay), settings, readReminded(), now), now)
  })

  useEffect(() => whenRemindedInAnotherTab(wakeUp), [])

  useEffect(() => {
    let reminding: number | undefined
    const remindThenWait = () => {
      remindOfThoseDue()
      reminding = window.setTimeout(wakeUp, nextWaking().getTime() - Date.now())
    }
    if (document.visibilityState === 'hidden') {
      reminding = window.setTimeout(remindThenWait, HIDDEN_TAB_LETS_THE_OTHERS_MS)
    } else {
      remindThenWait()
    }
    return () => window.clearTimeout(reminding)
  }, [myDay, settings.enabled, settings.minutesBefore, wakings])

  return null
}

/** Today's exchanges and the next ones. After midnight, the next day's first exchanges are among them. */
const toComeOf = (myDay: { today?: DayExchange[]; next?: DayExchange[] } | undefined) => [
  ...(myDay?.today ?? []),
  ...(myDay?.next ?? []),
]

/** Wakes up at the next reminder, or at midnight at the latest, when my day is read for the new day. */
function nextWakingOf(nextReminder: Date | null, now: Date): Date {
  const midnight = addDays(startOfDay(now), 1)
  return nextReminder && nextReminder < midnight ? nextReminder : midnight
}

/** Shows an exchange's reminder: a toast, and it draws the member's attention to a background tab. */
function useRemind(wakeUp: () => void) {
  const { t } = useTranslation()
  const navigate = useNavigate()

  const open = ({ exchange, interlocutor }: DayExchange) => {
    const search = { exchange: exchange.id }
    void (interlocutor.kind === 'person'
      ? navigate({ to: '/contacts/$contactId', params: { contactId: interlocutor.id }, search })
      : navigate({
          to: '/organizations/$organizationId',
          params: { organizationId: interlocutor.id },
          search,
        }))
  }

  return (item: DayExchange) => {
    const { exchange, interlocutor, organization } = item
    const id = reminderOf(exchange)
    const channel = t(`exchanges.channels.${exchange.channel}`)
    const title = t('reminders.title', {
      channel,
      interlocutor: interlocutor.name ?? t('calendar.nobody'),
      hour: formatHour(exchange.occurredAt),
    })
    const openIt = () => {
      toast.dismiss(id)
      open(item)
    }

    toast.custom(
      () => (
        <ReminderToast
          item={item}
          title={title}
          onOpen={openIt}
          onRemindAgain={() => {
            toast.dismiss(id)
            const now = new Date()
            remindAgain(exchange, new Date(now.getTime() + SNOOZE_MS), now)
            wakeUp()
          }}
          onClose={() => toast.dismiss(id)}
        />
      ),
      { id, duration: Number.POSITIVE_INFINITY },
    )

    if (document.visibilityState === 'hidden') {
      if (notificationsPermission() === 'granted') {
        notify(title, organization?.name ?? '', openIt)
      }
      blinkTheTitleWhileHidden(blinkingOf(t, channel, exchange.occurredAt))
    }
  }
}

function blinkingOf(t: TFunction, channel: string, occurredAt: string): string {
  const minutes = Math.ceil((new Date(occurredAt).getTime() - Date.now()) / MINUTE_MS)
  return minutes > 0 ? t('reminders.blinking', { channel, minutes }) : t('reminders.blinkingNow', { channel })
}
