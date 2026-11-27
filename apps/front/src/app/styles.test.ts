import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { contrastBetween, READABLE_TEXT_CONTRAST } from '@/test/contrast'

/** Read from the disk, from the front's root where Vitest runs: Vitest empties a stylesheet it imports, even as `?raw`. */
const styles = readFileSync('src/app/styles.css', 'utf8')

/** The light theme's tokens, as `:root` declares them, each `var()` resolved. */
const LIGHT_THEME = declarationsOf(blockOf(':root'))

/** What `@theme inline` gives Tailwind, as `bg-*`, `text-*` and `border-*` utilities. */
const TAILWIND_THEME = declarationsOf(blockOf('@theme inline'))

/** One step of an sRGB channel: the rounding of an OKLCH value written with three decimals. */
const ROUNDING = 1

/** The audit's forbidden zone, in HSL hues of the sRGB colors, as its palette gives them in hexadecimal. */
const VIOLET_AND_MAGENTA = { from: 250, to: 330 }

type Channels = [number, number, number]

describe('the « Le Filon » light theme', () => {
  it.each([
    ['--background', '#F6F4EF'],
    ['--foreground', '#0C1F2A'],
    ['--primary', '#0B6E75'],
    ['--accent', '#DDF0EF'],
    ['--ring', '#0B6E75'],
    ['--destructive', '#D5311F'],
    ['--chart-5', '#50647A'],
  ])('gives %s the value of the audit, %s', (token, hex) => {
    expectTheColor(token, hex)
  })

  it.each([
    ['--card', '#FFFFFF'],
    ['--popover', '#FFFFFF'],
    ['--secondary', '#EFECE5'],
    ['--secondary-foreground', '#0C1F2A'],
    ['--muted', '#EFECE5'],
    ['--muted-foreground', '#5E6D7A'],
    ['--accent-foreground', '#075158'],
    ['--success', '#0F9467'],
    ['--border', '#E7E3DA'],
    ['--input', '#DED9CE'],
    ['--chart-1', '#0B6E75'],
  ])('takes %s from the palette, %s', (token, hex) => {
    expectTheColor(token, hex)
  })

  it.each([
    ['--gold', '#F0A51B'],
    ['--gold-soft', '#FFF3D6'],
    ['--event-call', '#0F9467'],
    ['--event-call-soft', '#DCF5EA'],
    ['--event-meeting', '#E8502F'],
    ['--event-meeting-soft', '#FFE7E0'],
    ['--event-video', '#2477F2'],
    ['--event-video-soft', '#E1EDFF'],
    ['--event-written', '#50647A'],
    ['--event-written-soft', '#E8EDF2'],
    ['--event-todo', '#C98600'],
    ['--event-todo-soft', '#FFF1CC'],
    ['--day-weekend', '#F0EDE6'],
    ['--day-holiday', '#C2410C'],
    ['--day-holiday-soft', '#FFEDE3'],
    ['--day-vacation', '#B7791F'],
    ['--day-vacation-soft', '#FFF6DA'],
  ])('adds %s, %s', (token, hex) => {
    expectTheColor(token, hex)
  })

  it('writes the text on the gold in the ink of the gold, #2A1B00', () => {
    expectTheColor('--gold-foreground', '#2A1B00')
  })

  it('gives the links the primary color', () => {
    expect(LIGHT_THEME.get('--link')).toBe('var(--primary)')
  })

  it.each([
    ['--foreground', '--background'],
    ['--foreground', '--card'],
    ['--muted-foreground', '--background'],
    ['--muted-foreground', '--card'],
    ['--muted-foreground', '--muted'],
    ['--muted-foreground', '--sidebar'],
    ['--link', '--background'],
    ['--link', '--card'],
    ['--primary-foreground', '--primary'],
    ['--accent-foreground', '--accent'],
    ['--destructive-foreground', '--destructive'],
    ['--gold-foreground', '--gold'],
    ['--gold-foreground', '--gold-soft'],
  ])('keeps %s readable on %s', (text, surface) => {
    expect(contrastBetween(channelsOf(text), channelsOf(surface))).toBeGreaterThanOrEqual(
      READABLE_TEXT_CONTRAST,
    )
  })

  it.each([
    '--link',
    '--gold',
    '--gold-soft',
    '--gold-foreground',
    ...['call', 'meeting', 'video', 'written', 'todo'].flatMap((family) => [
      `--event-${family}`,
      `--event-${family}-soft`,
    ]),
    '--day-weekend',
    '--day-holiday',
    '--day-holiday-soft',
    '--day-vacation',
    '--day-vacation-soft',
  ])('gives Tailwind %s as a color', (token) => {
    expect(TAILWIND_THEME.get(`--color-${token.slice(2)}`)).toBe(`var(${token})`)
  })

  it.each(colorTokens())('keeps %s out of the violets and the magentas', (token) => {
    const hue = hueOf(channelsOf(token))
    expect(hue < VIOLET_AND_MAGENTA.from || hue > VIOLET_AND_MAGENTA.to, `${token} has the hue ${hue}°`).toBe(
      true,
    )
  })
})

