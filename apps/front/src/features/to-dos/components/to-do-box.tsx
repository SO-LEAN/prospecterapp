import { cn } from '@/shared/lib/utils'
import type { ToDo } from '../queries'
import { useToggle } from '../use-toggle'

type Props = {
  toDo: ToDo
  /** Its toast offers to take the tick back. */
  undoable?: boolean
  className?: string
}

/** A to-do's box on its own, ticked off or unticked in place. A page that composes the to-dos puts it in its chips. */
export function ToDoBox({ toDo, undoable, className }: Props) {
  const toggle = useToggle(toDo, { undoable })

  return <input type="checkbox" className={cn('size-3.5 shrink-0 accent-primary', className)} {...toggle} />
}
