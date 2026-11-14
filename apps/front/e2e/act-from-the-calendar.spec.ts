import type { Locator, Page } from '@playwright/test'
import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

/** Six steps, each reading the calendar again after its gesture. */
const LONGER_THAN_A_JOURNEY_MS = 120_000
/** The height of an hour of the grid: 3rem. */
const HOUR_PX = 48

const dayOf = (moment: Date) =>
  `${moment.getFullYear()}-${String(moment.getMonth() + 1).padStart(2, '0')}-${String(moment.getDate()).padStart(2, '0')}`

/** Grabbed at a point of it, then dragged by the mouse down the grid, in steps the grid follows. */
async function dragDown(page: Page, target: Locator, pixels: number, grabbedAt: 'middle' | 'end' = 'middle') {
  const box = await target.boundingBox()
  if (!box) throw new Error('Nothing to drag')
  const x = box.x + box.width / 2
  const y = grabbedAt === 'middle' ? box.y + box.height / 2 : box.y + box.height - 2
  await page.mouse.move(x, y)
  await page.mouse.down()
  await page.mouse.move(x, y + pixels / 2, { steps: 5 })
  await page.mouse.move(x, y + pixels, { steps: 5 })
  await page.mouse.up()
}

test('act from the calendar: an exchange dragged, stretched, opened, a slot planned', async ({ page }) => {
  test.setTimeout(LONGER_THAN_A_JOURNEY_MS)
  const organization = `E2E rendez-vous ${Date.now()}`
  const lastName = `Rendez-vous ${Date.now()}`
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  const theVisio = (hour: string) =>
    page.getByRole('button', { name: `${hour}, Visio avec Test ${lastName}, À venir` })
  const theDay = (day: Date) =>
    page.getByRole('list', { name: new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(day) })
  const dialog = page.getByRole('dialog', { name: 'Échange' })

  await test.step('a video call planned tomorrow at 14h00, a call noted now, with a contact of a new organization', async () => {
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
    await page.getByRole('button', { name: 'Planifier', exact: true }).click()
    await page.getByLabel('Visio', { exact: true }).check()
    await page.getByLabel('Date').fill(dayOf(tomorrow))
    await page.getByLabel('Heure').fill('14:00')
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(page.getByText('Prochain échange : Visio demain 14h00')).toBeVisible()
    await page.getByRole('button', { name: 'Noter un échange' }).click()
    await page.getByLabel('Appel', { exact: true }).check()
    await page.getByRole('button', { name: 'Enregistrer' }).click()
    await page.getByRole('button', { name: 'Plus tard' }).click()
  })

  await test.step('dragged to 16h00 it is moved, then back at 14h00 by its toast', async () => {
    await page.goto(`/calendar?view=day&date=${dayOf(tomorrow)}`)
    await dragDown(page, theVisio('14h00'), 2 * HOUR_PX)
    await expect(theVisio('16h00')).toBeVisible()
    const toast = page.getByRole('listitem').filter({ hasText: 'Échange déplacé' })
    await toast.getByRole('button', { name: 'Annuler' }).click()
    await expect(page.getByText('Déplacement annulé')).toBeVisible()
    await expect(theVisio('14h00')).toBeVisible()
  })

  await test.step('stretched by its end, it lasts an hour', async () => {
    await dragDown(page, theVisio('14h00'), HOUR_PX / 2, 'end')
    await expect(page.getByText('Échange déplacé')).toBeVisible()
    await theVisio('14h00').click()
    await expect(dialog.getByText(/1 h/)).toBeVisible()
  })

  await test.step('opened, it shows the video call and its contact, leads to their card and closes by Échap', async () => {
    await expect(dialog.getByRole('button', { name: 'Déplacer' })).toBeVisible()
    await expect(dialog.getByRole('region', { name: 'Le contact' })).toContainText(`Test ${lastName}`)
    await dialog.getByRole('link', { name: 'Ouvrir la fiche' }).click()
    await expect(page.getByRole('heading', { name: `Test ${lastName}` })).toBeVisible()
    await page.goBack()
    await theVisio('14h00').click()
    await expect(dialog).toBeVisible()
    await page.keyboard.press('Escape')
    await expect(dialog).toBeHidden()
  })

  await test.step('a free slot clicked plans a call there with the contact searched', async () => {
    const column = theDay(tomorrow)
    const box = await column.boundingBox()
    if (!box) throw new Error('No column')
    await page.mouse.click(box.x + box.width / 2, box.y + 10 * HOUR_PX + HOUR_PX / 8)
    const planning = page.getByRole('dialog', { name: 'Planifier' })
    await planning.getByLabel('Chercher un contact ou une organisation').fill(lastName)
    await planning.getByRole('button', { name: `Test ${lastName}` }).click()
    await expect(planning.getByLabel('Heure')).toHaveValue('10:00')
    await planning.getByLabel('Appel', { exact: true }).check()
    await planning.getByRole('button', { name: 'Enregistrer' }).click()
    await expect(
      page.getByRole('button', { name: `10h00, Appel avec Test ${lastName}, À venir` }),
    ).toBeVisible()
  })

  await test.step('the call noted, in the past, is not dragged', async () => {
    await page.goto(`/calendar?view=day&date=${dayOf(new Date())}`)
    const theCall = page.getByRole('button', { name: new RegExp(`Appel avec Test ${lastName}, Fait`) })
    await expect(theCall).not.toHaveAttribute('data-movable')
    const before = await theCall.boundingBox()
    await dragDown(page, theCall, 2 * HOUR_PX)
    await expect(page.getByText('Échange déplacé')).toHaveCount(0)
    await page.keyboard.press('Escape')
    expect((await theCall.boundingBox())?.y).toBe(before?.y)
  })

  await test.step('its organization no longer prospected', async () => {
    await page
      .getByRole('navigation', { name: 'Navigation principale' })
      .getByRole('link', { name: 'Organisations' })
      .click()
    await page.getByRole('link', { name: organization }).click()
    await page.getByRole('button', { name: 'Arrêter de prospecter' }).click()
    await page.getByRole('button', { name: 'Arrêter définitivement' }).click()
    await expect(page.getByRole('heading', { name: 'Organisations' })).toBeVisible()
  })
})
