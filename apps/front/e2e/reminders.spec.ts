import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { ASSISTANT, signInOnTheSignInScreen } from './sign-in'

const theSwitch = (page: Page) => page.getByRole('switch', { name: 'Me rappeler mes échanges planifiés' })
const theDelay = (page: Page) => page.getByRole('combobox', { name: 'Me prévenir' })

async function remindMe(page: Page, delay: string) {
  await theDelay(page).click()
  await page.getByRole('option', { name: delay }).click()
  await expect(theDelay(page)).toHaveText(delay)
}

test('a member sets their reminders in the configuration, kept for their account', async ({ page }) => {
  await test.step('the configuration opens from the menu of the avatar, reminders on, ten minutes before', async () => {
    await signInOnTheSignInScreen(page, ASSISTANT)
    await page.getByRole('button', { name: /menu du compte/ }).click()
    await page.getByRole('menuitem', { name: 'Configuration' }).click()

    await expect(page.getByRole('heading', { name: 'Configuration' })).toBeVisible()
    await expect(theSwitch(page)).toBeChecked()
    await expect(theDelay(page)).toHaveText('10 min avant')
  })

  await test.step('half an hour before, saved at once, found again once the page is loaded again', async () => {
    await remindMe(page, '30 min avant')
    await expect(page.getByText('Modification enregistrée').first()).toBeVisible()

    await page.reload()
    await expect(theDelay(page)).toHaveText('30 min avant')
  })

  await test.step('turned off then on again, the delay kept', async () => {
    await theSwitch(page).click()
    await expect(theSwitch(page)).not.toBeChecked()
    await expect(theDelay(page)).toBeDisabled()

    await theSwitch(page).click()
    await expect(theSwitch(page)).toBeChecked()
    await expect(theDelay(page)).toHaveText('30 min avant')
  })

  await test.step('ten minutes before again, as for the next run', async () => {
    await remindMe(page, '10 min avant')
    await page.reload()
    await expect(theDelay(page)).toHaveText('10 min avant')
  })
})
