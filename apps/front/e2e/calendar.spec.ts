import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

/** The exchanges of a stopped organization are erased by the reactions, a second or so later. */
const ERASED_WITHIN_MS = 30_000

const dayOf = (moment: Date) =>
  `${moment.getFullYear()}-${String(moment.getMonth() + 1).padStart(2, '0')}-${String(moment.getDate()).padStart(2, '0')}`

test('find a planned exchange in my calendar, by day and by month, the view in the URL', async ({ page }) => {
  const organization = `E2E calendrier ${Date.now()}`
  const lastName = `Calendrier ${Date.now()}`
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  const theVideoCall = page.getByRole('link', { name: `9h00, Visio avec Test ${lastName}, À venir` })
  const calendarRequests: string[] = []
  page.on('request', (request) => {
    if (new URL(request.url()).pathname === '/api/me/calendar') calendarRequests.push(request.url())
  })

  await test.step('a video call planned tomorrow at 9 with a contact of a new organization', async () => {
    await signInOnTheSignInScreen(page)
    await page.goto('/organizations/new')
    await page.getByLabel('Raison sociale').fill(organization)
    await page.getByRole('button', { name: 'Commencer à prospecter' }).click()
    await page.getByRole('button', { name: 'Modifier : Contacts' }).click()
    await page.getByLabel('Prénom').fill('Test')
    await page.getByLabel('Nom', { exact: true }).fill(lastName)
    await page.getByRole('button', { name: 'Enregistrer le contact' }).click()
    await page.getByRole('link', { name: `Test ${lastName}` }).click()
    await page.getByRole('button', { name: 'Planifier' }).click()
    await page.getByLabel('Visio', { exact: true }).check()
    await page.getByRole('button', { name: 'Demain 9h' }).click()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByText('Prochain échange : Visio demain 9h00')).toBeVisible()
  })

  await test.step('the calendar opens from the main navigation', async () => {
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Calendrier' })
      .click()
    await expect(page.getByRole('heading', { name: 'Calendrier' })).toBeVisible()
  })

  await test.step('the calendar of its day shows it, the days around read ahead', async () => {
    calendarRequests.length = 0
    await page.goto(`/calendar?view=day&date=${dayOf(tomorrow)}`)
    await expect(theVideoCall).toBeVisible()
    await expect.poll(() => new Set(calendarRequests).size).toBe(7)
  })

  await test.step('the month by its key shows it on its day, the view in the URL', async () => {
    await page.keyboard.press('m')
    await expect(page).toHaveURL(/view=month/)
    await expect(theVideoCall).toBeVisible()
  })

  await test.step('the next month, then back to this one by the back button', async () => {
    await page.getByRole('button', { name: 'Suivant' }).click()
    await expect(theVideoCall).toBeHidden()
    await page.goBack()
    await expect(theVideoCall).toBeVisible()
  })

  await test.step('its organization no longer prospected, the calendar no longer shows it', async () => {
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Organisations' })
      .click()
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()

    await page.goto(`/calendar?view=day&date=${dayOf(tomorrow)}`)
    await expect(async () => {
      await page.reload()
      await expect(page.getByRole('link', { name: new RegExp(lastName) })).toHaveCount(0)
    }).toPass({ timeout: ERASED_WITHIN_MS })
  })
})
