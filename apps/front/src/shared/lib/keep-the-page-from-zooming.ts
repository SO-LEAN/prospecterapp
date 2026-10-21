const SAFARI_PINCH_EVENTS = ['gesturestart', 'gesturechange'] as const

const cancel = (pinch: Event) => pinch.preventDefault()

/**
 * Safari zooms the page on a pinch whatever the viewport and the `touch-action` of the page say; MapLibre zooms the
 * maps from the touch events, which these do not cancel. Gives back what lets go of the pinches.
 */
export function keepThePageFromZooming(page: Document) {
  for (const type of SAFARI_PINCH_EVENTS) {
    page.addEventListener(type, cancel)
  }
  return () => {
    for (const type of SAFARI_PINCH_EVENTS) {
      page.removeEventListener(type, cancel)
    }
  }
}
