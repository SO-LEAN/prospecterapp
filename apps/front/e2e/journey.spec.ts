import { expect, type Page, test } from '@playwright/test'

async function signIn(page: Page) {
  await page.goto('/')
  // the SPA sends anonymous users to the authorization server sign-in page
  await expect(page).toHaveURL(/api\.local\.prospecter\.io\/login/)
  await page.getByLabel('Identifiant').fill('prospector')
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}

test('sign in, create an organization, find it and sign out', async ({ page }) => {
  const name = `E2E ${Date.now()}`

  await signIn(page)
  await expect(page).toHaveURL('https://local.prospecter.io/')

  await page
    .getByRole('navigation', { name: 'Navigation principale' })
    .getByRole('link', { name: 'Organisations' })
    .click()
  await page.getByRole('link', { name: 'Nouvelle organisation' }).first().click()
  await page.getByLabel('Raison sociale').fill(name)
  await page.getByLabel('E-mail').fill(`contact+${Date.now()}@e2e.test`)
  await page.getByLabel('Ville').fill('Strasbourg')
  await page.getByRole('button', { name: 'Créer l’organisation' }).click()

  await expect(page.getByRole('heading', { name })).toBeVisible()
  await expect(page.getByText('Strasbourg')).toBeVisible()

  await page.getByRole('link', { name: 'Organisations' }).first().click()
  await page.getByRole('searchbox').fill(name)
  await expect(page).toHaveURL(new RegExp(`q=${encodeURIComponent(name).replace(/%20/g, '\\+|%20')}`))
  await expect(page.getByRole('link', { name: new RegExp(name) })).toBeVisible()

  await page.getByRole('button', { name: /prospector|Prospector/ }).click()
  await page.getByRole('menuitem', { name: 'Se déconnecter' }).click()
  await expect(page).toHaveURL(/api\.local\.prospecter\.io\/login/)
})

test('rejects a wrong password', async ({ page }) => {
  await page.goto('/')
  await page.getByLabel('Identifiant').fill('prospector')
  await page.getByLabel('Mot de passe').fill('wrong password')
  await page.getByRole('button', { name: 'Se connecter' }).click()

  await expect(page.getByRole('alert')).toHaveText('Identifiant ou mot de passe incorrect.')
})
