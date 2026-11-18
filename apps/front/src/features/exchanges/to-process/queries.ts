import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { readerTimeZone } from '@/shared/lib/time-zone'

export type ExchangesToProcess = components['schemas']['ExchangesToProcessResource']
export type ExchangeToProcess = ExchangesToProcess['items'][number]
export type ToDoToProcess = ExchangesToProcess['toDos'][number]
export type ContactsToAttach = ExchangesToProcess['contactsToAttach']

/** What passed meanwhile, its end reached while the page stays open, is told without a gesture. */
const READ_AGAIN_EVERY_MS = 5 * 60_000

/**
 * What I planned whose end has passed and I have not handled yet, the oldest first; my to-dos of the day on my clock
 * and those late, the most overdue first.
 */
export const exchangesToProcessQuery = () =>
  api.queryOptions(
    'get',
    '/api/me/exchanges-to-process',
    { params: { query: { timeZone: readerTimeZone() } } },
    { refetchInterval: READ_AGAIN_EVERY_MS },
  )

/** What is left to process: the exchanges, the to-dos and the contacts to attach (ADR 0146). */
export const countOf = (toProcess: ExchangesToProcess) =>
  toProcess.items.length + toProcess.toDos.length + toProcess.contactsToAttach.length
