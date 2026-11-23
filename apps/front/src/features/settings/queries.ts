import { api } from '@/shared/api/client'

/** Whether the signed-in member is reminded of their planned exchanges, and how long before, whatever the device. */
export const remindersQuery = () => api.queryOptions('get', '/api/me/reminders')
