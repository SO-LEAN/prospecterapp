export const READABLE_GRAPHIC_CONTRAST = 3
export const READABLE_TEXT_CONTRAST = 4.5

/** WCAG 2 contrast ratio of two sRGB colors. */
export function contrastBetween(first: number[], second: number[]) {
  const luminances = [luminanceOf(first), luminanceOf(second)]
  return (Math.max(...luminances) + 0.05) / (Math.min(...luminances) + 0.05)
}

const CHANNEL_WEIGHTS = [0.2126, 0.7152, 0.0722]

/** WCAG 2 relative luminance of an sRGB color, from 0 (black) to 1 (white). */
export function luminanceOf(channels: number[]) {
  return channels.reduce((sum, channel, index) => sum + (CHANNEL_WEIGHTS[index] ?? 0) * linear(channel), 0)
}

function linear(channel: number) {
  const value = channel / 255
  return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
}
