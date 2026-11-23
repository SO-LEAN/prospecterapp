const BLINK_MS = 1_000

let stopBlinking: (() => void) | null = null

/**
 * A background tab's title alternates with `text` every second, until the tab comes to the front again. A second
 * call replaces the first call's text.
 */
export function blinkTheTitleWhileHidden(text: string) {
  stopBlinking?.()
  const title = document.title
  const blinking = window.setInterval(() => {
    document.title = document.title === text ? title : text
  }, BLINK_MS)
  document.title = text

  const stopOnceInFront = () => {
    if (document.visibilityState === 'visible') {
      stop()
    }
  }
  const stop = () => {
    window.clearInterval(blinking)
    document.removeEventListener('visibilitychange', stopOnceInFront)
    document.title = title
    stopBlinking = null
  }
  document.addEventListener('visibilitychange', stopOnceInFront)
  stopBlinking = stop
}
