import { useRouter } from '@tanstack/react-router'
import { useEffect, useState } from 'react'
import { useDebouncedValue } from './use-debounced-value'

/**
 * The typed query, and the function that changes it. Once the query stops changing, it goes to `search`, unless the
 * member left the page meanwhile. The page stays rendered while the next one loads, and a search would bring it back.
 */
export function useSearchAsYouType(searched: string, search: (query: string) => void) {
  const [query, setQuery] = useState(searched)
  const debouncedQuery = useDebouncedValue(query)
  const router = useRouter()
  const [thisPage] = useState(() => router.latestLocation.pathname)

  useEffect(() => {
    const stillOnThisPage = router.latestLocation.pathname === thisPage
    if (debouncedQuery !== searched && stillOnThisPage) {
      search(debouncedQuery)
    }
  }, [debouncedQuery])

  return [query, setQuery] as const
}
