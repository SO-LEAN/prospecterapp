import { expect, type Page } from '@playwright/test'

export const SIGN_IN_PAGE = /local\.prospecter\.io(:\d+)?\/auth\/sign-in/

export const PROSPECTOR = 'prospector@solean-it.io'
/** A prospector without the right to manage personal data. */
export const ASSISTANT = 'assistant@solean-it.io'
/** A prospector whose profile is empty, completed when they sign in. */
export const NEWCOMER = 'newcomer@solean-it.io'

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