function expectTheColor(token: string, hex: string) {
  const expected = channelsOfHex(hex)
  const actual = channelsOf(token)
  actual.forEach((channel, index) => {
    expect(
      Math.abs(channel - (expected[index] ?? 0)),
      `${token} is ${actual} instead of ${expected}`,
    ).toBeLessThanOrEqual(ROUNDING)
  })
}

function blockOf(selector: string) {
  const start = styles.indexOf(`${selector} {`)
  return styles.slice(start, styles.indexOf('}', start))
}

function declarationsOf(block: string) {
  return new Map(
    Array.from(block.matchAll(/(--[\w-]+):\s*([^;]+);/g), ([, name = '', value = '']) => [
      name,
      value.trim(),
    ]),
  )
}

function channelsOf(token: string): Channels {
  if (!LIGHT_THEME.has(token)) {
    throw new Error(`${token} is not declared in :root`)
  }
  const value = resolved(token)
  return value.startsWith('#') ? channelsOfHex(value) : channelsOfOklch(value)
}

function colorTokens() {
  return Array.from(LIGHT_THEME.keys()).filter((token) => isAColor(resolved(token)))
}

function resolved(token: string): string {
  const value = LIGHT_THEME.get(token) ?? ''
  const reference = /^var\((--[\w-]+)\)$/.exec(value)?.[1]
  return reference === undefined ? value : resolved(reference)
}

function isAColor(value: string) {
  return value.startsWith('#') || value.startsWith('oklch(')
}

function hueOf([red, green, blue]: Channels) {
  const highest = Math.max(red, green, blue)
  const spread = highest - Math.min(red, green, blue)
  if (spread === 0) {
    return 0
  }
  const sector =
    highest === red
      ? (green - blue) / spread + 6
      : highest === green
        ? (blue - red) / spread + 2
        : (red - green) / spread + 4
  return Math.round((sector * 60) % 360)
}

function channelsOfHex(hex: string): Channels {
  return [1, 3, 5].map((start) => Number.parseInt(hex.slice(start, start + 2), 16)) as Channels
}

/** CSS Color 4's conversion of `oklch(L C H)` to sRGB, through OKLab and linear sRGB. */
function channelsOfOklch(value: string): Channels {
  const match = /^oklch\(([\d.]+) ([\d.]+) ([\d.]+)\)$/.exec(value)
  if (match === null) {
    throw new Error(`${value} is not an oklch() color`)
  }
  const [lightness, chroma, hue] = match.slice(1).map(Number) as Channels
  const a = chroma * Math.cos((hue * Math.PI) / 180)
  const b = chroma * Math.sin((hue * Math.PI) / 180)
  const l = (lightness + 0.3963377774 * a + 0.2158037573 * b) ** 3
  const m = (lightness - 0.1055613458 * a - 0.0638541728 * b) ** 3
  const s = (lightness - 0.0894841775 * a - 1.291485548 * b) ** 3
  return [
    4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
    -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
    -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s,
  ].map(gammaEncoded) as Channels
}

function gammaEncoded(linear: number) {
  const clamped = Math.min(1, Math.max(0, linear))
  const encoded = clamped <= 0.0031308 ? 12.92 * clamped : 1.055 * clamped ** (1 / 2.4) - 0.055
  return Math.round(encoded * 255)
}
