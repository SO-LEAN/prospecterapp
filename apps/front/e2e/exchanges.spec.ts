import { readFile } from 'node:fs/promises'
import { expect, request, test } from '@playwright/test'
import { ASSISTANT, signInOnTheSignInScreen } from './sign-in'

const A_LONG_TEXT =
  'Intéressée par une mission en mars, à recontacter après le salon de novembre pour fixer un rendez-vous'
const A_CONFORM_TEXT = 'La contactée est intéressée par une mission en mars'

test('note the exchanges with a contact and their files, find them in its timeline, gone with its organization', async ({
  page,
  browser,
}) => {
  const organization = `E2E échanges ${Date.now()}`
  const lastName = `Échange ${Date.now()}`
  const timeline = page.getByRole('list', { name: 'Échanges' }).getByRole('listitem')

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
    await expect(page.getByText('Aucun échange noté.')).toBeVisible()
  })

  await test.step('an outgoing call of yesterday, then a LinkedIn message received now at the top', async () => {
    const yesterday = new Date(Date.now() - 24 * 3600_000 - new Date().getTimezoneOffset() * 60_000)
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('Appel', { exact: true }).check()
    await page.getByLabel('Envoyé', { exact: true }).check()
    await page.getByLabel('Moment').fill(yesterday.toISOString().slice(0, 16))
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText('Appel, Envoyé')

    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('LinkedIn', { exact: true }).check()
    await page.getByLabel(/^Texte/).fill(A_LONG_TEXT)
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText('LinkedIn, Reçu')
    await expect(timeline.first()).toContainText(A_LONG_TEXT)
    await expect(timeline.nth(1)).toContainText('Appel, Envoyé')
  })

  await test.step('on a phone, the long text is cut and the card keeps to the width of the screen', async () => {
    await page.setViewportSize({ width: 375, height: 800 })
    await expect(timeline.first()).toContainText('LinkedIn, Reçu')
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
    expect(overflow).toBeLessThanOrEqual(0)
    await page.setViewportSize({ width: 1280, height: 720 })
  })

  await test.step('without the right to manage personal data, the timeline is read, its texts flagged, and only a text is corrected', async () => {
    const assistant = await browser.newPage()
    await signInOnTheSignInScreen(assistant, ASSISTANT)
    await assistant.goto(new URL(page.url()).pathname)
    await expect(assistant.getByText('Données masquées')).toBeVisible()
    const itsTimeline = assistant.getByRole('list', { name: 'Échanges' }).getByRole('listitem')
    await expect(itsTimeline.first()).toContainText('Peut contenir des données personnelles')
    await expect(itsTimeline.first()).toContainText(A_LONG_TEXT)
    await expect(assistant.getByRole('button', { name: 'Noter un échange' })).toHaveCount(0)

    await itsTimeline.first().getByRole('button').click()
    const detail = assistant.getByRole('dialog', { name: 'Échange' })
    await expect(detail.getByRole('button', { name: 'Corriger le texte' })).toBeVisible()
    await expect(detail.getByRole('button', { name: 'Corriger', exact: true })).toHaveCount(0)
    await expect(detail.getByRole('button', { name: 'Supprimer' })).toHaveCount(0)
    await detail.getByRole('button', { name: 'Corriger le texte' }).click()
    await detail.getByLabel(/^Texte/).fill(A_CONFORM_TEXT)
    await detail.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(detail).toContainText(A_CONFORM_TEXT)
    await expect(detail).toContainText(/Modifié le .+ par /)
    await assistant.close()
  })

  await test.step('a received call that did not go through is missed', async () => {
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('Appel', { exact: true }).check()
    await page.getByLabel('L’appel n’a pas abouti').check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText('Appel, Manqué')
  })

  await test.step('the detail of an exchange beside the card, corrected, then erased once confirmed', async () => {
    await timeline.nth(1).getByRole('button').click()
    const detail = page.getByRole('dialog', { name: 'Échange' })
    await expect(detail).toContainText(A_CONFORM_TEXT)
    await expect(detail).not.toContainText('Peut contenir des données personnelles')
    await expect(detail).toContainText(/Noté le .+ par /)

    await detail.getByRole('button', { name: 'Corriger' }).click()
    await detail.getByLabel('E-mail', { exact: true }).check()
    await detail.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(detail).toContainText(/Modifié le .+ par /)
    await expect(detail).toContainText('E-mail, Reçu')
    await page.keyboard.press('Escape')
    await expect(timeline.nth(1)).toContainText('E-mail, Reçu')
    await timeline.nth(1).getByRole('button').click()

    await detail.getByRole('button', { name: 'Supprimer' }).click()
    await page.getByRole('button', { name: 'Supprimer l’échange' }).click()
    await expect(detail).toBeHidden()
    await expect(timeline).toHaveCount(2)
    await expect(timeline.nth(1)).toContainText('Appel, Envoyé')
  })

  await test.step('a file joined while noting, downloaded as it was sent, never read signed out, then removed', async () => {
    const contents = `Cahier des charges ${Date.now()}`
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('E-mail', { exact: true }).check()
    await page.getByLabel('choisissez-les').setInputFiles({
      name: 'cahier-des-charges.txt',
      mimeType: 'text/plain',
      buffer: Buffer.from(contents),
    })
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText('1 pièce jointe')

    await timeline.first().getByRole('button').click()
    const detail = page.getByRole('dialog', { name: 'Échange' })
    const file = detail.getByRole('link', { name: 'Télécharger cahier-des-charges.txt' })
    const [download] = await Promise.all([page.waitForEvent('download'), file.click()])
    expect(download.suggestedFilename()).toBe('cahier-des-charges.txt')
    expect(await readFile((await download.path()) ?? '', 'utf8')).toBe(contents)

    const signedOut = await request.newContext({ ignoreHTTPSErrors: true })
    expect((await signedOut.get((await file.getAttribute('href')) ?? '')).status()).toBe(401)
    await signedOut.dispose()

    await detail.getByRole('button', { name: 'Retirer cahier-des-charges.txt' }).click()
    await expect(file).toBeHidden()
    await page.keyboard.press('Escape')
    await expect(timeline.first()).not.toContainText('pièce jointe')
  })

  await test.step('stopping to prospect the organization takes the contact and its exchanges along', async () => {
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
