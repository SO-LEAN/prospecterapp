import { expect, type Locator, type Page, test } from '@playwright/test'

const LOGO_SIDE = 80
const FORMER_NAME_SIZE = 18
const PHONE = { width: 375, height: 812 }
const READABLE_GRAPHIC_CONTRAST = 3
const READABLE_TEXT_CONTRAST = 4.5

function brandOf(page: Page) {
  const name = page.getByRole('main').getByText('Prospecter', { exact: true })
  return { name, logo: name.locator('xpath=preceding-sibling::*[local-name()="svg"][1]') }
}

async function boxOf(locator: Locator) {
  const box = await locator.boundingBox()
  if (box === null) {
    throw new Error(`${locator} is not displayed`)
  }
  return box
}

function centerOf(box: { x: number; width: number }) {
  return box.x + box.width / 2
}

async function fontSizeOf(locator: Locator) {
  return locator.evaluate((element) => Number.parseFloat(getComputedStyle(element).fontSize))
}

/** The color of a CSS property as sRGB channels, whatever the notation (`oklch()`, hex…), painted on a pixel. */
async function colorOf(locator: Locator, property: 'fill' | 'color' | 'background-color') {
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
function contrastBetween(first: number[], second: number[]) {
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

test('the sign-in screen shows the logo large, centered, the name of the brand below it', async ({
  page,
}) => {
  await page.goto('/auth/sign-in')
  const { name, logo } = brandOf(page)
  const logoBox = await boxOf(logo)
  const nameBox = await boxOf(name)
  const cardBox = await boxOf(page.locator('[data-slot="card"]'))

  expect(logoBox.width).toBeCloseTo(LOGO_SIDE, 0)
  expect(logoBox.height).toBeCloseTo(LOGO_SIDE, 0)
  expect(centerOf(logoBox), 'the logo centered above the card').toBeCloseTo(centerOf(cardBox), 0)
  expect(nameBox.y, 'the name below the logo').toBeGreaterThanOrEqual(logoBox.y + logoBox.height)
  expect(centerOf(nameBox), 'the name centered under the logo').toBeCloseTo(centerOf(logoBox), 0)
  expect(await fontSizeOf(name), 'the name larger than before').toBeGreaterThan(FORMER_NAME_SIZE)
  expect(cardBox.y, 'the card below the name').toBeGreaterThan(nameBox.y + nameBox.height)
})

test('in the dark theme, the ink of the logo and the name stay readable on the background', async ({
  page,
}) => {
  await page.emulateMedia({ colorScheme: 'dark' })
  await page.goto('/auth/sign-in')
  await expect(page.locator('html'), 'the dark theme on').toHaveClass(/\bdark\b/)
  const { name, logo } = brandOf(page)
  const background = await colorOf(page.getByRole('main'), 'background-color')

  const ink = await colorOf(logo.locator('path').first(), 'fill')
  const text = await colorOf(name, 'color')

  expect(contrastBetween(ink, background), 'the ink of the logo').toBeGreaterThanOrEqual(
    READABLE_GRAPHIC_CONTRAST,
  )
  expect(contrastBetween(text, background), 'the name').toBeGreaterThanOrEqual(READABLE_TEXT_CONTRAST)
})

test('on a phone, the sign-in screen fits the width without scrolling', async ({ page }) => {
  await page.setViewportSize(PHONE)
  await page.goto('/auth/sign-in')
  await expect(brandOf(page).name).toBeVisible()

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)

  expect(overflow).toBeLessThanOrEqual(0)
})
