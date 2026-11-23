import type { TFunction } from 'i18next'
import { BellRing } from 'lucide-react'
import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { askToNotify, notificationsPermission, notify } from '@/shared/lib/browser-notifications'
import { Alert, AlertDescription, AlertTitle } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { Field } from '@/shared/ui/form/field'
import { Label } from '@/shared/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/ui/select'
import { Switch } from '@/shared/ui/switch'
import { type Reminders, useChangeMyReminders } from '../mutations'

/** The delays that the API offers, by their wording's key. */
const DELAYS = [
  { minutes: 0, label: 'onTime' },
  { minutes: 5, label: 'fiveMinutes' },
  { minutes: 10, label: 'tenMinutes' },
  { minutes: 15, label: 'fifteenMinutes' },
  { minutes: 30, label: 'halfAnHour' },
  { minutes: 60, label: 'anHour' },
] as const satisfies { minutes: Minutes; label: string }[]

type Minutes = Reminders['minutesBefore']

type Props = { reminders: Reminders }

export function RemindersSection({ reminders }: Props) {
  const { t } = useTranslation()
  const switchId = useId()
  const changeMyReminders = useChangeMyReminders()
  const changeable = Boolean(reminders._links.changeMyReminders) && !changeMyReminders.isPending
  const change = (next: Pick<Reminders, 'enabled' | 'minutesBefore'>) =>
    changeMyReminders.mutate({ body: next })

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('settings.reminders.title')}</CardTitle>
        <CardDescription>{t('settings.reminders.description')}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6 text-sm">
        <div className="flex items-center justify-between gap-4">
          <Label htmlFor={switchId}>{t('settings.reminders.enabled')}</Label>
          <Switch
            id={switchId}
            checked={reminders.enabled}
            disabled={!changeable}
            onCheckedChange={(enabled) => change({ enabled, minutesBefore: reminders.minutesBefore })}
          />
        </div>
        <Field label={t('settings.reminders.delay')} className="max-w-60">
          <ReminderDelaySelect
            minutes={reminders.minutesBefore}
            disabled={!changeable || !reminders.enabled}
            onChange={(minutesBefore) => change({ enabled: reminders.enabled, minutesBefore })}
          />
        </Field>
        <TryAReminder minutesBefore={reminders.minutesBefore} />
      </CardContent>
    </Card>
  )
}

type DelayProps = {
  id?: string
  minutes: Minutes
  disabled: boolean
  onChange: (minutes: Minutes) => void
  'aria-describedby'?: string
}

function ReminderDelaySelect({ minutes, disabled, onChange, ...trigger }: DelayProps) {
  const { t } = useTranslation()

  return (
    <Select value={String(minutes)} disabled={disabled} onValueChange={(next) => chosen(next, onChange)}>
      <SelectTrigger className="w-full" {...trigger}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {DELAYS.map((delay) => (
          <SelectItem key={delay.minutes} value={String(delay.minutes)}>
            {t(`settings.reminders.delays.${delay.label}`)}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}

/** The delay of a list item. An item is always one of the offered delays. */
function chosen(value: string, onChange: (minutes: Minutes) => void) {
  const delay = DELAYS.find((offered) => String(offered.minutes) === value)
  if (delay) {
    onChange(delay.minutes)
  }
}

/**
 * Shows an example reminder in the application, and asks once whether this browser may notify the member. When the
 * member refused, it says how to allow it again. Only the browser can allow it again.
 */
function TryAReminder({ minutesBefore }: { minutesBefore: Minutes }) {
  const { t } = useTranslation()
  const [permission, setPermission] = useState(notificationsPermission)

  const tryAReminder = async () => {
    const title = t('settings.reminders.example.title')
    const body = exampleBodyOf(t, minutesBefore)
    toast.info(title, { description: body })
    const answer = await askToNotify()
    setPermission(answer)
    if (answer === 'granted') {
      notify(title, body)
    }
  }

  return (
    <div className="space-y-4">
      <Button variant="outline" onClick={() => void tryAReminder()}>
        <BellRing aria-hidden /> {t('settings.reminders.try')}
      </Button>
      {permission === 'denied' && (
        <Alert>
          <AlertTitle>{t('settings.reminders.notificationsRefused.title')}</AlertTitle>
          <AlertDescription>{t('settings.reminders.notificationsRefused.howToAllow')}</AlertDescription>
        </Alert>
      )}
    </div>
  )
}

const AN_HOUR = 60

/** The example announces the exchange as a real reminder would, `minutesBefore` the exchange. */
function exampleBodyOf(t: TFunction, minutesBefore: Minutes): string {
  if (minutesBefore === 0) {
    return t('settings.reminders.example.body.now')
  }
  if (minutesBefore === AN_HOUR) {
    return t('settings.reminders.example.body.inAnHour')
  }
  return t('settings.reminders.example.body.inMinutes', { minutes: minutesBefore })
}
