import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

const ACTIVE_ORGANIZATIONS = 'Organisations actives'
const asTheDashboardWritesIt = new Intl.NumberFormat('fr-FR')

/** The figure a card of the dashboard shows above its label. */
function figureOf(page: Page, label: string) {
  return page.getByText(label, { exact: true }).locator('xpath=preceding-sibling::*[1]')
}

async function countOf(page: Page, label: string): Promise<number> {
  return Number(((await figureOf(page, label).textContent()) ?? '').replace(/\D/g, ''))
}

test('an organization started is counted among the active ones of the dashboard', async ({ page }) => {
  await signInOnTheSignInScreen(page)
  const before = await countOf(page, ACTIVE_ORGANIZATIONS)

  await page
    .getByRole('navigation', { name: 'Navigation principale' })
    .getByRole('link', { name: 'Organisations' })
    .click()
  await page.getByRole('link', { name: 'Nouvelle organisation' }).first().click()
  await page.getByLabel('Raison sociale').fill(`E2E counted ${Date.now()}`)
  await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
  await expect(page.getByText(/^Vous prospectez /)).toBeVisible()
  await page.getByRole('link', { name: 'Tableau de bord' }).first().click()

  await expect(figureOf(page, ACTIVE_ORGANIZATIONS), 'counted by the worker, then read again').toHaveText(
    asTheDashboardWritesIt.format(before + 1),
  )
})
