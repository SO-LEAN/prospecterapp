import { TriangleAlert } from 'lucide-react'
import { type ComponentProps, createContext, type DragEvent, type ReactNode, use } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import type { CalendarToDo } from '../queries'
import { movedTo, type ToDoRevision } from '../to-dos'

/** What a to-do dragged carries, told apart from the exchanges the grid moves by its own gestures. */
const TO_DO_DRAGGED = 'application/x-prospecter-to-do'

/**
 * The box ticking a to-do off or unticking it, told by the route composing the to-dos: a feature never imports
 * another.
 */
export const ToDoBoxOf = createContext<(toDo: CalendarToDo) => ReactNode>(() => null)

type Props = Omit<ComponentProps<'div'>, 'children'> & {
  toDo: CalendarToDo
  late: boolean
  /** « En retard » written beside its icon, where there is room for it; said to the screen readers otherwise. */
  withTheLateLabel?: boolean
}

/**
 * A to-do of the calendar: its box, its hour when said, its text; done, it stays, greyed. Late, its edge, an icon and
 * « En retard » say it, never the colour alone, with one pulse when shown. One the API lets revise is dragged to
 * another day.
 */
export function CalendarToDoChip({ toDo, late, withTheLateLabel = false, className, ...props }: Props) {
  const { t } = useTranslation()
  const boxOf = use(ToDoBoxOf)
  const done = Boolean(toDo.doneAt)

  return (
    // biome-ignore lint/a11y/noStaticElementInteractions: dragging is a shortcut of the pointer, the keyboard revises the day of a to-do from its card
    <div
      draggable={Boolean(toDo._links.revise)}
      onDragStart={(event) => event.dataTransfer.setData(TO_DO_DRAGGED, toDo.id)}
      data-late={late ? '' : undefined}
      className={cn(
        'flex min-w-0 select-none items-center gap-1 rounded-md border bg-card px-1.5 py-0.5 text-xs leading-5',
        toDo._links.revise && 'cursor-grab',
        done && 'bg-muted text-muted-foreground',
        late && 'border-l-4 border-l-warning motion-safe:animate-late-once',
        className,
      )}
      {...props}
    >
      {boxOf(toDo)}
      {toDo.dueAt && <span className="shrink-0 tabular-nums">{toDo.dueAt.replace(':', 'h')}</span>}
      <span className={cn('min-w-0 truncate', done && 'line-through')}>{toDo.text}</span>
      {late && (
        <>
          <TriangleAlert aria-hidden className="size-3 shrink-0" />
          <span className={withTheLateLabel ? 'shrink-0 font-medium' : 'sr-only'}>{t('calendar.late')}</span>
        </>
      )}
    </div>
  )
}

/** Revises a to-do: told by the route composing the to-dos. */
export type ReviseToDo = (id: string, revision: ToDoRevision) => void

/**
 * Where a to-do is dropped to change its day, its hour kept: a day of the grid, of the month, or its band of the whole
 * day. A drop on its own day changes nothing.
 */
export function toDoDrop(toDos: CalendarToDo[], reviseToDo: ReviseToDo) {
  return (day: string) => ({
    onDragOver: (event: DragEvent) => {
      if (event.dataTransfer.types.includes(TO_DO_DRAGGED)) event.preventDefault()
    },
    onDrop: (event: DragEvent) => {
      const toDo = toDos.find(({ id }) => id === event.dataTransfer.getData(TO_DO_DRAGGED))
      if (!toDo?._links.revise || toDo.dueOn === day) return
      event.preventDefault()
      reviseToDo(toDo.id, movedTo(toDo, day))
    },
  })
}
