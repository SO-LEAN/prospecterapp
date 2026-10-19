import { expect, test } from '@playwright/test'
import { ASSISTANT, signInOnTheSignInScreen } from './sign-in'

const A_LONG_TEXT =
  'Intéressée par une mission en mars, à recontacter après le salon de novembre pour fixer un rendez-vous'

test('note the exchanges with a contact, find them in its timeline, gone with its organization', async ({
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

  await test.step('without the right to manage personal data, the card asks nothing of its exchanges', async () => {
    const assistant = await browser.newPage()
    const exchangesAsked: string[] = []
    assistant.on('request', (request) => {
      if (request.url().endsWith('/exchanges')) {
        exchangesAsked.push(request.url())
      }
    })
    await signInOnTheSignInScreen(assistant, ASSISTANT)
    await assistant.goto(new URL(page.url()).pathname)
    await expect(assistant.getByText('Données masquées')).toBeVisible()
    await expect(assistant.getByRole('list', { name: 'Échanges' })).toHaveCount(0)
    expect(exchangesAsked).toEqual([])
    await assistant.close()
  })

  await test.step('a received call that did not go through is missed', async () => {
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('Appel', { exact: true }).check()
    await page.getByLabel('L’appel n’a pas abouti').check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(timeline.first()).toContainText('Appel, Manqué')
  })

  await test.step('stopping to prospect the organization takes the contact and its exchanges along', async () => {
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
