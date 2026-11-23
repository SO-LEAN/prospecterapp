import { CalendarDays } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { byDay, isSameDay, parseDay } from '../dates'
import type { CalendarExchange, CalendarToDo } from '../queries'
import { toDosByDay } from '../to-dos'
import { CalendarExchangeChip } from './calendar-exchange'
import { CalendarToDoChip } from './calendar-to-do'

const longDayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })

type Props = {
  items: CalendarExchange[]
  toDos: CalendarToDo[]
  /** The ids of the late exchanges and to-dos, as the API says. */
  late: ReadonlySet<string>
  now: Date
  onOpen: (item: CalendarExchange) => void
}

/**
 * The period's days that hold exchanges or to-dos, one under the other. A day's to-dos come first, and today is
 * marked. A phone shows this view first.
 */
export function AgendaView({ items, toDos, late, now, onOpen }: Props) {
  const { t } = useTranslation()
  const exchangesOf = byDay(items)
  const toDosOf = toDosByDay(toDos)
  const days = [...new Set([...exchangesOf.keys(), ...toDosOf.keys()])].sort()

  if (days.length === 0) return <EmptyState icon={CalendarDays} title={t('calendar.empty')} />
  return (
    <ol className="space-y-4">
      {days.map((written) => {
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
              {(toDosOf.get(written) ?? []).map((toDo) => (
                <li key={toDo.id}>
                  <CalendarToDoChip
                    toDo={toDo}
                    late={late.has(toDo.id)}
                    withTheLateLabel
                    className="px-3 py-1.5 text-sm"
                  />
                </li>
              ))}
              {(exchangesOf.get(written) ?? []).map((item) => (
                <li key={item.exchange.id}>
                  <CalendarExchangeChip
                    item={item}
                    now={now}
                    late={late.has(item.exchange.id)}
                    className="px-3 py-1.5 text-sm"
                    onClick={() => onOpen(item)}
                  />
                </li>
              ))}
            </ul>
          </li>
        )
      })}
    </ol>
  )
}
