import { expect, type Locator, type Page, test } from '@playwright/test'

const LOGO_SIDE = 80
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

test('the sign-in screen shows the logo large, centered, the name of the brand below it', async ({
  page,
}) => {
  await page.goto('/auth/sign-in')
  const { name, logo } = brandOf(page)
  const logoBox = await boxOf(logo)
  const nameBox = await boxOf(name)
  const cardBox = await boxOf(page.getByRole('heading', { name: 'Connexion' }))

  expect(logoBox.width).toBeCloseTo(LOGO_SIDE, 0)
  expect(logoBox.height).toBeCloseTo(LOGO_SIDE, 0)
  expect(nameBox.y, 'the name below the logo').toBeGreaterThanOrEqual(logoBox.y + logoBox.height)
  expect(cardBox.y, 'the card below the name').toBeGreaterThan(nameBox.y + nameBox.height)
  expect(centerOf(nameBox), 'the name centered under the logo').toBeCloseTo(centerOf(logoBox), 0)
})

test('on a phone, the sign-in screen fits the width without scrolling', async ({ page }) => {
  await page.setViewportSize(PHONE)
  await page.goto('/auth/sign-in')
  await expect(brandOf(page).name).toBeVisible()

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)

  expect(overflow).toBeLessThanOrEqual(0)
})
