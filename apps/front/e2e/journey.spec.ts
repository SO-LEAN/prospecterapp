import { expect, type Page, test } from '@playwright/test'

const SIGN_IN_PAGE = /api\.local\.prospecter\.io\/login/

async function signInOnTheAuthorizationServer(page: Page) {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)
  await page.getByLabel('Identifiant').fill('prospector')
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}

test('sign in, create an organization, find it and sign out', async ({ page }) => {
  const name = `E2E ${Date.now()}`
  const organizationPage = page.getByRole('heading', { name })

  await test.step('sign in', async () => {
    await signInOnTheAuthorizationServer(page)
    await expect(page).toHaveURL('https://local.prospecter.io/')
  })

  await test.step('create an organization', async () => {
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Organisations' })
      .click()
    await page.getByRole('link', { name: 'Nouvelle organisation' }).first().click()
    await page.getByLabel('Raison sociale').fill(name)
    await page.getByLabel('E-mail').fill(`contact+${Date.now()}@e2e.test`)
    await page.getByLabel('Ville').fill('Strasbourg')
    await page.getByRole('button', { name: 'Créer l’organisation' }).click()

    await expect(organizationPage).toBeVisible()
    await expect(page.getByText('Strasbourg')).toBeVisible()
  })

  await test.step('find it in my organizations', async () => {
    await page.getByRole('link', { name: 'Organisations' }).first().click()
    await page.getByRole('searchbox').fill(name)
    await expect(page).toHaveURL(new RegExp(`q=${encodeURIComponent(name).replace(/%20/g, '\\+|%20')}`))
    await expect(page.getByRole('link', { name: new RegExp(name) })).toBeVisible()
  })

  await test.step('back from the organization returns to the filtered list', async () => {
    await page.getByRole('link', { name: new RegExp(name) }).click()
    await expect(organizationPage).toBeVisible()
    await page.getByRole('button', { name: 'Retour' }).click()
    await expect(page.getByRole('searchbox')).toHaveValue(name)
  })

  await test.step('a map marker opens a quick look, then the organization', async () => {
    await page.getByRole('link', { name: 'Tableau de bord' }).first().click()
    await page.getByRole('button', { name, exact: true }).dispatchEvent('click')
    await page.getByRole('link', { name: 'Voir la fiche' }).click()
    await expect(organizationPage).toBeVisible()
  })

  await test.step('leaving the edit form, then back, returns to the dashboard', async () => {
    await page.getByRole('link', { name: 'Modifier' }).click()
    await page.getByRole('button', { name: 'Annuler' }).click()
    await expect(organizationPage).toBeVisible()
    await page.getByRole('button', { name: 'Retour' }).click()
    await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
  })

  await test.step('sign out', async () => {
    await page.getByRole('button', { name: /menu du compte/ }).click()
    await page.getByRole('menuitem', { name: 'Se déconnecter' }).click()
    await expect(page).toHaveURL(SIGN_IN_PAGE)
  })
})

test('rejects a wrong password', async ({ page }) => {
  await page.goto('/')
  await page.getByLabel('Identifiant').fill('prospector')
  await page.getByLabel('Mot de passe').fill('wrong password')
  await page.getByRole('button', { name: 'Se connecter' }).click()

  await expect(page.getByRole('alert')).toHaveText('Identifiant ou mot de passe incorrect.')
})
