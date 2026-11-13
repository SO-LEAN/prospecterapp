import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { dayOf, daysOf, isSameDay, neighbourOf, periodOf } from '../dates'

const monthFormat = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' })
const weekdayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'narrow' })
const longDayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })

/** A small month to jump to a day: its own months browsed apart from the calendar, the day shown and today marked. */
export function MiniMonth({
  selected,
  today,
  onPick,
}: {
  selected: Date
  today: Date
  onPick: (day: Date) => void
}) {
  const { t } = useTranslation()
  const [month, setMonth] = useState(selected)
  const days = daysOf(periodOf('month', month))

  return (
    <div className="w-64 space-y-2">
      <div className="flex items-center justify-between">
        <Button variant="ghost" size="icon-sm" onClick={() => setMonth(neighbourOf('month', month, -1))}>
          <ChevronLeft aria-hidden />
          <span className="sr-only">{t('calendar.previous')}</span>
        </Button>
        <span className="font-medium text-sm capitalize">{monthFormat.format(month)}</span>
        <Button variant="ghost" size="icon-sm" onClick={() => setMonth(neighbourOf('month', month, 1))}>
          <ChevronRight aria-hidden />
          <span className="sr-only">{t('calendar.next')}</span>
        </Button>
      </div>
      <div className="grid grid-cols-7 gap-0.5 text-center text-xs">
        {days.slice(0, 7).map((day) => (
          <span key={`weekday-${dayOf(day)}`} aria-hidden className="py-1 text-muted-foreground capitalize">
            {weekdayFormat.format(day)}
          </span>
        ))}
        {days.map((day) => (
          <button
            key={dayOf(day)}
            type="button"
            onClick={() => onPick(day)}
            aria-label={longDayFormat.format(day)}
            aria-pressed={isSameDay(day, selected)}
            className={cn(
              'grid size-8 place-items-center rounded-md tabular-nums hover:bg-accent',
              day.getMonth() !== month.getMonth() && 'text-muted-foreground',
              isSameDay(day, today) && 'font-semibold text-primary',
              isSameDay(day, selected) && 'bg-primary text-primary-foreground hover:bg-primary/90',
            )}
          >
            {day.getDate()}
          </button>
        ))}
      </div>
    </div>
  )
}
