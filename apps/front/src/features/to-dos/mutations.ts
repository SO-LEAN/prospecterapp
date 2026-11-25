import { type QueryClient, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { type ToDo, toDoKeys } from './queries'

/**
 * A to-do shows on its contact's card, on its organizations' cards, among my to-dos, in my calendar and in what I have
 * to process. Each of them is read again, and the gesture stays pending until they are. So no list shows the former
 * state once the gesture is over.
 */
const readTheListsAgain = (queryClient: QueryClient) =>
  Promise.all([
    queryClient.invalidateQueries({ queryKey: toDoKeys.ofContacts }),
    queryClient.invalidateQueries({ queryKey: toDoKeys.ofOrganizations }),
    queryClient.invalidateQueries({ queryKey: toDoKeys.mine }),
    queryClient.invalidateQueries({ queryKey: queryKeys.myCalendar }),
    queryClient.invalidateQueries({ queryKey: queryKeys.exchangesToProcess }),
  ])

export function useNoteToDo() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/contacts/{id}/to-dos', {
    meta: { silentValidation: true },
    onSuccess: async () => {
      await readTheListsAgain(queryClient)
      toast.success(t('toDos.noted'))
    },
  })
}

export function useNoteOrganizationToDo() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/organizations/{id}/to-dos', {
    meta: { silentValidation: true },
    onSuccess: async () => {
      await readTheListsAgain(queryClient)
      toast.success(t('toDos.noted'))
    },
  })
}

/** A form shows the refusals of a revision it sends. A toast shows them for a revision from elsewhere, such as a to-do dropped on another day. */
export function useReviseToDo({ fromAForm = true } = {}) {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/to-dos/{id}', {
    meta: { silentValidation: fromAForm },
    onSuccess: async () => {
      await readTheListsAgain(queryClient)
      toast.success(t('common.saved'))
    },
  })
}

/** How long the toast of a to-do ticked off lets the member take the tick back. */
const TICK_UNDO_DELAY_MS = 6000

/** Says that the to-do is done. Its « Annuler » unticks it, while the to-do carries the link that does it. */
function useTickedOffToast() {
  const { t } = useTranslation()
  const untick = useUntickToDo()

  return (toDo: Pick<ToDo, 'id' | '_links'>) =>
    toast(t('toDos.tickedOff'), {
      duration: TICK_UNDO_DELAY_MS,
      action: toDo._links.untick && {
        label: t('common.undo'),
        onClick: () => untick.mutate({ params: { path: { id: toDo.id } } }),
      },
    })
}

/**
 * Ticks a to-do off. Where the tick takes the to-do away, it is `undoable`: its toast can take the tick back. The
 * toast lives in the mutation's options, so it outlives the line that the lists, read again, take away.
 */
export function useTickOffToDo({ undoable = false } = {}) {
  const queryClient = useQueryClient()
  const showTheToast = useTickedOffToast()

  return api.useMutation('post', '/api/to-dos/{id}/done', {
    onSuccess: async (toDo) => {
      if (undoable) showTheToast(toDo)
      await readTheListsAgain(queryClient)
    },
  })
}

export function useUntickToDo() {
  const queryClient = useQueryClient()

  return api.useMutation('delete', '/api/to-dos/{id}/done', {
    onSuccess: () => readTheListsAgain(queryClient),
  })
}

export function useEraseToDo() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('delete', '/api/to-dos/{id}', {
    onSuccess: async () => {
      await readTheListsAgain(queryClient)
      toast.success(t('toDos.erased'))
    },
  })
}
