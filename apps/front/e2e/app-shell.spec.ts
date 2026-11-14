import { expect, test } from './fixtures'
import { signInOnTheSignInScreen } from './sign-in'

test('signed in, the logo keeps its size in the sidebar and in the mobile header', async ({ page }) => {
  const SIDEBAR_LOGO_SIDE = 32
  const MOBILE_HEADER_LOGO_SIDE = 28
  await signInOnTheSignInScreen(page)

  const sidebarLogo = page.getByRole('link', { name: 'Prospecter', exact: true }).locator('svg')
  await expect(sidebarLogo).toHaveCSS('width', `${SIDEBAR_LOGO_SIDE}px`)

  await page.setViewportSize({ width: 375, height: 812 })
  const mobileHeaderLogo = page.getByRole('banner').locator('svg[viewBox="385 65 300 300"]')
  await expect(mobileHeaderLogo).toHaveCSS('width', `${MOBILE_HEADER_LOGO_SIDE}px`)
})
