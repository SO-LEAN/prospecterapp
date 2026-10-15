import { expect, type Page, test } from '@playwright/test'

const SIGN_IN_PAGE = /local\.prospecter\.io\/auth\/sign-in/

async function signInOnTheSignInScreen(page: Page) {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)
  await page.getByLabel('E-mail').fill('prospector@solean-it.io')
  await page.getByLabel('Mot de passe').fill('password')
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}

test('sign in, start prospecting an organization, change it section by section, find it and sign out', async ({
  page,
}) => {
  const name = `E2E ${Date.now()}`
  const organizationPage = page.getByRole('heading', { name })
  let organizationUrl = ''

  await test.step('sign in', async () => {
    await signInOnTheSignInScreen(page)
    await expect(page).toHaveURL('https://local.prospecter.io/')
  })

  await test.step('start prospecting an organization', async () => {
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Organisations' })
      .click()
    await page.getByRole('link', { name: 'Nouvelle organisation' }).first().click()
    await page.getByLabel('Raison sociale').fill(name)
    await page.getByLabel('E-mail').fill(`contact+${Date.now()}@e2e.test`)
    await page.getByLabel('Ville').fill('Strasbourg')
    await page.getByRole('button', { name: 'Commencer à prospecter' }).click()

    await expect(organizationPage).toBeVisible()
    await expect(page.getByText('Strasbourg')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Supprimer' }), 'no logo to remove').toHaveCount(0)
    await expect(page.getByRole('button', { name: 'Détacher' }), 'no holding to detach from').toHaveCount(0)
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

  await test.step('a section changes alone, the others stay as they were', async () => {
    await page.getByRole('button', { name: 'Modifier : Coordonnées' }).click()
    await page.getByLabel(/^Téléphone/).fill('03 88 00 00 00')
    await page.getByRole('button', { name: 'Enregistrer' }).click()

    await expect(page.getByRole('link', { name: '03 88 00 00 00' })).toBeVisible()
    await expect(organizationPage).toBeVisible()
    await expect(page.getByText('Strasbourg')).toBeVisible()
    organizationUrl = page.url()
  })

  await test.step('leaving a section form, then back, returns to the dashboard', async () => {
    await page.getByRole('button', { name: 'Modifier : Observations' }).click()
    await page.getByRole('button', { name: 'Annuler' }).click()
    await expect(organizationPage).toBeVisible()
    await page.getByRole('button', { name: 'Retour' }).click()
    await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
  })

  await test.step('an organization that is not a UUID is not found', async () => {
    await page.goto('/organizations/42')
    await expect(page.getByText(/introuvable/i).first()).toBeVisible()
    await page.goto('/')
  })

  await test.step('a contact is collected, attached to the organization, removed from it, then erased', async () => {
    const lastName = `Lovelace ${Date.now()}`
    const contact = `Ada ${lastName}`
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Contacts' })
      .click()
    await page.getByRole('link', { name: 'Nouveau contact' }).first().click()
    await page.getByLabel('Prénom').fill('Ada')
    await page.getByLabel('Nom', { exact: true }).fill(lastName)
    await page.getByLabel(/^Fonction/).fill('Directrice commerciale')
    await page.getByLabel('E-mail').fill(`ada.${Date.now()}@acme.test`)
    await page.getByRole('button', { name: 'Enregistrer le contact' }).click()
    await expect(page.getByRole('heading', { name: contact })).toBeVisible()

    await page.goto(organizationUrl)
    await page.getByRole('button', { name: 'Modifier : Contacts' }).click()
    await page.getByLabel('Rechercher un de mes contacts…').fill(lastName)
    await page.getByRole('button', { name: new RegExp(lastName) }).click()
    await expect(page.getByRole('link', { name: contact })).toBeVisible()

    await page.getByRole('button', { name: `Retirer ${contact} des contacts` }).click()
    await expect(page.getByText('Aucun contact rattaché.')).toBeVisible()

    await page.goto('/contacts')
    await page.getByRole('link', { name: new RegExp(lastName) }).click()
    await page.getByRole('button', { name: 'Effacer la fiche' }).click()
    await page.getByRole('button', { name: 'Effacer définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Contacts' })).toBeVisible()
    await expect(page.getByRole('link', { name: new RegExp(lastName) })).toHaveCount(0)
    await page.goto('/')
  })

  await test.step('my account changes section by section', async () => {
    await page.getByRole('link', { name: 'Mon compte' }).first().click()
    await page.getByRole('button', { name: 'Modifier : Nom' }).click()
    await page.getByLabel('Prénom').fill('Paul')
    await page.getByRole('button', { name: 'Enregistrer' }).click()

    await expect(page.getByRole('button', { name: 'Modifier : Nom' })).toBeVisible()
    await expect(page.getByRole('main').getByText('Paul', { exact: true })).toBeVisible()
    await expect(page.getByRole('main').getByText('SOLEAN IT', { exact: true })).toBeVisible()
  })

  await test.step('sign out', async () => {
    await page.getByRole('button', { name: /menu du compte/ }).click()
    await page.getByRole('menuitem', { name: 'Se déconnecter' }).click()
    await expect(page).toHaveURL(SIGN_IN_PAGE)
  })
})

test('rejects a wrong password', async ({ page }) => {
  await page.goto('/')
  await page.getByLabel('E-mail').fill('prospector@solean-it.io')
  await page.getByLabel('Mot de passe').fill('wrong password')
  await page.getByRole('button', { name: 'Se connecter' }).click()

  await expect(page.getByRole('alert')).toHaveText('E-mail ou mot de passe incorrect.')
})
