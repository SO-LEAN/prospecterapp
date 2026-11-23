const SAFARI_PINCH_EVENTS = ['gesturestart', 'gesturechange'] as const

const cancel = (pinch: Event) => pinch.preventDefault()

/**
 * Safari zooms the page on a pinch, whatever the page's viewport and `touch-action` say. MapLibre zooms the maps from
 * the touch events, and cancelling these events does not affect them. Returns the function that releases the pinches.
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
