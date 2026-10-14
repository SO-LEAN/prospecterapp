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

  // back from an organization returns to where it was opened: the filtered list...
  await page.getByRole('link', { name: new RegExp(name) }).click()
  await expect(page.getByRole('heading', { name })).toBeVisible()
  await page.getByRole('button', { name: 'Retour' }).click()
  await expect(page.getByRole('searchbox')).toHaveValue(name)

  // ...or the dashboard, where a map marker opens a quick look first
  await page.getByRole('link', { name: 'Tableau de bord' }).first().click()
  // markers of the same city overlap: dispatch the click to the one of this organization
  await page.getByRole('button', { name, exact: true }).dispatchEvent('click')
  await page.getByRole('link', { name: 'Voir la fiche' }).click()
  await expect(page.getByRole('heading', { name })).toBeVisible()
  // leaving the edit form does not stack the organization page again
  await page.getByRole('link', { name: 'Modifier' }).click()
  await page.getByRole('button', { name: 'Annuler' }).click()
  await expect(page.getByRole('heading', { name })).toBeVisible()
  await page.getByRole('button', { name: 'Retour' }).click()
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()

  await page.getByRole('button', { name: /menu du compte/ }).click()
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
