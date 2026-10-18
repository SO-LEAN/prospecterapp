import { expect, type Page, test } from '@playwright/test'
import { colorOf, contrastBetween, READABLE_GRAPHIC_CONTRAST, READABLE_TEXT_CONTRAST } from './colors'
import { signInOnTheSignInScreen } from './sign-in'

type ThemeName = 'Clair' | 'Sombre'

const AN_ORGANIZATION = /^\/api\/organizations\/[^/]+$/
const EVERY_LINE = {
  address: { street: '1 place Kléber', postalCode: '67000', city: 'Strasbourg', country: 'FR' },
  email: 'contact@babbage.example',
  phoneNumber: '+33388112233',
}

test.beforeEach(async ({ page }) => {
  await theOrganizationsOpenedWithEveryLine(page)
})

/** The organization opened has an address, an email and a phone, whatever the local data say of it. */
async function theOrganizationsOpenedWithEveryLine(page: Page) {
  await page.route(
    (url) => AN_ORGANIZATION.test(url.pathname),
    async (route) => {
      const response = await route.fetch()
      const organization = await response.json()
      const { address, ...contactDetails } = EVERY_LINE
      await route.fulfill({
        response,
        json: {
          ...organization,
          address,
          contactDetails: { ...organization.contactDetails, ...contactDetails },
        },
      })
    },
  )
}

function popupOf(page: Page) {
  const content = page.locator('.maplibregl-popup-content')
  return {
    content,
    tip: page.locator('.maplibregl-popup-tip'),
    closeButton: page.locator('.maplibregl-popup-close-button'),
    name: content.locator('p').first(),
    address: content.getByText(EVERY_LINE.address.street),
    email: content.getByRole('link', { name: EVERY_LINE.email }),
    phone: content.locator('a[href^="tel:"]'),
    showButton: content.getByRole('link', { name: 'Voir la fiche' }),
  }
}

function userMenuOf(page: Page) {
  return page.getByRole('button', { name: /menu du compte/ })
}

/** Clicks the marker drawn on top: organizations at the same place hide the markers below. */
async function openThePopupOfAnOrganizationOnTheMap(page: Page) {
  await page.locator('.maplibregl-marker button').last().click()
  await expect(popupOf(page).showButton).toBeVisible()
}

async function choose(page: Page, theme: ThemeName) {
  await userMenuOf(page).click()
  await page.getByRole('menuitemradio', { name: theme }).click()
}

/** The background and the text of the floating surfaces of the theme on (the user menu). */
async function floatingSurfaceOf(page: Page) {
  await userMenuOf(page).click()
  const menu = page.locator('[data-slot="dropdown-menu-content"]')
  const surface = { background: await colorOf(menu, 'background-color'), text: await colorOf(menu, 'color') }
  await page.keyboard.press('Escape')
  await expect(menu).toBeHidden()
  return surface
}

async function expectThePopupToLookLikeTheFloatingSurfaces(page: Page) {
  const surface = await floatingSurfaceOf(page)
  const popup = popupOf(page)
  const background = await colorOf(popup.content, 'background-color')

  expect(background, 'the background of the floating surfaces').toEqual(surface.background)
  expect(await colorOf(popup.content, 'color'), 'the text of the floating surfaces').toEqual(surface.text)
  for (const line of ['name', 'address', 'email', 'phone'] as const) {
    expect(
      contrastBetween(await colorOf(popup[line], 'color'), background),
      `the ${line}`,
    ).toBeGreaterThanOrEqual(READABLE_TEXT_CONTRAST)
  }
  expect(
    contrastBetween(
      await colorOf(popup.showButton, 'color'),
      await colorOf(popup.showButton, 'background-color'),
    ),
    'the button to the organization',
  ).toBeGreaterThanOrEqual(READABLE_TEXT_CONTRAST)
  expect(await colorOf(popup.tip, 'border-top-color'), 'the tip').toEqual(background)
  expect(
    contrastBetween(await colorOf(popup.closeButton, 'color'), background),
    'the close button',
  ).toBeGreaterThanOrEqual(READABLE_GRAPHIC_CONTRAST)
}

test('in the dark theme, the popup of an organization on the map is a dark floating surface', async ({
  page,
}) => {
  await signInOnTheSignInScreen(page)
  await choose(page, 'Sombre')
  await openThePopupOfAnOrganizationOnTheMap(page)

  await expectThePopupToLookLikeTheFloatingSurfaces(page)
})

test('in the light theme, MapLibre adds no padding around the content of the popup', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  await choose(page, 'Clair')
  await openThePopupOfAnOrganizationOnTheMap(page)

  await expectThePopupToLookLikeTheFloatingSurfaces(page)
  await expect(popupOf(page).content).toHaveCSS('padding', '0px')
})

test('the popup open follows a change of theme without closing', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  await choose(page, 'Clair')
  await openThePopupOfAnOrganizationOnTheMap(page)

  await choose(page, 'Sombre')

  await expect(page.locator('html'), 'the dark theme on').toHaveClass(/\bdark\b/)
  await expect(popupOf(page).showButton, 'the popup still open').toBeVisible()
  await expectThePopupToLookLikeTheFloatingSurfaces(page)
})

test('with the system theme on a dark system, the popup is dark', async ({ page }) => {
  await page.emulateMedia({ colorScheme: 'dark' })
  await signInOnTheSignInScreen(page)
  await openThePopupOfAnOrganizationOnTheMap(page)

  await expect(page.locator('html'), 'the dark theme on').toHaveClass(/\bdark\b/)
  await expectThePopupToLookLikeTheFloatingSurfaces(page)
})
