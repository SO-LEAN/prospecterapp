import type { Page } from '@playwright/test'
import { expect, keepToTheStack, test } from './fixtures'
import { SPA_URL } from './stack'

export const SIGN_IN_PAGE = /prospecter\.localhost(:\d+)?\/auth\/sign-in/

export const PROSPECTOR = 'prospector@solean-it.io'

export async function signInOnTheSignInScreen(page: Page, email = PROSPECTOR) {
  await submitTheSignInScreen(page, email)
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}

/** The header a journey calls the API with, from the access token the SPA keeps in its tab (`shared/auth/session.ts`). */
export async function authorizationOf(page: Page): Promise<string> {
  const token = await page.evaluate(() => {
    const key = Object.keys(sessionStorage).find((name) => name.endsWith('.accessToken'))
    if (!key) {
      throw new Error('No access token in the session: is the member signed in?')
    }
    return sessionStorage.getItem(key) ?? ''
  })
  return `Bearer ${token}`
}

export async function submitTheSignInScreen(page: Page, email: string) {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)
  await page.getByLabel('E-mail').fill(email)
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
}

/** What the SPA keeps in the storage of its tab: the tokens of the member signed in (`shared/auth/session.ts`). */
type StorageOfTheTab = Record<string, string>

/**
 * The member signed in once for the journeys of a file (ADR 0122), on a page of its own: each journey opens its page
 * with their session, given to the storage of its tab before the SPA loads, instead of signing in again on Cognito,
 * once the page shows the menu of the member, as once signed in.
 */
export function signInOncePerFile(email = PROSPECTOR) {
  let session: StorageOfTheTab = {}

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext({ baseURL: SPA_URL, ignoreHTTPSErrors: true, locale: 'fr-FR' })
    await keepToTheStack(context)
    const page = await context.newPage()
    await signInOnTheSignInScreen(page, email)
    session = await page.evaluate(() => ({ ...sessionStorage }))
    await context.close()
  })

  return async function openSignedIn(page: Page, path = '/') {
    await page.addInitScript((kept: StorageOfTheTab) => {
      for (const [key, value] of Object.entries(kept)) sessionStorage.setItem(key, value)
    }, session)
    await page.goto(path)
    await expect(
      page.getByRole('button', { name: /menu du compte/ }),
      'signed in by the session kept',
    ).toBeVisible()
  }
}
