import { useEffect, useState } from 'react'

/** The value, updated only once it has not changed for `delay` ms, so that search as you type does not send a request per key. */
export function useDebouncedValue<T>(value: T, delay = 300): T {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const timeout = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(timeout)
  }, [value, delay])

  return debounced
}
