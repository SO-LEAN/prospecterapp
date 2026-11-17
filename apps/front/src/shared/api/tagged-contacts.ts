import { infiniteQueryOptions } from '@tanstack/react-query'
import { fetchClient } from './client'
import { dataOf, nextSliceOf } from './slices'

const MARK = '#'
const SEPARATOR = ','

/**
 * The tags chosen to find the contacts carrying them, as the url and the api keep them: their names without their
 * mark, separated by commas (`ESN,EMAIL`), so that a filter is shared as a link.
 */
export const chosenTagsIn = (search: string): string[] =>
  search === '' ? [] : search.split(SEPARATOR).map((name) => MARK + name)

export const searchOfTags = (tags: string[]): string =>
  tags.map((tag) => (tag.startsWith(MARK) ? tag.slice(MARK.length) : tag)).join(SEPARATOR)

/**
 * The contacts carrying every tag chosen (`ESN,EMAIL`, their names without their mark), a slice after the other,
 * each slice saying how many there are in all (ADR 0143): the list of the contacts filtered by tags and the target of
 * a campaign (ADR 0144) read it alike.
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
