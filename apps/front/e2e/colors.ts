import type { Locator } from '@playwright/test'

export const READABLE_GRAPHIC_CONTRAST = 3
export const READABLE_TEXT_CONTRAST = 4.5

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

/** WCAG 2 contrast ratio of two sRGB colors. */
export function contrastBetween(first: number[], second: number[]) {
  const luminances = [luminanceOf(first), luminanceOf(second)]
  return (Math.max(...luminances) + 0.05) / (Math.min(...luminances) + 0.05)
}

const CHANNEL_WEIGHTS = [0.2126, 0.7152, 0.0722]

function luminanceOf(channels: number[]) {
  return channels.reduce((sum, channel, index) => sum + (CHANNEL_WEIGHTS[index] ?? 0) * linear(channel), 0)
}

function linear(channel: number) {
  const value = channel / 255
  return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
}
