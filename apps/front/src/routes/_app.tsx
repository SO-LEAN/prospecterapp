import { createFileRoute, Outlet } from '@tanstack/react-router'
import { type SigninState, userManager } from '@/app/auth'
import { AppShell } from '@/app/layout/app-shell'

/**
 * Every page below requires a signed-in user: anonymous visitors go through the OAuth flow and come back.
 * The guard reads the stored session itself: right after the OAuth callback, the React auth state may not be updated yet.
 */
const waitForTheBrowserToLeave = () => new Promise<never>(() => {})

export const Route = createFileRoute('/_app')({
  beforeLoad: async ({ location }) => {
    let user = await userManager.getUser()
    if (user?.expired && user.refresh_token) {
      user = await userManager.signinSilent().catch(() => null)
    }
    if (!user || user.expired) {
      const state: SigninState = { returnTo: window.location.origin + location.href }
      await userManager.signinRedirect({ state })
      await waitForTheBrowserToLeave()
    }
  },
  component: () => (
    <AppShell>
      <Outlet />
    </AppShell>
  ),
})
