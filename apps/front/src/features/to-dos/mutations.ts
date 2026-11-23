import { type QueryClient, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { toDoKeys } from './queries'

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

export function useTickOffToDo() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/to-dos/{id}/done', {
    onSuccess: () => readTheListsAgain(queryClient),
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
