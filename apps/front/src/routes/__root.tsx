import { createRootRouteWithContext, Outlet } from '@tanstack/react-router'
import type { RouterContext } from '@/app/router'
import { NotFound } from '@/shared/ui/feedback/not-found'
import { PageError } from '@/shared/ui/feedback/page-error'

export const Route = createRootRouteWithContext<RouterContext>()({
  component: Outlet,
  notFoundComponent: NotFound,
  errorComponent: ({ error }) => <PageError error={error} />,
})
