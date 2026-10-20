import { afterEach, describe, expect, it } from 'vitest'
import { keepThePinchesOnTheMap } from './keep-the-pinches-on-the-map'

function pinch(target: HTMLElement, type = 'gesturestart') {
  const event = new Event(type, { bubbles: true, cancelable: true })
  target.dispatchEvent(event)
  return event
}

function aMapOnThePage() {
  const map = document.createElement('div')
  const marker = document.createElement('button')
  const text = document.createElement('p')
  map.append(marker)
  document.body.append(map, text)
  return { map, marker, text }
}

afterEach(() => {
  document.body.replaceChildren()
})

describe('keepThePinchesOnTheMap', () => {
  it.each(['gesturestart', 'gesturechange'])('keeps the page from zooming on a %s over the map', (type) => {
    const { map } = aMapOnThePage()
    keepThePinchesOnTheMap(map)

    expect(pinch(map, type).defaultPrevented).toBe(true)
  })

  it('keeps the page from zooming on a pinch over a marker of the map', () => {
    const { map, marker } = aMapOnThePage()
    keepThePinchesOnTheMap(map)

    expect(pinch(marker).defaultPrevented).toBe(true)
  })

  it('leaves a pinch outside the map to the page, which zooms', () => {
    const { map, text } = aMapOnThePage()
    keepThePinchesOnTheMap(map)

    expect(pinch(text).defaultPrevented).toBe(false)
  })

  it('lets go of the pinches once the map is gone', () => {
    const { map } = aMapOnThePage()
    const letGo = keepThePinchesOnTheMap(map)

    letGo()

    expect(pinch(map).defaultPrevented).toBe(false)
  })
})
