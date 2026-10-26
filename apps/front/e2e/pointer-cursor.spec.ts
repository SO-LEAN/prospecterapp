import { expect, test } from '@playwright/test'
import { SIGN_IN_PAGE, signInOnTheSignInScreen } from './sign-in'

/*
 * The browser gives the hand to the links only: the base rule of `app/styles.css` gives it to every other clickable
 * area, so that neither an upgrade of Tailwind nor a new shadcn component takes it away.
 */
const HAND = 'pointer'
const DISABLED = 'not-allowed'

test('the sign-in button and the labels of its fields have the hand, its title does not', async ({
  page,
}) => {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)

  await expect(page.getByRole('button', { name: 'Se connecter' })).toHaveCSS('cursor', HAND)
  await expect(page.getByRole('heading').first()).not.toHaveCSS('cursor', HAND)
  await expect(page.getByText('Mot de passe', { exact: true }), 'a label focuses its field').toHaveCSS(
    'cursor',
    HAND,
  )
})

test('the buttons, the menus and the lists of choices have the hand, a disabled button does not', async ({
  page,
}) => {
  await signInOnTheSignInScreen(page)

  await test.step('the user menu and its items', async () => {
    const theUserMenu = page.getByRole('button', { name: /menu du compte/ })
    await expect(theUserMenu).toHaveCSS('cursor', HAND)
    await theUserMenu.click()
    await expect(page.getByRole('menuitem').first()).toHaveCSS('cursor', HAND)
    await expect(page.getByRole('menuitemradio').first()).toHaveCSS('cursor', HAND)
    await page.keyboard.press('Escape')
  })

  await test.step('a list of choices and its options, the submit button of an empty form', async () => {
    await page.goto('/organizations/new')
    const theSubmit = page.getByRole('button', { name: 'Commencer à prospecter' })
    await expect(theSubmit).toBeDisabled()
    await expect(theSubmit).toHaveCSS('cursor', DISABLED)

    const theCountries = page.getByRole('combobox').first()
    await expect(theCountries).toHaveCSS('cursor', HAND)
    await theCountries.click()
    await expect(page.getByRole('option').first()).toHaveCSS('cursor', HAND)
    await page.keyboard.press('Escape')

    await expect(page.getByRole('heading', { level: 1 })).not.toHaveCSS('cursor', HAND)
  })
})
