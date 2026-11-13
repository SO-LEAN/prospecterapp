import { type QueryClient, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { toDoKeys } from './queries'

/**
 * A to-do shows on the cards of its contact, of its organizations, and among mine: each is read again, the gesture
 * pending until they are, so that a list never shows the former state once the gesture is over.
 */
const readTheListsAgain = (queryClient: QueryClient) =>
  Promise.all([
    queryClient.invalidateQueries({ queryKey: toDoKeys.ofContacts }),
    queryClient.invalidateQueries({ queryKey: toDoKeys.ofOrganizations }),
    queryClient.invalidateQueries({ queryKey: toDoKeys.mine }),
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

export function useReviseToDo() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/to-dos/{id}', {
    meta: { silentValidation: true },
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
