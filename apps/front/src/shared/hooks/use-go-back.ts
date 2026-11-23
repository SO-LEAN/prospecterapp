import { useCanGoBack, useRouter } from '@tanstack/react-router'

/**
 * Returns to the application's previous page, like the browser's back button, so that leaving a form does not stack
 * its opening page again. Without history (the page was opened from a link), it goes to `fallback`.
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
