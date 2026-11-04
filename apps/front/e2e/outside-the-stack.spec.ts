import { expect, test } from './fixtures'
import { SIGN_IN_PAGE } from './sign-in'

test.fail(
  'a journey whose page fetches from a host outside the stack fails, naming the host',
  async ({ page }) => {
    await page.goto('/')
    await expect(page).toHaveURL(SIGN_IN_PAGE)

    await page.evaluate(() => fetch('https://example.org/').catch(() => 'refused'))
  },
)

test.fail(
  'a journey whose page opens a WebSocket outside the stack fails, naming the host',
  async ({ page }) => {
    await page.goto('/')
    await expect(page).toHaveURL(SIGN_IN_PAGE)

    await page.evaluate(
      () =>
        new Promise((closed) => {
          new WebSocket('wss://example.net/').addEventListener('close', closed)
        }),
    )
  },
)
