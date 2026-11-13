import { expect, test } from './fixtures'
import { ASSISTANT, signInOnTheSignInScreen } from './sign-in'

test('plan exchanges with a contact and with an organization, find them to come, warned of an overlap', async ({
  page,
  browser,
}) => {
  const organization = `E2E rendez-vous ${Date.now()}`
  const lastName = `Rendez-vous ${Date.now()}`
  const upcoming = page.getByRole('list', { name: 'À venir' }).getByRole('listitem')

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

  await test.step('a video call tomorrow at 9 for an hour, to come and in the header of the card', async () => {
    await page.getByRole('button', { name: 'Planifier' }).click()
    await page.getByLabel('Visio', { exact: true }).check()
    await page.getByRole('button', { name: 'Demain 9h' }).click()
    await page.getByRole('button', { name: '1 h', exact: true }).click()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(upcoming.first()).toContainText('Visio, À venir')
    await expect(upcoming.first()).toContainText('demain 9h00, 1 h')
    await expect(page.getByText('Prochain échange : Visio demain 9h00')).toBeVisible()
  })

  await test.step('its detail says who planned it', async () => {
    await upcoming.first().getByRole('button').click()
    const detail = page.getByRole('dialog', { name: 'Échange' })
    await expect(detail).toContainText(/Planifié le .+ par /)
    await page.keyboard.press('Escape')
    await expect(detail).toBeHidden()
  })

  await test.step('a call tomorrow at 9:30 is warned of the video call, and still planned', async () => {
    await page.getByRole('button', { name: 'Planifier' }).click()
    await page.getByLabel('Appel', { exact: true }).check()
    await page.getByRole('button', { name: 'Demain 9h' }).click()
    await page.getByLabel('Heure').fill('09:30')
    await expect(
      page.getByText(`Vous avez déjà une visio avec Test ${lastName} de 9h00 à 10h00.`),
    ).toBeVisible()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(upcoming).toHaveCount(2)
    await expect(upcoming.nth(1)).toContainText('Appel, À venir')
  })

  await test.step('a moment already past is refused', async () => {
    await page.getByRole('button', { name: 'Planifier' }).click()
    await page.getByLabel('Rendez-vous', { exact: true }).check()
    await page.getByLabel('Date').fill('2020-01-01')
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByText('Un échange planifié est dans le futur.')).toBeVisible()
    await page.getByRole('button', { name: 'Annuler' }).click()
  })

  await test.step('a video call noted afterwards is among the past exchanges', async () => {
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('Visio', { exact: true }).check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await page.getByRole('button', { name: 'Plus tard' }).click()
    await expect(page.getByRole('list', { name: 'Échanges' }).getByRole('listitem').first()).toContainText(
      'Visio, Reçu',
    )
  })

  await test.step('without the right to manage personal data, nothing is planned', async () => {
    const assistant = await browser.newPage()
    await signInOnTheSignInScreen(assistant, ASSISTANT)
    await assistant.goto(new URL(page.url()).pathname)
    await expect(assistant.getByRole('list', { name: 'À venir' }).getByRole('listitem')).toHaveCount(2)
    await expect(assistant.getByRole('button', { name: 'Planifier' })).toHaveCount(0)
    await assistant.close()
  })

  await test.step('a meeting with the organization itself, among what is to come of its card', async () => {
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Planifier' }).click()
    await page.getByLabel(organization, { exact: true }).check()
    await page.getByLabel('Rendez-vous', { exact: true }).check()
    await page.getByRole('button', { name: 'Lundi 9h' }).click()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(upcoming.filter({ hasText: 'Rendez-vous, À venir' })).toHaveCount(1)
    await expect(upcoming).toHaveCount(3)
  })

  await test.step('stopping to prospect the organization takes everything along', async () => {
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
