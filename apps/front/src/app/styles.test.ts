import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { contrastBetween, luminanceOf, READABLE_TEXT_CONTRAST } from '@/test/contrast'

/** Read from the disk, from the front's root where Vitest runs: Vitest empties a stylesheet it imports, even as `?raw`. */
const styles = readFileSync('src/app/styles.css', 'utf8')

/** The light theme's tokens, as `:root` declares them. */
const LIGHT_THEME = declarationsOf(blockOf(':root'))

/**
 * The dark theme's tokens: `.dark` overrides `:root` on the same element.
 * A token that `.dark` does not redeclare resolves its `var()` among the dark values.
 */
const DARK_THEME: Theme = new Map([...LIGHT_THEME, ...declarationsOf(blockOf('.dark'))])

/** What `@theme inline` gives Tailwind, as `bg-*`, `text-*` and `border-*` utilities. */
const TAILWIND_THEME = declarationsOf(blockOf('@theme inline'))

/** One step of an sRGB channel: the rounding of an OKLCH value written with three decimals. */
const ROUNDING = 1

/** The audit's forbidden zone, in HSL hues of the sRGB colors, as its palette gives them in hexadecimal. */
const VIOLET_AND_MAGENTA = { from: 250, to: 330 }

const EVENT_FAMILIES = ['call', 'meeting', 'video', 'written', 'todo']

type Channels = [number, number, number]

type Theme = Map<string, string>

describe('the « Le Filon » light theme', () => {
  it.each([
    ['--background', '#F6F4EF'],
    ['--foreground', '#0C1F2A'],
    ['--primary', '#0B6E75'],
    ['--accent', '#DDF0EF'],
    ['--ring', '#0B6E75'],
    ['--destructive', '#D5311F'],
    ['--chart-5', '#50647A'],
    ['--sidebar', '#0B1E28'],
  ])('gives %s the value of the audit, %s', (token, hex) => {
    expectTheColor(LIGHT_THEME, token, hex)
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
    expectTheColor(LIGHT_THEME, token, hex)
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
    expectTheColor(LIGHT_THEME, token, hex)
  })

  it('writes the text on the gold in the ink of the gold, #2A1B00', () => {
    expectTheColor(LIGHT_THEME, '--gold-foreground', '#2A1B00')
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
    ['--sidebar-foreground', '--sidebar'],
    ['--sidebar-muted-foreground', '--sidebar'],
    ['--sidebar-accent-foreground', '--sidebar-accent'],
    ['--link', '--background'],
    ['--link', '--card'],
    ['--primary-foreground', '--primary'],
    ['--accent-foreground', '--accent'],
    ['--destructive-foreground', '--destructive'],
    ['--gold-foreground', '--gold'],
    ['--gold-foreground', '--gold-soft'],
  ])('keeps %s readable on %s', (text, surface) => {
    expectReadable(LIGHT_THEME, text, surface)
  })

  it.each([
    '--link',
    '--gold',
    '--gold-soft',
    '--gold-foreground',
    ...EVENT_FAMILIES.flatMap((family) => [`--event-${family}`, `--event-${family}-soft`]),
    '--day-weekend',
    '--day-holiday',
    '--day-holiday-soft',
    '--day-vacation',
    '--day-vacation-soft',
    '--sidebar-muted-foreground',
  ])('gives Tailwind %s as a color', (token) => {
    expect(TAILWIND_THEME.get(`--color-${token.slice(2)}`)).toBe(`var(${token})`)
  })

  it.each(colorTokens(LIGHT_THEME))('keeps %s out of the violets and the magentas', (token) => {
    expectOutOfTheVioletsAndTheMagentas(LIGHT_THEME, token)
  })
})

