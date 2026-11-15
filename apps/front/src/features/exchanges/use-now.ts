import { useEffect, useState } from 'react'

const MINUTE_MS = 60_000

/** The moment of the screen, again each minute: the line of now moves, what passed becomes to handle. */
export function useNow(): Date {
  const [now, setNow] = useState(() => new Date())
  useEffect(() => {
    const ticking = window.setInterval(() => setNow(new Date()), MINUTE_MS)
    return () => window.clearInterval(ticking)
  }, [])
  return now
}
