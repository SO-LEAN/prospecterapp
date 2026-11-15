const BLINK_MS = 1_000

let stopBlinking: (() => void) | null = null

/**
 * The title of a tab in the background alternates with `text` each second, until the tab comes in front again; a
 * second call replaces the text of the first.
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
