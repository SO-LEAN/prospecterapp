import { expect, test } from './fixtures'
import { authorizationOf, signInOnTheSignInScreen } from './sign-in'
import { API_URL as API } from './stack'

/** More exchanges than one transaction could erase along with the organization, some three writes each. */
const MANY_EXCHANGES = 36
/** Noting the exchanges, then a reaction per exchange, each an invocation of the worker, take longer than a journey. */
const LONGER_THAN_A_JOURNEY_MS = 120_000
const ERASED_WITHIN_MS = 60_000

test('stopping to prospect an organization of many exchanges erases each in a reaction of its own', async ({
  page,
}) => {
  test.setTimeout(LONGER_THAN_A_JOURNEY_MS)
  const organization = `E2E many exchanges ${Date.now()}`
  let organizationUrl = ''
  let authorization = ''
  const exchanges: string[] = []

  await test.step('an organization of more than 35 exchanges, noted through the API', async () => {
    await signInOnTheSignInScreen(page)
    await page.goto('/organizations/new')
    await page.getByLabel('Raison sociale').fill(organization)
    await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
    await expect(page.getByRole('heading', { name: organization })).toBeVisible()
    organizationUrl = page.url()

    const id = new URL(organizationUrl).pathname.split('/').pop()
    authorization = await authorizationOf(page)
    for (let exchange = 0; exchange < MANY_EXCHANGES; exchange += 1) {
      const noted = await page.request.post(`${API}/api/organizations/${id}/exchanges`, {
        headers: { authorization },
        data: { exchange: { channel: 'call', direction: 'inbound' } },
      })
      expect(noted.status()).toBe(201)
      exchanges.push(new URL(noted.headers().location ?? '', API).href)
    }
  })

  await test.step('stopping to prospect it succeeds, the organization gone', async () => {
    await page.goto(organizationUrl)
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()

    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
    await expect(page.getByRole('link', { name: organization })).toHaveCount(0)
  })

  await test.step('its exchanges are erased by the reactions, a second or so later', async () => {
    /** One exchange after the other: asked all at once, they would start a function of the API each, beside the reactions. */
    const erased = async () => {
      for (const exchange of exchanges) {
        const answer = await page.request.get(exchange, { headers: { authorization } })
        if (answer.status() !== 404) {
          return false
        }
      }
      return true
    }

    await expect.poll(erased, { timeout: ERASED_WITHIN_MS }).toBe(true)
  })
})
