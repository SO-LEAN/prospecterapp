import { devices, expect, type Locator, type Page, test } from '@playwright/test'
import { signInOnTheSignInScreen } from './sign-in'

/*
 * Headless Chromium hands the synthesized touches to the page but never scrolls or zooms the page itself: the
 * journeys check what the browsers obey, the touch-action of the map and of the page, and the gesture events of
 * Safari; how a real phone obeys them is the QA journey's.
 */
const { defaultBrowserType: _, ...aPhone } = devices['Pixel 7']
test.use(aPhone)

const ENLARGED = 2
const PAGE_SCROLL_ONLY = 'pan-x pan-y'
const NO_DOUBLE_TAP_ZOOM = 'manipulation'

async function centerOf(target: Locator) {
  const box = await target.boundingBox()
  if (!box) throw new Error(`${target} is not on the screen`)
  return { x: box.x + box.width / 2, y: box.y + box.height / 2 }
}

async function pinchToEnlarge(page: Page, target: Locator) {
  const touchScreen = await page.context().newCDPSession(page)
  await touchScreen.send('Input.synthesizePinchGesture', {
    ...(await centerOf(target)),
    scaleFactor: ENLARGED,
    gestureSourceType: 'touch',
  })
}

/** What Safari sends on a pinch: cancelled, Safari does not zoom the page. */
function safariPinchOn(target: Locator) {
  return target.evaluate((element) => {
    const pinch = new Event('gesturestart', { bubbles: true, cancelable: true })
    element.dispatchEvent(pinch)
    return pinch.defaultPrevented
  })
}

function mapOf(page: Page) {
  return page.locator('.maplibregl-map')
}

function frameOfTheMap(page: Page) {
  return mapOf(page).locator('..')
}

async function theDashboardWithItsMap(page: Page) {
  await signInOnTheSignInScreen(page)
  await expect(page.locator('.maplibregl-marker').first()).toBeVisible()
  await page.waitForLoadState('networkidle')
}

test('on a phone, pinching the map zooms the map', async ({ page }) => {
  await theDashboardWithItsMap(page)
  const before = await mapOf(page).screenshot()

  await pinchToEnlarge(page, mapOf(page))

  await expect.poll(() => mapOf(page).screenshot(), 'the map zoomed').not.toEqual(before)
})

test('on a phone, the map leaves the page to scroll, never to zoom, even under a finger off its edge', async ({
  page,
}) => {
  await theDashboardWithItsMap(page)

  await expect(frameOfTheMap(page)).toHaveCSS('touch-action', PAGE_SCROLL_ONLY)
  expect(await safariPinchOn(frameOfTheMap(page)), 'Safari kept from zooming the page').toBe(true)
  expect(await safariPinchOn(page.locator('.maplibregl-marker').first()), 'over a marker too').toBe(true)
})

test('on a phone, the page out of the map still enlarges under a pinch, never under a double tap', async ({
  page,
}) => {
  await theDashboardWithItsMap(page)
  const theText = page.getByRole('heading', { name: /Bonjour/ })

  await expect(theText).toHaveCSS('touch-action', NO_DOUBLE_TAP_ZOOM)
  expect(await safariPinchOn(theText), 'Safari zooms the page').toBe(false)
})

test('on a phone, a double tap while scrolling a list does not zoom the page', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  await page.goto('/organizations')

  await expect(page.getByRole('main').getByRole('link').first()).toHaveCSS('touch-action', NO_DOUBLE_TAP_ZOOM)
})
