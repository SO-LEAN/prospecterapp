import { CalendarDays } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { byDay, isSameDay, parseDay } from '../dates'
import type { CalendarExchange } from '../queries'
import { CalendarExchangeChip } from './calendar-exchange'

const longDayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })

/** The days of the period holding exchanges, one under the other, today marked: what a phone shows first. */
export function AgendaView({ items, now }: { items: CalendarExchange[]; now: Date }) {
  const { t } = useTranslation()
  const days = [...byDay(items).entries()]

  if (days.length === 0) return <EmptyState icon={CalendarDays} title={t('calendar.empty')} />
  return (
    <ol className="space-y-4">
      {days.map(([written, ofTheDay]) => {
        const day = parseDay(written)
        const today = isSameDay(day, now)

        return (
          <li key={written} className="overflow-hidden rounded-xl border bg-card">
            <h2
              className={cn(
                'border-b bg-muted/40 px-4 py-2 font-medium text-sm capitalize',
                today && 'text-primary',
              )}
            >
              {longDayFormat.format(day)}
              {today && (
                <span className="ml-2 font-normal text-muted-foreground normal-case">
                  {t('calendar.today')}
                </span>
              )}
            </h2>
            <ul className="space-y-1.5 p-2">
              {ofTheDay.map((item) => (
                <li key={item.exchange.id}>
                  <CalendarExchangeChip item={item} now={now} className="px-3 py-1.5 text-sm" />
                </li>
              ))}
            </ul>
          </li>
        )
      })}
    </ol>
  )
}
