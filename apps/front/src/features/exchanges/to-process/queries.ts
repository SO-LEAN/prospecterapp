import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type ExchangesToProcess = components['schemas']['ExchangesToProcessResource']
export type ExchangeToProcess = ExchangesToProcess['items'][number]

/** What passed meanwhile, its end reached while the page stays open, is told without a gesture. */
const READ_AGAIN_EVERY_MS = 5 * 60_000

/** What I planned whose end has passed and I have not handled yet, the oldest first. */
export const exchangesToProcessQuery = () =>
  api.queryOptions('get', '/api/me/exchanges-to-process', undefined, { refetchInterval: READ_AGAIN_EVERY_MS })
