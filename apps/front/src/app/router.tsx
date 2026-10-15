import type { QueryClient } from '@tanstack/react-query'
import { createRouter } from '@tanstack/react-router'
import type { AuthContextProps } from 'react-oidc-context'
import { queryClient } from './query-client'
import { routeTree } from './routeTree.gen'

export type RouterContext = {
  queryClient: QueryClient
  auth: AuthContextProps
}

export const router = createRouter({
  routeTree,
  context: { queryClient, auth: undefined as unknown as AuthContextProps },
  defaultPreload: 'intent',
  defaultPreloadStaleTime: 0,
  scrollRestoration: true,
})

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
