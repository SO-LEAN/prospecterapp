import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { submitTheSignInScreen } from './sign-in'
import { unique } from './unique'

/**
 * The run of `make e2e`, which opens an account by the console before the journeys: its founder, whose figures no
 * other journey changes, lets this one read them as they were counted (ADR 0122); it runs after the main journey, alone
 * (ADR 0136).
 */
const RUN = process.env.E2E_RUN
const ACTIVE_ORGANIZATIONS = 'Organisations actives'
const asTheDashboardWritesIt = new Intl.NumberFormat('fr-FR')

test.skip(!RUN, 'make e2e opens the account of a founder by the console first')

/** The figure a card of the dashboard shows above its label. */
function figureOf(page: Page, label: string) {
  return page.getByText(label, { exact: true }).locator('xpath=preceding-sibling::*[1]')
}

async function countOf(page: Page, label: string): Promise<number> {
  return Number(((await figureOf(page, label).textContent()) ?? '').replace(/\D/g, ''))
}

test('the founder the console made, their profile written by the reactions, counts the organization they start', async ({
  page,
}) => {
  await test.step('the founder signs in, a member with an empty profile, written by the reactions', async () => {
    await submitTheSignInScreen(page, `founder-${RUN}@e2e.test`)
    await expect(page.getByRole('heading', { name: 'Complétez votre profil' })).toBeVisible()
  })

  await test.step('their name given, the dashboard of their account', async () => {
    await page.getByLabel('Prénom').fill('Ada')
    await page.getByLabel('Nom', { exact: true }).fill('Lovelace')
    await page.getByRole('button', { name: 'Continuer' }).click()
    await expect(page.getByRole('heading', { name: 'Bonjour Ada' })).toBeVisible()
  })

  await test.step('an organization started is counted among the active ones of the dashboard', async () => {
    const before = await countOf(page, ACTIVE_ORGANIZATIONS)
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Organisations' })
      .click()
    await page.getByRole('link', { name: 'Nouvelle organisation' }).first().click()
    await page.getByLabel('Raison sociale').fill(`E2E counted ${unique()}`)
    await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
    await expect(page.getByText(/^Vous prospectez /)).toBeVisible()
    await page.getByRole('link', { name: 'Tableau de bord' }).first().click()

    await expect(figureOf(page, ACTIVE_ORGANIZATIONS), 'counted by the worker, then read again').toHaveText(
      asTheDashboardWritesIt.format(before + 1),
    )
  })
})
