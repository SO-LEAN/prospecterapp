const SAFARI_PINCH_EVENTS = ['gesturestart', 'gesturechange'] as const

const keepThePageFromZooming = (pinch: Event) => pinch.preventDefault()

/**
 * Safari zooms the page on a pinch it catches, whatever the `touch-action` of the map says; MapLibre zooms the map
 * from the touch events, which these do not cancel. Gives back what lets go of the pinches.
 */
export function keepThePinchesOnTheMap(map: HTMLElement) {
  for (const type of SAFARI_PINCH_EVENTS) {
    map.addEventListener(type, keepThePageFromZooming)
  }
  return () => {
    for (const type of SAFARI_PINCH_EVENTS) {
      map.removeEventListener(type, keepThePageFromZooming)
    }
  }
}
