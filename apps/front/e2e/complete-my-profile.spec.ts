import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { NEWCOMER, submitTheSignInScreen } from './sign-in'

const completeMyProfile = (page: Page) => page.getByRole('heading', { name: 'Complétez votre profil' })

test('a member signing in for the first time completes their profile, then prospects', async ({ page }) => {
  await test.step('the member is asked their name, the email they signed in with proposed', async () => {
    await submitTheSignInScreen(page, NEWCOMER)

    await expect(completeMyProfile(page)).toBeVisible()
    await expect(page.getByLabel('E-mail')).toHaveValue(NEWCOMER)
  })

  await test.step('their name given, they land on the home page, named', async () => {
    await page.getByLabel('Prénom').fill('Ada')
    await page.getByLabel('Nom', { exact: true }).fill('Lovelace')
    await page.getByRole('button', { name: 'Continuer' }).click()

    await expect(page.getByRole('heading', { name: 'Bonjour Ada' })).toBeVisible()
    await expect(page).toHaveURL(/local\.prospecter\.io\/$/)
    await expect(page.getByRole('button', { name: 'Ada Lovelace, menu du compte' })).toBeVisible()
  })

  await test.step('their name taken away, the profile is to complete again, as for the next run', async () => {
    await page.goto('/account')
    await page.getByRole('button', { name: 'Modifier : Nom' }).click()
    await page.getByLabel('Prénom').clear()
    await page.getByLabel('Nom', { exact: true }).clear()
    await page.getByRole('button', { name: 'Enregistrer' }).click()

    await expect(completeMyProfile(page)).toBeVisible()
  })
})
