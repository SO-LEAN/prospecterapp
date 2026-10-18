import { expect, type Page, test } from '@playwright/test'
import { signInOnTheSignInScreen } from './sign-in'

const OPENSTREETMAP = 'https://www.openstreetmap.org/**'
const LYON = { latitude: 45.764, longitude: 4.8357 }
const TO_COLMAR = 'to=48.0794%2C7.3585'

const STRASBOURG = { latitude: 48.5834, longitude: 7.7456 }
const COLMAR = { latitude: 48.0794, longitude: 7.3585 }
const AN_ORGANIZATION = /^\/api\/organizations\/[^/]+$/

test.beforeEach(async ({ page, context }) => {
  await context.route(OPENSTREETMAP, (route) => route.fulfill({ body: 'OpenStreetMap' }))
  await theOrganizationsOpenedInColmar(page)
})

/** The organization opened placed in Colmar, whatever the local data say of it. */
async function theOrganizationsOpenedInColmar(page: Page) {
  await page.route(
    (url) => AN_ORGANIZATION.test(url.pathname),
    async (route) => {
      const response = await route.fetch()
      await route.fulfill({ response, json: { ...(await response.json()), location: COLMAR } })
    },
  )
}

/** My organization placed where said (nowhere: `null`), the rest of my account as the API gives it. */
async function myOrganizationAt(page: Page, location: typeof STRASBOURG | null) {
  await page.route(
    (url) => url.pathname === '/api/me',
    async (route) => {
      const response = await route.fetch()
      const account = await response.json()
      await route.fulfill({
        response,
        json: { ...account, organization: { ...account.organization, location } },
      })
    },
  )
}

async function openTheRouteToBabbage(page: Page) {
  await page.getByRole('link', { name: 'Organisations' }).first().click()
  await page.getByRole('link', { name: /Babbage & Co/ }).click()
  const route = page.waitForEvent('popup')
  await page.getByRole('link', { name: 'Itinéraire' }).click()
  const tab = await route
  await tab.waitForURL(/openstreetmap/, { waitUntil: 'commit' })
  return tab.url()
}

test('the route starts from my organization', async ({ page }) => {
  await myOrganizationAt(page, STRASBOURG)
  await signInOnTheSignInScreen(page)

  expect(await openTheRouteToBabbage(page)).toContain(`from=48.5834%2C7.7456&${TO_COLMAR}`)
})

test('without the position of my organization, the route starts from the device', async ({
  page,
  context,
}) => {
  await context.grantPermissions(['geolocation'])
  await context.setGeolocation(LYON)
  await myOrganizationAt(page, null)
  await signInOnTheSignInScreen(page)

  expect(await openTheRouteToBabbage(page)).toContain(`from=45.764%2C4.8357&${TO_COLMAR}`)
})

test('without any start, the route has its arrival only', async ({ page }) => {
  await myOrganizationAt(page, null)
  await signInOnTheSignInScreen(page)

  expect(await openTheRouteToBabbage(page)).toMatch(new RegExp(`directions\\?${TO_COLMAR}$`))
})
