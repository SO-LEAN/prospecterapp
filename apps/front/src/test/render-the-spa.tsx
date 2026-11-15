import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { createMemoryHistory, createRouter, RouterProvider } from '@tanstack/react-router'
import { render } from '@testing-library/react'
import { Toaster } from 'sonner'
import { PRELOADING } from '@/app/preloading'
import { mutationsToastingTheirRefusals } from '@/app/query-client'
import { routeTree } from '@/app/routeTree.gen'

/**
 * The SPA on its routes, opened at a path once the router loaded it: its routes, their code and their data, the
 * refusals of its mutations told in toasts as in the app.
 */
export async function renderTheSpaAt(path: string) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
    mutationCache: mutationsToastingTheirRefusals(),
  })
  const router = createRouter({
    routeTree,
    context: { queryClient },
    ...PRELOADING,
    history: createMemoryHistory({ initialEntries: [path] }),
  })
  await router.load()
  render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
      <Toaster />
    </QueryClientProvider>,
  )
  return router
}
