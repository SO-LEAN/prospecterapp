import { useRouter } from '@tanstack/react-router'
import { useEffect, useState } from 'react'
import { useDebouncedValue } from './use-debounced-value'

/**
 * The query typed, and the function changing it; `search` is given it once it stopped changing, unless the page
 * was left meanwhile: the page stays rendered while the next one loads, and searching then would bring it back.
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
