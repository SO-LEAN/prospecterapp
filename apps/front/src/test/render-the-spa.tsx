import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { createMemoryHistory, createRouter, RouterProvider } from '@tanstack/react-router'
import { render } from '@testing-library/react'
import { Toaster } from 'sonner'
import { PRELOADING } from '@/app/preloading'
import { mutationsToastingTheirRefusals } from '@/app/query-client'
import { routeTree } from '@/app/routeTree.gen'
import { TooltipProvider } from '@/shared/ui/tooltip'

/**
 * The code of every route, which the router otherwise loads on demand, loaded while the test's file loads. Each line
 * it runs once, such as a module's constant, then runs before any test. The mutation then runs every related test
 * against that line's mutants, not only the first test that opened its route (docs/architecture/tests.md).
 */
async function loadTheCodeOfEveryRoute() {
  const router = createRouter({ routeTree, context: { queryClient: new QueryClient() } })
  await Promise.all(Object.values(router.routesById).map((route) => router.loadRouteChunk(route)))
}

await loadTheCodeOfEveryRoute()

/**
 * The SPA on its routes, opened at a path once the router loaded it: its routes, their code and their data, the
 * refusals of its mutations told in toasts and its tooltips as in the app.
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
      <TooltipProvider>
        <RouterProvider router={router} />
        <Toaster />
      </TooltipProvider>
    </QueryClientProvider>,
  )
  return router
}
