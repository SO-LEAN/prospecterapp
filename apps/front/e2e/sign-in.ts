import { expect, type Page } from '@playwright/test'

export const SIGN_IN_PAGE = /local\.prospecter\.io\/auth\/sign-in/

export async function signInOnTheSignInScreen(page: Page) {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)
  await page.getByLabel('E-mail').fill('prospector@solean-it.io')
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}
