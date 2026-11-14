import type { QueryClient } from '@tanstack/react-query'
import { createRouter } from '@tanstack/react-router'
import { PRELOADING } from './preloading'
import { queryClient } from './query-client'
import { routeTree } from './routeTree.gen'

export type RouterContext = {
  queryClient: QueryClient
}

export const router = createRouter({
  routeTree,
  context: { queryClient },
  ...PRELOADING,
  scrollRestoration: true,
})

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
