import type { Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { ASSISTANT, signInOnTheSignInScreen } from './sign-in'

/** The journey waits for the time of a call planned two minutes ahead. */
const LONGER_THAN_A_JOURNEY_MS = 240_000
const UNTIL_THE_CALL_IS_DUE_MS = 180_000
const UNTIL_THE_TOAST_IS_GONE_MS = 6_000

/** The date and the time, in the time of the browser, two minutes from now. */
const inTwoMinutes = (page: Page) =>
  page.evaluate(() => {
    const moment = new Date(Date.now() + 2 * 60_000)
    const local = new Date(moment.getTime() - moment.getTimezoneOffset() * 60_000).toISOString()
    return { date: local.slice(0, 10), time: local.slice(11, 16) }
  })

test('handle planned exchanges: moved, cancelled, done once their time has come, then what follows planned', async ({
  page,
  browser,
}) => {
  test.setTimeout(LONGER_THAN_A_JOURNEY_MS)
  const organization = `E2E traiter ${Date.now()}`
  const lastName = `Rendez-vous ${Date.now()}`
  const upcoming = page.getByRole('list', { name: 'À venir' }).getByRole('listitem')
  const past = page.getByRole('list', { name: 'Échanges' }).getByRole('listitem')
  const detail = page.getByRole('dialog', { name: 'Échange' })
  const plan = async (channel: string, choose: () => Promise<void>) => {
    await page.getByRole('button', { name: 'Planifier', exact: true }).click()
    await page.getByLabel(channel, { exact: true }).check()
    await choose()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
  }

  await test.step('a contact with a call in two minutes, a video call tomorrow and a meeting in a week', async () => {
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
    const { date, time } = await inTwoMinutes(page)
    await plan('Appel', async () => {
      await page.getByLabel('Date').fill(date)
      await page.getByLabel('Heure').fill(time)
    })
    await plan('Visio', () => page.getByRole('button', { name: 'Demain 9h' }).click())
    await plan('Rendez-vous', () => page.getByRole('button', { name: 'Dans 1 semaine' }).click())
    await expect(upcoming).toHaveCount(3)
  })

  await test.step('before its time, the call is not done yet', async () => {
    await upcoming.filter({ hasText: 'Appel' }).getByRole('button').click()
    await expect(detail.getByRole('button', { name: 'Déplacer' })).toBeVisible()
    await expect(detail.getByRole('button', { name: 'Fait' })).toHaveCount(0)
    await page.keyboard.press('Escape')
    await expect(detail).toBeHidden()
  })

  await test.step('the video call moved to next week, its duration kept', async () => {
    await upcoming.filter({ hasText: 'Visio' }).getByRole('button').click()
    await detail.getByRole('button', { name: 'Déplacer' }).click()
    await detail.getByRole('button', { name: 'Dans 1 semaine' }).click()
    await detail.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(detail.getByText(/Visio, À venir/)).toBeVisible()
    await page.keyboard.press('Escape')
    await expect(detail).toBeHidden()
    await expect(upcoming.filter({ hasText: 'Visio' })).toContainText('dans 7 jours')
  })

  await test.step('the meeting cancelled by the contact stays struck through among the past exchanges', async () => {
    await upcoming.filter({ hasText: 'Rendez-vous' }).getByRole('button').click()
    await detail.getByRole('button', { name: 'Annuler l’échange' }).click()
    const cancelling = page.getByRole('dialog', { name: /Annuler cet échange/ })
    await cancelling.getByLabel('Le contact').check()
    await cancelling.getByRole('button', { name: 'Annuler l’échange' }).click()
    await expect(page.getByText('Échange annulé')).toBeVisible()
    await expect(detail.getByText('Rendez-vous, Annulé')).toBeVisible({
      timeout: UNTIL_THE_TOAST_IS_GONE_MS + 5_000,
    })
    await page.keyboard.press('Escape')
    await expect(detail).toBeHidden()
    await expect(past.filter({ hasText: 'Rendez-vous, Annulé' })).toHaveCount(1)
    await expect(past.filter({ hasText: 'Annulé par le contact' })).toHaveCount(1)
    await expect(upcoming).toHaveCount(2)
  })

  await test.step('without being its holder, an assistant handles none of them', async () => {
    const assistant = await browser.newPage()
    await signInOnTheSignInScreen(assistant, ASSISTANT)
    await assistant.goto(new URL(page.url()).pathname)
    await assistant
      .getByRole('list', { name: 'À venir' })
      .getByRole('listitem')
      .filter({ hasText: 'Visio' })
      .getByRole('button')
      .click()
    await expect(assistant.getByRole('dialog', { name: 'Échange' }).getByText(/Visio, À venir/)).toBeVisible()
    await expect(assistant.getByRole('button', { name: 'Déplacer' })).toHaveCount(0)
    await expect(assistant.getByRole('button', { name: 'Annuler l’échange' })).toHaveCount(0)
    await assistant.close()
  })

  await test.step('its time come, the call is done with its report, then what follows is planned for tomorrow', async () => {
    await expect(async () => {
      await page.reload()
      await page
        .getByRole('list', { name: /Échanges|À venir/ })
        .getByRole('listitem')
        .filter({ hasText: 'Appel' })
        .first()
        .getByRole('button')
        .click()
      await expect(detail.getByRole('button', { name: 'Fait' })).toBeVisible({ timeout: 2_000 })
    }).toPass({ timeout: UNTIL_THE_CALL_IS_DUE_MS, intervals: [10_000] })
    await detail.getByRole('button', { name: 'Fait' }).click()
    await detail.getByLabel(/Ce qui s’est dit/).fill('Mission de 3 mois en mars, TJM à envoyer')
    await detail.getByRole('button', { name: 'Enregistrer' }).click()
    await detail.getByRole('button', { name: 'Planifier la suite' }).click()
    await expect(detail.getByLabel('Appel', { exact: true })).toBeChecked()
    await detail.getByRole('button', { name: 'Demain 9h' }).click()
    await detail.getByRole('button', { name: 'Enregistrer' }).click()
    await page.keyboard.press('Escape')
    await expect(detail).toBeHidden()
    await expect(past.filter({ hasText: 'Mission de 3 mois en mars' })).toHaveCount(1)
    await expect(upcoming.filter({ hasText: 'Appel, À venir' })).toHaveCount(1)
  })

  await test.step('stopping to prospect the organization takes everything along', async () => {
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
