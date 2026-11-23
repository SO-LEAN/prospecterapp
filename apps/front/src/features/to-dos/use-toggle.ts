import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useTickOffToDo, useUntickToDo } from './mutations'
import type { ToDo } from './queries'

type Toggled = Pick<ToDo, 'id' | 'text' | 'doneAt' | '_links'>

/**
 * A to-do's box, ticked off or unticked in one click when its links allow it. It shows the click at once. It keeps
 * showing it until the lists, read again, show the new state, or until a refusal brings back the former one.
 */
export function useToggle<Item extends Toggled>(toDo: Item, onTickedOff?: (toDo: Item) => void) {
  const { t } = useTranslation()
  const tickOff = useTickOffToDo()
  const untick = useUntickToDo()
  const [clicked, setClicked] = useState<boolean | null>(null)
  const done = Boolean(toDo.doneAt)
  const params = { params: { path: { id: toDo.id } } }

  const onToggle = () => {
    setClicked(!done)
    const shownAsItIs = { onSettled: () => setClicked(null) }

    if (done) {
      untick.mutate(params, shownAsItIs)
      return
    }
    tickOff.mutate(params, { ...shownAsItIs, onSuccess: () => onTickedOff?.(toDo) })
  }

  return {
    checked: clicked ?? done,
    disabled: !(done ? toDo._links.untick : toDo._links.tickOff) || tickOff.isPending || untick.isPending,
    'aria-label': t(done ? 'toDos.untick' : 'toDos.tickOff', { text: toDo.text }),
    onChange: onToggle,
  }
}
