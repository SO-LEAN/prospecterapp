import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

test('note a to-do on the card of a contact and of its organization, tick it off, note the exchange, erase it', async ({
  page,
}) => {
  const organization = `E2E à faire ${Date.now()}`
  const lastName = `À faire ${Date.now()}`
  const toDos = page.getByRole('list', { name: 'À faire' }).getByRole('listitem')
  const quickEntry = page.locator('form').filter({ has: page.getByLabel('Ce qu’il y a à faire') })

  await test.step('a contact of a new organization', async () => {
    await signInOnTheSignInScreen(page)
    await page.goto('/organizations/new')
    await page.getByLabel('Raison sociale').fill(organization)
    await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
    await page.getByRole('button', { name: 'Modifier : Contacts' }).click()
    await page.getByLabel('Prénom').fill('Test')
    await page.getByLabel('Nom', { exact: true }).fill(lastName)
    await page.getByRole('button', { name: 'Enregistrer le contact' }).click()
    await page.getByRole('link', { name: `Test ${lastName}` }).click()
    await expect(page.getByRole('heading', { name: `Test ${lastName}` })).toBeVisible()
  })

  await test.step('« Envoyer mon CV » for tomorrow, by its shortcut', async () => {
    await quickEntry.getByLabel('Ce qu’il y a à faire').fill('Envoyer mon CV')
    await quickEntry.getByRole('button', { name: 'Demain' }).click()
    await quickEntry.getByRole('button', { name: 'Ajouter' }).click()
    await expect(toDos.first()).toContainText('Envoyer mon CV')
  })

  await test.step('ticked off, the exchange it led to is noted by email', async () => {
    await page.getByRole('checkbox', { name: /^Cocher «\sEnvoyer mon CV/ }).check()
    await page.getByRole('button', { name: 'Noter l’échange' }).click()
    await page.getByLabel('E-mail', { exact: true }).check()
    await page.getByLabel('Envoyé', { exact: true }).check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByRole('list', { name: 'Échanges' }).getByRole('listitem').first()).toContainText(
      'E-mail, Envoyé',
    )
    await expect(page.getByText('1 faite')).toBeVisible()
  })

  await test.step('« Préparer la proposition » on the organization, revised, then erased', async () => {
    await page.getByRole('link', { name: organization }).click()
    await expect(page.getByRole('heading', { name: organization })).toBeVisible()
    await quickEntry.getByLabel('Ce qu’il y a à faire').fill('Préparer la proposition')
    await quickEntry.getByRole('button', { name: 'Dans 1 semaine' }).click()
    await quickEntry.getByRole('button', { name: 'Ajouter' }).click()
    await expect(toDos.first()).toContainText('Préparer la proposition')

    await page.getByRole('button', { name: /^Actions sur «\sPréparer la proposition\s»/ }).click()
    await page.getByRole('menuitem', { name: 'Modifier' }).click()
    await page.getByRole('dialog').getByLabel('Ce qu’il y a à faire').fill('Préparer la proposition chiffrée')
    await page.getByRole('dialog').getByRole('button', { name: 'Enregistrer' }).click()
    await expect(toDos.first()).toContainText('Préparer la proposition chiffrée')

    await page.getByRole('button', { name: /^Actions sur «\sPréparer la proposition chiffrée/ }).click()
    await page.getByRole('menuitem', { name: 'Supprimer' }).click()
    await expect(toDos).toHaveCount(0)
  })
})
