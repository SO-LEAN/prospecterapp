import { expect, test } from './fixtures'
import { SIGN_IN_PAGE } from './sign-in'

const A_HOST_OUTSIDE_THE_STACK = 'https://example.org/'

test.fail('a journey whose page asks a host outside the stack fails, naming the host', async ({ page }) => {
  await page.goto('/')
  await expect(page).toHaveURL(SIGN_IN_PAGE)

  await page.evaluate((url) => fetch(url).catch(() => 'refused'), A_HOST_OUTSIDE_THE_STACK)
})
