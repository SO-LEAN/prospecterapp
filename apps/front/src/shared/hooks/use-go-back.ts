import { useCanGoBack, useRouter } from '@tanstack/react-router'

/**
 * Returns to the previous page of the application, like the browser back button, so leaving a form
 * does not stack the page it was opened from again; without history (opened from a link), `fallback`.
 */
export function useGoBack(fallback: () => unknown) {
  const router = useRouter()
  const canGoBack = useCanGoBack()

  return () => {
    if (canGoBack) {
      router.history.back()
    } else {
      void fallback()
    }
  }
}
