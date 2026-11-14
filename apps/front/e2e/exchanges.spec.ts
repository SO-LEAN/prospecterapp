import { readFile } from 'node:fs/promises'
import { request } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

const A_LONG_TEXT =
  'Intéressée par une mission en mars, à recontacter après le salon de novembre pour fixer un rendez-vous'
const PHONE = { width: 375, height: 800 }
/** The link asked of the API, then the file read from the bucket: two requests, a function of the API maybe started. */
const DOWNLOADED_WITHIN_MS = 15_000
/** The file is sent once the exchange is noted, while the timeline shows it, which is read again afterwards. */
const JOINED_WITHIN_MS = 15_000
/** A dozen screens and files beside the reactions of stopping to prospect, which run together: longer than a journey. */
const LONGER_THAN_A_JOURNEY_MS = 60_000

test('a file joined to an exchange is downloaded as it was sent, never read signed out, then removed', async ({
  page,
}) => {
  test.setTimeout(LONGER_THAN_A_JOURNEY_MS)
  const organization = `E2E pièce jointe ${Date.now()}`
  const lastName = `Pièce jointe ${Date.now()}`
  const contents = `Cahier des charges ${Date.now()}`
  const timeline = page.getByRole('list', { name: 'Échanges' }).getByRole('listitem')
  const detail = page.getByRole('dialog', { name: 'Échange' })
  const file = detail.getByRole('link', { name: 'Télécharger cahier-des-charges.txt' })

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

  await test.step('an email received with a long text, a file joined while noting it', async () => {
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('E-mail', { exact: true }).check()
    await page.getByLabel(/^Texte/).fill(A_LONG_TEXT)
    await page.getByLabel('choisissez-les').setInputFiles({
      name: 'cahier-des-charges.txt',
      mimeType: 'text/plain',
      buffer: Buffer.from(contents),
    })
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await page.getByRole('button', { name: 'Plus tard' }).click()
    await expect(timeline.first()).toContainText('1 pièce jointe', { timeout: JOINED_WITHIN_MS })
  })

  await test.step('on a phone, the long text is cut and the card keeps to the width of the screen', async () => {
    await page.setViewportSize(PHONE)
    await expect(timeline.first()).toContainText('E-mail, Reçu')
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
    expect(overflow).toBeLessThanOrEqual(0)
    await page.setViewportSize({ width: 1280, height: 720 })
  })

  await test.step('downloaded from its detail as it was sent', async () => {
    await timeline.first().getByRole('button').click()
    const downloaded = page.waitForEvent('download', { timeout: DOWNLOADED_WITHIN_MS })
    await file.click()
    const download = await downloaded
    expect(download.suggestedFilename()).toBe('cahier-des-charges.txt')
    expect(await readFile((await download.path()) ?? '', 'utf8')).toBe(contents)
  })

  await test.step('never read signed out, then removed', async () => {
    const signedOut = await request.newContext({ ignoreHTTPSErrors: true })
    expect((await signedOut.get((await file.getAttribute('href')) ?? '')).status()).toBe(401)
    await signedOut.dispose()

    await detail.getByRole('button', { name: 'Retirer cahier-des-charges.txt' }).click()
    await expect(file).toBeHidden()
    await page.keyboard.press('Escape')
    await expect(timeline.first()).not.toContainText('pièce jointe')
  })

  await test.step('stopping to prospect the organization takes the contact and its exchange along', async () => {
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
