import { cn } from '@/shared/lib/utils'
import type { ToDo } from '../queries'
import { useToggle } from '../use-toggle'

/** The box of a to-do alone, ticked off or unticked in place: what a page composing the to-dos puts in its chips. */
export function ToDoBox({ toDo, className }: { toDo: ToDo; className?: string }) {
  const toggle = useToggle(toDo)

  return <input type="checkbox" className={cn('size-3.5 shrink-0 accent-primary', className)} {...toggle} />
}
