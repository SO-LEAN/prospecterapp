import { afterEach, describe, expect, it } from 'vitest'
import { keepThePageFromZooming } from './keep-the-page-from-zooming'

function pinch(target: HTMLElement, type = 'gesturestart') {
  const event = new Event(type, { bubbles: true, cancelable: true })
  target.dispatchEvent(event)
  return event
}

function aPageWithAMap() {
  const map = document.createElement('div')
  const marker = document.createElement('button')
  const text = document.createElement('p')
  map.append(marker)
  document.body.append(map, text)
  return { map, marker, text }
}

let letGo = () => {}

afterEach(() => {
  letGo()
  document.body.replaceChildren()
})

describe('keepThePageFromZooming', () => {
  it.each(['gesturestart', 'gesturechange'])('keeps the page from zooming on a %s over the text', (type) => {
    const { text } = aPageWithAMap()
    letGo = keepThePageFromZooming(document)

    expect(pinch(text, type).defaultPrevented).toBe(true)
  })

  it('keeps the page from zooming on a pinch over a map and its markers', () => {
    const { map, marker } = aPageWithAMap()
    letGo = keepThePageFromZooming(document)

    expect(pinch(map).defaultPrevented).toBe(true)
    expect(pinch(marker).defaultPrevented).toBe(true)
  })

  it('lets go of the pinches once asked', () => {
    const { text } = aPageWithAMap()
    keepThePageFromZooming(document)()

    expect(pinch(text).defaultPrevented).toBe(false)
  })
})
