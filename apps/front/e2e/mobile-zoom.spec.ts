import { devices, expect, type Locator, type Page, test } from '@playwright/test'
import { signInOnTheSignInScreen } from './sign-in'

const { defaultBrowserType: _, ...aPhone } = devices['Pixel 7']
test.use(aPhone)

const ENLARGED = 2
const ONE_FINGER_SLIDE = -200

type Point = { x: number; y: number }

async function centerOf(target: Locator): Promise<Point> {
  const box = await target.boundingBox()
  if (!box) throw new Error(`${target} is not on the screen`)
  return { x: box.x + box.width / 2, y: box.y + box.height / 2 }
}

async function leftEdgeOf(target: Locator): Promise<Point> {
  const box = await target.boundingBox()
  if (!box) throw new Error(`${target} is not on the screen`)
  return { x: box.x, y: box.y + box.height / 2 }
}

/** Two fingers spread around the point, as a phone does it: the browser decides who takes the pinch. */
async function pinchToEnlarge(page: Page, { x, y }: Point) {
  const touchScreen = await page.context().newCDPSession(page)
  await touchScreen.send('Input.synthesizePinchGesture', {
    x,
    y,
    scaleFactor: ENLARGED,
    gestureSourceType: 'touch',
  })
}

async function slideOneFinger(page: Page, { x, y }: Point) {
  const touchScreen = await page.context().newCDPSession(page)
  await touchScreen.send('Input.synthesizeScrollGesture', {
    x,
    y,
    yDistance: ONE_FINGER_SLIDE,
    gestureSourceType: 'touch',
  })
}

const scaleOfThePage = (page: Page) => page.evaluate(() => window.visualViewport?.scale)
const scrollOfThePage = (page: Page) => page.evaluate(() => window.scrollY)

function mapOf(page: Page) {
  return page.locator('.maplibregl-map')
}

async function theDashboardWithItsMap(page: Page) {
  await signInOnTheSignInScreen(page)
  await expect(page.locator('.maplibregl-marker').first()).toBeVisible()
  await page.waitForLoadState('networkidle')
}

test('on a phone, pinching the map zooms the map, the page keeps its size', async ({ page }) => {
  await theDashboardWithItsMap(page)
  const before = await mapOf(page).screenshot()

  await pinchToEnlarge(page, await centerOf(mapOf(page)))

  expect(await scaleOfThePage(page), 'the page keeps its size').toBe(1)
  await expect.poll(() => mapOf(page).screenshot(), 'the map zoomed').not.toEqual(before)
})

test('on a phone, a pinch with a finger off the map leaves the page its size', async ({ page }) => {
  await theDashboardWithItsMap(page)

  await pinchToEnlarge(page, await leftEdgeOf(mapOf(page)))

  expect(await scaleOfThePage(page)).toBe(1)
})

test('on a phone, one finger sliding on the map scrolls the page', async ({ page }) => {
  await theDashboardWithItsMap(page)
  const scrollBefore = await scrollOfThePage(page)

  await slideOneFinger(page, await centerOf(mapOf(page)))

  expect(await scrollOfThePage(page)).toBeGreaterThan(scrollBefore)
})

test('on a phone, the page out of the map still enlarges under a pinch', async ({ page }) => {
  await theDashboardWithItsMap(page)

  await pinchToEnlarge(page, await centerOf(page.getByRole('heading', { name: /Bonjour/ })))

  await expect.poll(() => scaleOfThePage(page)).toBeGreaterThan(1)
})

test('on a phone, a double tap while scrolling a list does not zoom the page', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  await page.goto('/organizations')
  const aLineOfTheList = page.getByRole('main').getByRole('link').first()

  await expect(aLineOfTheList, 'the browser waits for no second tap to zoom').toHaveCSS(
    'touch-action',
    'manipulation',
  )
})
