import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

/** The account the double of Google connects (docker/nginx/conf.d/google-double.conf). */
const ACCOUNT_OF_THE_DOUBLE = 'agenda@solean-it.io'

const theConnectButton = (page: Page) => page.getByRole('button', { name: 'Connecter', exact: true })

async function disconnect(page: Page) {
  await page.getByRole('button', { name: 'Déconnecter' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Déconnecter' }).click()
  await expect(theConnectButton(page)).toBeVisible()
}

test('a member connects their Google account in the configuration, then disconnects it', async ({ page }) => {
  await test.step('the connectors of the configuration offer to connect Google Calendar', async () => {
    await signInOnTheSignInScreen(page)
    await page.goto('/settings?section=connectors')

    await expect(page.getByText('Google Agenda')).toBeVisible()
    await expect(theConnectButton(page).or(page.getByRole('button', { name: 'Déconnecter' }))).toBeVisible()
    if (await page.getByRole('button', { name: 'Déconnecter' }).isVisible()) {
      await disconnect(page)
    }
  })

  await test.step('Google grants the access and brings the member back, their account connected', async () => {
    await theConnectButton(page).click()

    await expect(page.getByText('Votre compte Google est connecté.')).toBeVisible()
    await expect(page).toHaveURL(/\/settings\?section=connectors/)
    await expect(page.getByText(`Connecté au compte ${ACCOUNT_OF_THE_DOUBLE}`)).toBeVisible()
  })

  await test.step('checked again with Google as the page opens again, still connected', async () => {
    await page.reload()

    await expect(page.getByText(`Connecté au compte ${ACCOUNT_OF_THE_DOUBLE}`)).toBeVisible()
    await expect(page.getByRole('button', { name: 'Reconnecter' })).toBeVisible()
  })

  await test.step('disconnected once confirmed, the card offers to connect again', async () => {
    await disconnect(page)

    await expect(page.getByText('Votre compte Google est déconnecté.')).toBeVisible()
  })
})
