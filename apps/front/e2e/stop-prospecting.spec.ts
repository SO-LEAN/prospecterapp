import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

/** More exchanges than one transaction could erase along with the organization, some three writes each. */
const MANY_EXCHANGES = 36
const API = 'https://api.local.prospecter.io'

/** The access token the SPA keeps in the session of its tab (`shared/auth/session.ts`). */
function accessTokenOf(page: Page): Promise<string> {
  return page.evaluate(() => {
    const key = Object.keys(sessionStorage).find((name) => name.endsWith('.accessToken'))
    if (!key) {
      throw new Error('No access token in the session: is the member signed in?')
    }
    return sessionStorage.getItem(key) ?? ''
  })
}

test('stopping to prospect an organization of many exchanges erases each in a reaction of its own', async ({
  page,
}) => {
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
    authorization = `Bearer ${await accessTokenOf(page)}`
    for (let exchange = 0; exchange < MANY_EXCHANGES; exchange += 1) {
      const noted = await page.request.post(`${API}/api/organizations/${id}/exchanges`, {
        headers: { authorization },
        data: { channel: 'call', direction: 'inbound' },
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
    const erased = async () => {
      const answers = await Promise.all(
        exchanges.map((exchange) => page.request.get(exchange, { headers: { authorization } })),
      )
      return answers.every((answer) => answer.status() === 404)
    }

    await expect.poll(erased, { timeout: 30_000 }).toBe(true)
  })
})
