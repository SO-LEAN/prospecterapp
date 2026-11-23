import { infiniteQueryOptions } from '@tanstack/react-query'
import { fetchClient } from './client'
import { dataOf, nextSliceOf } from './slices'

const MARK = '#'
const SEPARATOR = ','

/**
 * The tags chosen to find the contacts that carry them, as the URL and the API keep them: their names without their
 * mark, separated by commas (`ESN,EMAIL`). So a filter can be shared as a link.
 */
export const chosenTagsIn = (search: string): string[] =>
  search === '' ? [] : search.split(SEPARATOR).map((name) => MARK + name)

export const searchOfTags = (tags: string[]): string =>
  tags.map((tag) => (tag.startsWith(MARK) ? tag.slice(MARK.length) : tag)).join(SEPARATOR)

/**
 * The contacts that carry every chosen tag (`ESN,EMAIL`, the names without their mark), one slice after another.
 * Each slice gives the total count (ADR 0143). The contact list filtered by tags and a campaign's target (ADR 0144)
 * both read it the same way.
 */
export const taggedContactSlicesQuery = (tags: string) =>
  infiniteQueryOptions({
    queryKey: ['get', '/api/tagged-contacts', { params: { query: { tags } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/tagged-contacts', {
          params: { query: { tags, after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })
