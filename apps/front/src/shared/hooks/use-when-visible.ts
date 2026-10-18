import { useEffect, useRef } from 'react'

/**
 * A ref whose element calls `onVisible` each time it scrolls into view: the end of a list loading what
 * follows. Without IntersectionObserver (jsdom), it never calls.
 */
export function useWhenVisible<T extends Element>(onVisible: () => void) {
  const ref = useRef<T>(null)

  useEffect(() => {
    const element = ref.current
    if (!element || typeof IntersectionObserver === 'undefined') {
      return
    }
    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        onVisible()
      }
    })
    observer.observe(element)
    return () => observer.disconnect()
  }, [onVisible])

  return ref
}
