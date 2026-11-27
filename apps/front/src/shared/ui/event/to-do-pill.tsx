import { type LucideIcon, Square, SquareCheck, TriangleAlert } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { LATE_EDGE } from './family'

type State = 'open' | 'done' | 'late'

const LOOKS: Record<'open' | 'done', string> = {
  open: 'border border-event-todo text-foreground',
  done: 'bg-event-todo-soft text-muted-foreground line-through',
}

const ICONS: Record<State, LucideIcon> = {
  open: Square,
  done: SquareCheck,
  late: TriangleAlert,
}

/**
 * A to-do in the calendar: a box with an amber edge while it is to do, filled and struck through once done. A late
 * one has a red edge, and says it is late.
 */
export function ToDoPill({
  done,
  late = false,
  className,
  children,
}: {
  done: boolean
  late?: boolean
  className?: string
  children?: ReactNode
}) {
  const { t } = useTranslation()
  const look = done ? 'done' : 'open'
  const state: State = late ? 'late' : look
  const Icon = ICONS[state]

  return (
    <span
      data-slot="to-do-pill"
      className={cn(
        'flex min-w-0 items-center gap-1 rounded-md px-1.5 py-0.5 text-xs leading-5',
        LOOKS[look],
        late && LATE_EDGE,
        className,
      )}
    >
      <span
        role="img"
        aria-label={t(`events.toDos.${state}`)}
        className="inline-flex shrink-0 text-event-todo"
      >
        <Icon aria-hidden className="size-3" />
      </span>
      {children}
    </span>
  )
}
