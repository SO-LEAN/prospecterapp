import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

function mapOf(page: Page) {
  return page.locator('.maplibregl-map')
}

async function theDashboardWithItsMap(page: Page) {
  await signInOnTheSignInScreen(page)
  await expect(page.locator('.maplibregl-marker').first()).toBeVisible()
}

test('the controls of the map are announced in French', async ({ page }) => {
  await theDashboardWithItsMap(page)
  const map = mapOf(page)

  await expect(map.getByRole('region', { name: 'Carte' })).toBeVisible()
  await expect(map.getByRole('button', { name: 'Zoomer', exact: true })).toBeVisible()
  await expect(map.getByRole('button', { name: 'Dézoomer' })).toBeVisible()
  await expect(map.locator('summary.maplibregl-ctrl-attrib-button')).toHaveAccessibleName(
    'Afficher ou masquer les crédits de la carte',
  )
})

test('scrolling over the map without Ctrl tells, in French, how to zoom', async ({ page }) => {
  await theDashboardWithItsMap(page)
  await mapOf(page).hover()

  await page.mouse.wheel(0, 200)

  const help = page.locator('.maplibregl-cooperative-gesture-screen')
  await expect(help).toHaveClass(/maplibregl-show/)
  await expect(help.locator('.maplibregl-desktop-message')).toHaveText(
    'Ctrl + molette pour zoomer sur la carte',
  )
  await expect(help.locator('.maplibregl-mobile-message')).toHaveText('Deux doigts pour déplacer la carte')
})

test('the cross of the popup of an organization on the map is announced « Fermer »', async ({ page }) => {
  await theDashboardWithItsMap(page)

  await page.locator('.maplibregl-marker button').last().click()

  await expect(page.locator('.maplibregl-popup').getByRole('button', { name: 'Fermer' })).toBeVisible()
})
