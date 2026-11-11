import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

const ACTIVE_ORGANIZATIONS = 'Organisations actives'

/** The figure a card of the dashboard shows above its label, its separators of thousands left out. */
async function figureOf(page: Page, label: string): Promise<number> {
  const figure = await page
    .getByText(label, { exact: true })
    .locator('xpath=preceding-sibling::*[1]')
    .textContent()
  return Number((figure ?? '').replace(/\D/g, ''))
}

test('an organization started is counted among the active ones of the dashboard', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  const before = await figureOf(page, ACTIVE_ORGANIZATIONS)

  await page.goto('/organizations/new')
  await page.getByLabel('Raison sociale').fill(`E2E counted ${Date.now()}`)
  await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
  await expect(page.getByText(/^Vous prospectez /)).toBeVisible()

  await expect(async () => {
    await page.goto('/')
    expect(await figureOf(page, ACTIVE_ORGANIZATIONS)).toBe(before + 1)
  }, 'counted by the worker within a moment of the gesture').toPass()
})
