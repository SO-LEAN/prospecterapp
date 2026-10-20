import { expect, type Page, test } from '@playwright/test'
import { signInOnTheSignInScreen } from './sign-in'

const timelineOf = (page: Page) => page.getByRole('list', { name: 'Échanges' }).getByRole('listitem')

test('the timeline of an organization gathers its exchanges and those of its contacts, each with who communicated', async ({
  page,
}) => {
  const organization = `E2E timeline ${Date.now()}`
  const timeline = timelineOf(page)

  await test.step('an organization with two contacts', async () => {
    await signInOnTheSignInScreen(page)
    await page.goto('/organizations/new')
    await page.getByLabel('Raison sociale').fill(organization)
    await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
    for (const lastName of ['Un', 'Deux']) {
      await page.getByRole('button', { name: 'Modifier : Contacts' }).click()
      await page.getByLabel('Prénom').fill('Test')
      await page.getByLabel('Nom', { exact: true }).fill(lastName)
      await page.getByRole('button', { name: 'Enregistrer le contact' }).click()
      await expect(page.getByRole('link', { name: `Test ${lastName}` })).toBeVisible()
    }
    await expect(page.getByText('Aucun échange noté.')).toBeVisible()
  })

  await test.step('from its card, a call received from the organization itself, then an email sent to Test Un', async () => {
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await expect(page.getByLabel(organization, { exact: true })).toBeChecked()
    await page.getByLabel('Appel', { exact: true }).check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText(organization)

    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('Test Un', { exact: true }).check()
    await page.getByLabel('E-mail', { exact: true }).check()
    await page.getByLabel('Envoyé', { exact: true }).check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText('Test Un')
  })

  await test.step('from the card of Test Deux, a text message received', async () => {
    await page.getByRole('link', { name: 'Test Deux' }).click()
    await expect(page.getByRole('heading', { name: 'Test Deux' })).toBeVisible()
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('SMS', { exact: true }).check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline).toHaveCount(1)
    await expect(timeline.first()).toContainText('SMS, Reçu')
  })

  await test.step('the timeline of the organization shows the three, the most recent first, with who communicated', async () => {
    await page.getByRole('link', { name: organization }).click()
    await expect(page.getByRole('heading', { name: organization })).toBeVisible()
    await expect(timeline).toHaveCount(3)
    await expect(timeline.nth(0)).toContainText('Test Deux')
    await expect(timeline.nth(0)).toContainText('SMS, Reçu')
    await expect(timeline.nth(1)).toContainText('Test Un')
    await expect(timeline.nth(1)).toContainText('E-mail, Envoyé')
    await expect(timeline.nth(2)).toContainText(organization)
    await expect(timeline.nth(2)).toContainText('Appel, Reçu')
  })

  await test.step('the timeline of Test Un shows its email only', async () => {
    await page.getByRole('link', { name: 'Test Un' }).click()
    await expect(page.getByRole('heading', { name: 'Test Un' })).toBeVisible()
    await expect(timeline).toHaveCount(1)
    await expect(timeline.first()).toContainText('E-mail, Envoyé')
  })

  await test.step('stopping to prospect the organization takes everything along', async () => {
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
