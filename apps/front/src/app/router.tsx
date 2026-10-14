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
  // start loading a route (code + data) when its link is hovered or focused
  defaultPreload: 'intent',
  // loaders read the TanStack Query cache, which handles freshness itself
  defaultPreloadStaleTime: 0,
  scrollRestoration: true,
})

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
