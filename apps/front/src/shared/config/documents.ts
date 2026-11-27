import { queryOptions } from '@tanstack/react-query'

export const EVENT_STORMING_OVERVIEW = '/docs/event-storming/'

/**
 * Whether the instance serves the documents generated from the code. Every release holds them, and the instance
 * decides whether it serves them, never the build (ADR 0066).
 */
export const servedDocumentsQuery = () =>
  queryOptions({
    queryKey: ['head', EVENT_STORMING_OVERVIEW],
    queryFn: async () => {
      const answer = await fetch(new URL(EVENT_STORMING_OVERVIEW, window.location.origin), { method: 'HEAD' })
      return answer.ok
    },
    staleTime: 'static',
  })
