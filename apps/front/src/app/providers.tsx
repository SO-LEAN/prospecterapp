import { QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { RouterProvider } from '@tanstack/react-router'
import type { User } from 'oidc-client-ts'
import { useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { type AuthContextProps, AuthProvider, useAuth } from 'react-oidc-context'
import { type SigninState, userManager } from '@/shared/auth/user-manager'
import { useThemeClass } from '@/shared/stores/preferences'
import { FullPageMessage } from '@/shared/ui/feedback/full-page-message'
import { Toaster } from '@/shared/ui/sonner'
import { TooltipProvider } from '@/shared/ui/tooltip'
import { queryClient } from './query-client'
import { router } from './router'

function onSigninCallback(user: User | undefined) {
  const { returnTo } = (user?.state ?? {}) as SigninState
  const target = returnTo ? new URL(returnTo) : null
  void router.navigate({ to: target ? target.pathname + target.search : '/', replace: true })
}

function useRouteGuardsRerunOnSignInOrOut(auth: AuthContextProps) {
  const wasAuthenticated = useRef(auth.isAuthenticated)

  useEffect(() => {
    if (!auth.isLoading && wasAuthenticated.current !== auth.isAuthenticated) {
      wasAuthenticated.current = auth.isAuthenticated
      void router.invalidate()
    }
  }, [auth.isAuthenticated, auth.isLoading])
}

const isReadingTheStoredSession = (auth: AuthContextProps) =>
  auth.isLoading && !window.location.pathname.startsWith('/auth/callback')

function InnerApp() {
  const auth = useAuth()
  const { t } = useTranslation()
  useThemeClass()

  useRouteGuardsRerunOnSignInOrOut(auth)

  if (isReadingTheStoredSession(auth)) {
    return <FullPageMessage loading title={t('common.loading')} />
  }

  return <RouterProvider router={router} context={{ auth }} />
}

export function App() {
  return (
    <AuthProvider userManager={userManager} onSigninCallback={onSigninCallback}>
      <QueryClientProvider client={queryClient}>
        <TooltipProvider delayDuration={300}>
          <InnerApp />
          <Toaster richColors closeButton position="bottom-right" />
        </TooltipProvider>
        <ReactQueryDevtools buttonPosition="bottom-right" />
      </QueryClientProvider>
    </AuthProvider>
  )
}
