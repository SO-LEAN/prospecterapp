import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

/** Two cards, four to-dos and an exchange, each gesture pending until the lists are read again: longer than a journey. */
const LONGER_THAN_A_JOURNEY_MS = 60_000

test('note a to-do on the card of a contact and of its organization, tick it off, note the exchange, erase it', async ({
  page,
}) => {
  test.setTimeout(LONGER_THAN_A_JOURNEY_MS)
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

  await test.step('the quick entry emptied, « Relancer par e-mail » noted next', async () => {
    await expect(quickEntry.getByLabel('Ce qu’il y a à faire')).toHaveValue('')
    await quickEntry.getByLabel('Ce qu’il y a à faire').fill('Relancer par e-mail')
    await quickEntry.getByRole('button', { name: 'Dans 1 semaine' }).click()
    await quickEntry.getByRole('button', { name: 'Ajouter' }).click()
    await expect(toDos).toHaveCount(2)
  })

  await test.step('emptied again, « Envoyer la plaquette » noted for the day shown', async () => {
    const theDay = quickEntry.getByLabel('Pour le')
    await expect(quickEntry.getByLabel('Ce qu’il y a à faire')).toHaveValue('')
    await quickEntry.getByLabel('Ce qu’il y a à faire').fill('Envoyer la plaquette')
    await quickEntry.getByRole('button', { name: 'Dans 1 semaine' }).click()
    const shown = await theDay.inputValue()
    await quickEntry.getByRole('button', { name: 'Ajouter' }).click()
    await expect(toDos).toHaveCount(3)
    await expect(theDay).not.toHaveValue(shown)
    await expect(toDos.filter({ hasText: 'Envoyer la plaquette' })).toContainText(
      new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(`${shown}T12:00:00`)),
    )
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
    const proposal = toDos.filter({ hasText: 'Préparer la proposition' })
    await expect(proposal).toHaveCount(1)

    await page.getByRole('button', { name: /^Actions sur «\sPréparer la proposition\s»/ }).click()
    await page.getByRole('menuitem', { name: 'Modifier' }).click()
    await page.getByRole('dialog').getByLabel('Ce qu’il y a à faire').fill('Préparer la proposition chiffrée')
    await page.getByRole('dialog').getByRole('button', { name: 'Enregistrer' }).click()
    await expect(proposal).toContainText('Préparer la proposition chiffrée')

    await page.getByRole('button', { name: /^Actions sur «\sPréparer la proposition chiffrée/ }).click()
    await page.getByRole('menuitem', { name: 'Supprimer' }).click()
    await expect(proposal).toHaveCount(0)
  })
})
