import type { Locator } from '@playwright/test'

export { contrastBetween, READABLE_GRAPHIC_CONTRAST, READABLE_TEXT_CONTRAST } from '../src/test/contrast'

export type ColorProperty = 'fill' | 'color' | 'background-color' | 'border-top-color'

/** The color of a CSS property as sRGB channels, whatever the notation (`oklch()`, hex…), painted on a pixel. */
export async function colorOf(locator: Locator, property: ColorProperty) {
  return locator.evaluate((element, name) => {
    const context = document.createElement('canvas').getContext('2d', { willReadFrequently: true })
    if (context === null) {
      throw new Error('No 2D canvas')
    }
    context.fillStyle = getComputedStyle(element).getPropertyValue(name)
    context.fillRect(0, 0, 1, 1)
    return Array.from(context.getImageData(0, 0, 1, 1).data.slice(0, 3))
  }, property)
}
