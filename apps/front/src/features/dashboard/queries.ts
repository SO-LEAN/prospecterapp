import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type DashboardStats = components['schemas']['DashboardStatsResource']

export const dashboardQuery = () => api.queryOptions('get', '/api/dashboard')
