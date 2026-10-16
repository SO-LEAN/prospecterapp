import { createFileRoute, Outlet, redirect } from '@tanstack/react-router'
import { AppShell } from '@/app/layout/app-shell'
import { session } from '@/shared/auth/session'

/** Every page below requires a signed-in member: anonymous visitors sign in, then come back. */
export const Route = createFileRoute('/_app')({
  beforeLoad: async ({ location }) => {
    if (!(await session.accessToken())) {
      throw redirect({ to: '/auth/sign-in', search: { returnTo: location.href } })
    }
  },
  component: () => (
    <AppShell>
      <Outlet />
    </AppShell>
  ),
})
