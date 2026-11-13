import { api } from '@/shared/api/client'

/** Whether and how long before a planned exchange the signed-in member is reminded of it, whatever the device. */
export const remindersQuery = () => api.queryOptions('get', '/api/me/reminders')
