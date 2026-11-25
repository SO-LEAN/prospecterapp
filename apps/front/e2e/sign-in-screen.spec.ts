import type { Locator, Page } from '@playwright/test'
import { colorOf, contrastBetween, READABLE_GRAPHIC_CONTRAST, READABLE_TEXT_CONTRAST } from './colors'
import { expect, test } from './fixtures'

const LOGO_SIDE = 80
const FORMER_NAME_SIZE = 18
const PHONE = { width: 375, height: 812 }

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

/** The theme chosen in the app, as its preferences keep it in the tab's storage before the page loads. */
async function chooseBeforeLoading(page: Page, theme: 'light' | 'dark') {
  await page.addInitScript((chosen) => {
    localStorage.setItem('prospecter-preferences', JSON.stringify({ state: { theme: chosen }, version: 0 }))
  }, theme)
}

async function nativeControlsOf(page: Page) {
  return page.locator('html').evaluate((html) => getComputedStyle(html).colorScheme)
}

test('the native controls follow the light theme chosen in the app, not the dark system', async ({
  page,
}) => {
  await page.emulateMedia({ colorScheme: 'dark' })
  await chooseBeforeLoading(page, 'light')
  await page.goto('/auth/sign-in')
  await expect(page.locator('html'), 'the light theme on').not.toHaveClass(/\bdark\b/)

  expect(await nativeControlsOf(page)).toBe('light')
})

test('the native controls follow the dark theme chosen in the app, not the light system', async ({
  page,
}) => {
  await page.emulateMedia({ colorScheme: 'light' })
  await chooseBeforeLoading(page, 'dark')
  await page.goto('/auth/sign-in')
  await expect(page.locator('html'), 'the dark theme on').toHaveClass(/\bdark\b/)

  expect(await nativeControlsOf(page)).toBe('dark')
})

test('on a phone, the sign-in screen fits the width without scrolling', async ({ page }) => {
  await page.setViewportSize(PHONE)
  await page.goto('/auth/sign-in')
  await expect(brandOf(page).name).toBeVisible()

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)

  expect(overflow).toBeLessThanOrEqual(0)
})

test('rejects a wrong password', async ({ page }) => {
  await page.goto('/')
  await page.getByLabel('E-mail').fill('prospector@solean-it.io')
  await page.getByLabel('Mot de passe').fill('wrong password')
  await page.getByRole('button', { name: 'Se connecter' }).click()

  await expect(page.getByRole('alert')).toHaveText('E-mail ou mot de passe incorrect.')
})
