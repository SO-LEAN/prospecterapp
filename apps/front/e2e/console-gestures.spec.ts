import { expect, test } from './fixtures'
import { submitTheSignInScreen } from './sign-in'

/**
 * The run of `make e2e`, which opens an account and registers a member by the console before the journeys; its
 * founder signs in in `dashboard-figures.spec.ts`.
 */
const RUN = process.env.E2E_RUN

test.skip(!RUN, 'make e2e opens the account of a founder and registers a member by the console first')

test('the member the console registered signs in, a member with an empty profile, written by the reactions', async ({
  page,
}) => {
  await submitTheSignInScreen(page, `member-${RUN}@e2e.test`)

  await expect(page.getByRole('heading', { name: 'Complétez votre profil' })).toBeVisible()
})
