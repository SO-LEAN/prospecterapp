import { devices, expect, type Locator, type Page, test } from '@playwright/test'
import { signInOnTheSignInScreen } from './sign-in'

/*
 * Headless Chromium hands the synthesized touches to the page but never scrolls or zooms the page itself: the
 * journeys check what the browsers obey, the viewport and the touch-action of the page, and the gesture events of
 * Safari; how a real phone obeys them is the QA journey's.
 */
const { defaultBrowserType: _, ...aPhone } = devices['Pixel 7']
test.use(aPhone)

const ENLARGED = 2
const PINCH_START = 40
const PINCH_STEPS = 10
const PAGE_SCROLL_ONLY = 'pan-x pan-y'
const NO_PAGE_ZOOM = /maximum-scale=1\b.*user-scalable=no/
const A_LONG_NAME = 'Compagnie Générale des Établissements Michelin et de leurs Filiales Européennes'

async function centerOf(target: Locator) {
  const box = await target.boundingBox()
  if (!box) throw new Error(`${target} is not on the screen`)
  return { x: box.x + box.width / 2, y: box.y + box.height / 2 }
}

/** Two fingers moving apart from the center of the target, as a phone reports them. */
async function pinchToEnlarge(page: Page, target: Locator) {
  const touchScreen = await page.context().newCDPSession(page)
  const { x, y } = await centerOf(target)
  const fingersApart = (spread: number) => [
    { x: x - spread, y, id: 0 },
    { x: x + spread, y, id: 1 },
  ]
  await touchScreen.send('Input.dispatchTouchEvent', {
    type: 'touchStart',
    touchPoints: fingersApart(PINCH_START),
  })
  for (let step = 1; step <= PINCH_STEPS; step++) {
    const spread = PINCH_START * (1 + ((ENLARGED - 1) * step) / PINCH_STEPS)
    await touchScreen.send('Input.dispatchTouchEvent', {
      type: 'touchMove',
      touchPoints: fingersApart(spread),
    })
  }
  await touchScreen.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] })
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

function widthBeyondTheScreen(page: Page) {
  return page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
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

test('on a phone, the page never zooms, on the map or out of it', async ({ page }) => {
  await theDashboardWithItsMap(page)
  const theText = page.getByRole('heading', { name: /Bonjour/ })

  await expect(page.locator('meta[name=viewport]')).toHaveAttribute('content', NO_PAGE_ZOOM)
  await expect(theText).toHaveCSS('touch-action', PAGE_SCROLL_ONLY)
  expect(await safariPinchOn(theText), 'Safari kept from zooming the page').toBe(true)
  expect(await safariPinchOn(mapOf(page)), 'over the map too').toBe(true)
})

test('on a phone, a double tap while scrolling a list does not zoom the page', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  await page.goto('/organizations')

  await expect(page.getByRole('main').getByRole('link').first()).toHaveCSS('touch-action', PAGE_SCROLL_ONLY)
})

/** The organizations listed carry a name too long for a phone, whatever the local data say of them. */
async function organizationsWithALongName(page: Page) {
  await page.route(
    (url) => url.pathname === '/api/organizations',
    async (route) => {
      const response = await route.fetch()
      const slice = await response.json()
      await route.fulfill({
        response,
        json: {
          ...slice,
          items: slice.items.map((organization: object) => ({ ...organization, name: A_LONG_NAME })),
        },
      })
    },
  )
}

test('on a phone, the dashboard and its map fit the width of the screen, whatever the names listed', async ({
  page,
}) => {
  await organizationsWithALongName(page)
  await theDashboardWithItsMap(page)

  await expect(page.getByText(A_LONG_NAME).first()).toBeVisible()
  expect(await widthBeyondTheScreen(page)).toBeLessThanOrEqual(0)
})

test('on a phone, the list of the organizations fits the width of the screen, whatever their names', async ({
  page,
}) => {
  await organizationsWithALongName(page)
  await signInOnTheSignInScreen(page)
  await page.goto('/organizations')

  await expect(page.getByText(A_LONG_NAME).first()).toBeVisible()
  expect(await widthBeyondTheScreen(page)).toBeLessThanOrEqual(0)
})
