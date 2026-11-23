import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { remindersQuery } from './queries'

export type Reminders = components['schemas']['RemindersResource']

/** Saves each change of the switch or of the delay at once, and confirms it with a toast. */
export function useChangeMyReminders() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/me/reminders', {
    onSuccess: (reminders) => {
      queryClient.setQueryData(remindersQuery().queryKey, reminders)
      toast.success(t('common.saved'))
    },
  })
}