describe('the « Le Filon » dark theme', () => {
  it.each([
    ['--background', '#07141B'],
    ['--foreground', '#EAF1F4'],
    ['--card', '#0E2029'],
    ['--popover', '#0E2029'],
    ['--primary', '#2CB5BD'],
    ['--ring', '#2CB5BD'],
    ['--accent', '#0F3A40'],
    ['--destructive', '#FF6B57'],
    ['--sidebar', '#050F14'],
    ['--chart-5', '#9FB2C4'],
    ['--event-call', '#34C792'],
    ['--event-video', '#5B9DFF'],
    ['--event-meeting', '#FF7A5C'],
  ])('gives %s the value of the audit, %s', (token, hex) => {
    expectTheColor(DARK_THEME, token, hex)
  })

  it.each([
    ['--border', '#1A2E38'],
    ['--input', '#223843'],
    ['--secondary', '#142A34'],
    ['--muted', '#142A34'],
    ['--sidebar-accent', '#142A34'],
    ['--sidebar-border', '#12242D'],
    ['--muted-foreground', '#9AACB8'],
    ['--accent-foreground', '#A8E6E8'],
    ['--primary-foreground', '#07141B'],
    ['--success', '#34C792'],
    ['--warning', '#F2B33D'],
  ])('takes %s from the dark palette, %s', (token, hex) => {
    expectTheColor(DARK_THEME, token, hex)
  })

  it.each([
    ['--foreground', '--background'],
    ['--foreground', '--card'],
    ['--muted-foreground', '--background'],
    ['--muted-foreground', '--card'],
    ['--muted-foreground', '--muted'],
    ['--muted-foreground', '--sidebar'],
    ['--sidebar-foreground', '--sidebar'],
    ['--sidebar-muted-foreground', '--sidebar'],
    ['--sidebar-accent-foreground', '--sidebar-accent'],
    ['--link', '--background'],
    ['--link', '--card'],
    ['--primary-foreground', '--primary'],
    ['--accent-foreground', '--accent'],
    ['--destructive-foreground', '--destructive'],
    ['--gold-foreground', '--gold'],
    ['--gold', '--gold-soft'],
    ...EVENT_FAMILIES.flatMap((family) => [
      [`--event-${family}`, `--event-${family}-soft`],
      [`--event-${family}`, '--card'],
    ]),
    ['--day-holiday', '--day-holiday-soft'],
    ['--foreground', '--day-holiday-soft'],
    ['--day-vacation', '--day-vacation-soft'],
    ['--foreground', '--day-vacation-soft'],
    ['--foreground', '--day-weekend'],
  ])('keeps %s readable on %s', (text, surface) => {
    expectReadable(DARK_THEME, text, surface)
  })

  it.each(colorTokens(DARK_THEME))('keeps %s out of the violets and the magentas', (token) => {
    expectOutOfTheVioletsAndTheMagentas(DARK_THEME, token)
  })

  it.each(EVENT_FAMILIES)('lightens the tint of the family %s', (family) => {
    const tint = `--event-${family}`
    expect(luminanceOf(channelsOf(DARK_THEME, tint))).toBeGreaterThan(
      luminanceOf(channelsOf(LIGHT_THEME, tint)),
    )
  })
})

function expectOutOfTheVioletsAndTheMagentas(theme: Theme, token: string) {
  const hue = hueOf(channelsOf(theme, token))
  expect(hue < VIOLET_AND_MAGENTA.from || hue > VIOLET_AND_MAGENTA.to, `${token} has the hue ${hue}°`).toBe(
    true,
  )
}

function expectReadable(theme: Theme, text: string, surface: string) {
  expect(contrastBetween(channelsOf(theme, text), channelsOf(theme, surface))).toBeGreaterThanOrEqual(
    READABLE_TEXT_CONTRAST,
  )
}

function expectTheColor(theme: Theme, token: string, hex: string) {
  const expected = channelsOfHex(hex)
  const actual = channelsOf(theme, token)
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

function declarationsOf(block: string): Theme {
  return new Map(
    Array.from(block.matchAll(/(--[\w-]+):\s*([^;]+);/g), ([, name = '', value = '']) => [
      name,
      value.trim(),
    ]),
  )
}

function channelsOf(theme: Theme, token: string): Channels {
  if (!theme.has(token)) {
    throw new Error(`${token} is not declared`)
  }
  const value = resolved(theme, token)
  return value.startsWith('#') ? channelsOfHex(value) : channelsOfOklch(value)
}

function colorTokens(theme: Theme) {
  return Array.from(theme.keys()).filter((token) => isAColor(resolved(theme, token)))
}

/** The token's value, each `var()` resolved in the same theme, as the browser does on the element holding the theme. */
function resolved(theme: Theme, token: string): string {
  const value = theme.get(token) ?? ''
  const reference = /^var\((--[\w-]+)\)$/.exec(value)?.[1]
  return reference === undefined ? value : resolved(theme, reference)
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
