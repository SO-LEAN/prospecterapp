import { expect, type Page } from '@playwright/test'

export const SIGN_IN_PAGE = /local\.prospecter\.io\/auth\/sign-in/

export const PROSPECTOR = 'prospector@solean-it.io'
/** A prospector without the right to manage personal data. */
export const ASSISTANT = 'assistant@solean-it.io'

export async function signInOnTheSignInScreen(page: Page, email = PROSPECTOR) {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)
  await page.getByLabel('E-mail').fill(email)
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}
