import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { readerTimeZone } from '@/shared/lib/time-zone'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { Label } from '@/shared/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/ui/select'
import { Switch } from '@/shared/ui/switch'
import { type BookingPage, useSetWindows } from '../mutations'
import {
  copiedToWorkingDays,
  type DayWindow,
  QUARTER_HOURS,
  WEEKDAYS,
  type Weekday,
  weekOf,
  windowsOf,
} from '../weekly-windows'

type Props = { page: BookingPage }

/** The seven days, each on or off, with its window on the quarter hour. They are saved all at once in the browser's time zone. */
export function WindowsCard({ page }: Props) {
  const { t } = useTranslation()
  const [week, setWeek] = useState(() => weekOf(page.windows))
  const setWindows = useSetWindows()
  const timeZone = readerTimeZone()

  const change = (day: Weekday, window: Partial<DayWindow>) =>
    setWeek((current) => ({ ...current, [day]: { ...current[day], ...window } }))

  const save = () =>
    setWindows.mutate(
      { body: { timeZone, windows: windowsOf(week) } },
      { onSuccess: () => toast.success(t('common.saved')) },
    )

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('booking.windows.title')}</CardTitle>
        <CardDescription>{t('booking.windows.description', { timeZone })}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        <ul className="space-y-3">
          {WEEKDAYS.map((day) => (
            <DayRow key={day} day={day} window={week[day]} onChange={(window) => change(day, window)} />
          ))}
        </ul>
        <div className="flex flex-wrap gap-2">
          <Button variant="outline" onClick={() => setWeek(copiedToWorkingDays)}>
            {t('booking.windows.copyToWorkingDays')}
          </Button>
          <Button disabled={!page._links.setWindows || setWindows.isPending} onClick={save}>
            {t('booking.windows.save')}
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}

type DayProps = { day: Weekday; window: DayWindow; onChange: (window: Partial<DayWindow>) => void }

function DayRow({ day, window, onChange }: DayProps) {
  const { t } = useTranslation()
  const dayName = t(`booking.windows.weekdays.${day}`)
  const switchId = `window-${day}`

  return (
    <li className="flex flex-wrap items-center gap-3">
      <Switch
        id={switchId}
        checked={window.on}
        aria-label={t('booking.windows.day', { day: dayName })}
        onCheckedChange={(on) => onChange({ on })}
      />
      <Label htmlFor={switchId} className="w-24">
        {dayName}
      </Label>
      <TimeSelect
        label={t('booking.windows.fromOf', { day: dayName })}
        value={window.from}
        disabled={!window.on}
        onChange={(from) => onChange({ from })}
      />
      <span aria-hidden className="text-muted-foreground">
        –
      </span>
      <TimeSelect
        label={t('booking.windows.toOf', { day: dayName })}
        value={window.to}
        disabled={!window.on}
        onChange={(to) => onChange({ to })}
      />
    </li>
  )
}

type TimeProps = { label: string; value: string; disabled: boolean; onChange: (time: string) => void }

function TimeSelect({ label, value, disabled, onChange }: TimeProps) {
  return (
    <Select value={value} disabled={disabled} onValueChange={onChange}>
      <SelectTrigger className="w-24" aria-label={label}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {QUARTER_HOURS.map((time) => (
          <SelectItem key={time} value={time}>
            {time}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
