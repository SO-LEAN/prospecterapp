import { useQuery } from '@tanstack/react-query'
import { useNavigate } from '@tanstack/react-router'
import { useEffect, useEffectEvent, useReducer } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { blinkTheTitleWhileHidden } from '@/shared/lib/blinking-title'
import { notificationsPermission, notify } from '@/shared/lib/browser-notifications'
import { addDays, startOfDay } from '../../calendar/dates'
import { exchangesToProcessQuery } from '../../to-process/queries'
import { markShown, readReminded, remindAgain, whenRemindedInAnotherTab } from '../reminded'
import { type ReminderSettings, SNOOZE_MS } from '../schedule'
import {
  asReminder,
  nextToDoReminderAt,
  type RemindedToDo,
  reminderOfToDo,
  toDoRemindersDue,
} from '../to-do-schedule'

type Props = { settings: ReminderSettings }

/**
 * The reminders of my to-dos of the day, read among what I have to process, while the application is open: one with
 * an hour at the delay of the exchanges, one without at 9:00, or once the application opens later that day (ADR
 * 0134); a toast until it is closed, once whatever the tabs open, a notification and a blinking title in the
 * background.
 */
export function ToDoReminders({ settings }: Props) {
  const { data } = useQuery(exchangesToProcessQuery())
  const [wakings, wakeUp] = useReducer((count: number) => count + 1, 0)
  const remind = useRemind(wakeUp)
  const ofTheDay = (data?.toDos ?? []).filter(({ daysOverdue }) => daysOverdue === 0).map(({ toDo }) => toDo)
  const remindOfThoseDue = useEffectEvent(() => {
    const now = new Date()
    for (const toDo of toDoRemindersDue(ofTheDay, settings, readReminded(), now)) {
      markShown(asReminder(toDo), now)
      remind(toDo)
    }
  })
  const nextWaking = useEffectEvent(() => {
    const now = new Date()
    const next = nextToDoReminderAt(ofTheDay, settings, readReminded(), now)
    const midnight = addDays(startOfDay(now), 1)
    return next && next < midnight ? next : midnight
  })

  useEffect(() => whenRemindedInAnotherTab(wakeUp), [])

  useEffect(() => {
    remindOfThoseDue()
    const reminding = window.setTimeout(wakeUp, nextWaking().getTime() - Date.now())
    return () => window.clearTimeout(reminding)
  }, [data, settings.enabled, settings.minutesBefore, wakings])

  return null
}

/** Shows the reminder of a to-do: a toast opening its day in the calendar, and the tab in the background blinking. */
function useRemind(wakeUp: () => void) {
  const { t } = useTranslation()
  const navigate = useNavigate()

  return (toDo: RemindedToDo) => {
    const id = reminderOfToDo(toDo)
    const title = toDo.dueAt
      ? t('reminders.toDoAt', { hour: toDo.dueAt.replace(':', 'h'), text: toDo.text })
      : t('reminders.toDo', { text: toDo.text })
    const openIt = () => {
      toast.dismiss(id)
      void navigate({ to: '/calendar', search: { view: 'day', date: toDo.dueOn } })
    }

    toast.info(title, {
      id,
      duration: Number.POSITIVE_INFINITY,
      action: { label: t('reminders.open'), onClick: openIt },
      cancel: {
        label: t('reminders.again'),
        onClick: () => {
          const now = new Date()
          remindAgain(asReminder(toDo), new Date(now.getTime() + SNOOZE_MS), now)
          wakeUp()
        },
      },
    })

    if (document.visibilityState === 'hidden') {
      if (notificationsPermission() === 'granted') {
        notify(title, '', openIt)
      }
      blinkTheTitleWhileHidden(t('reminders.toDoBlinking'))
    }
  }
}
