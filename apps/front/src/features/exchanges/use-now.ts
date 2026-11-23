import { useEffect, useState } from 'react'

const MINUTE_MS = 60_000

/** The screen's current moment, refreshed each minute. The line of now moves, and the exchanges that have passed become ones to handle. */
export function useNow(): Date {
  const [now, setNow] = useState(() => new Date())
  useEffect(() => {
    const ticking = window.setInterval(() => setNow(new Date()), MINUTE_MS)
    return () => window.clearInterval(ticking)
  }, [])
  return now
}
