import { api } from '@/shared/api/client'

export const dashboardQuery = () => api.queryOptions('get', '/api/dashboard')
