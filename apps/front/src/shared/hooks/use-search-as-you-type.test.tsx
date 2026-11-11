import {
  createMemoryHistory,
  createRootRoute,
  createRoute,
  createRouter,
  Link,
  Outlet,
  RouterProvider,
  useNavigate,
} from '@tanstack/react-router'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { useSearchAsYouType } from './use-search-as-you-type'

const LONGER_THAN_THE_DEBOUNCE_MS = 400

const waitLongerThanTheDebounce = () =>
  new Promise((resolve) => setTimeout(resolve, LONGER_THAN_THE_DEBOUNCE_MS))

const BABBAGE = '0190a1c0-0000-7000-8000-000000000001'

/** The list of the organizations searched as you type, and an organization whose data come when `arrived` is called. */
function theListAndASlowOrganization() {
  let arrived = () => {}
  const rootRoute = createRootRoute({ component: Outlet })
  const organizationsRoute = createRoute({ getParentRoute: () => rootRoute, path: '/organizations' })
  const listRoute = createRoute({
    getParentRoute: () => organizationsRoute,
    path: '/',
    validateSearch: (search: Record<string, unknown>) => ({
      q: typeof search.q === 'string' ? search.q : '',
    }),
    component: function List() {
      const { q } = listRoute.useSearch()
      const navigate = useNavigate({ from: '/organizations/' })
      const [query, setQuery] = useSearchAsYouType(q, (typed) =>
        navigate({ search: { q: typed }, replace: true }),
      )
      return (
        <>
          <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} />
          <Link to="/organizations/$organizationId" params={{ organizationId: BABBAGE }}>
            Babbage & Co
          </Link>
        </>
      )
    },
  })
  const organizationRoute = createRoute({
    getParentRoute: () => organizationsRoute,
    path: '$organizationId',
    loader: () => new Promise<void>((resolve) => (arrived = resolve)),
    component: () => <h1>Babbage & Co</h1>,
  })
  const router = createRouter({
    routeTree: rootRoute.addChildren([organizationsRoute.addChildren([listRoute, organizationRoute])]),
    history: createMemoryHistory({ initialEntries: ['/organizations'] }),
  })

  return { router, organizationDataArrive: () => arrived() }
}

describe('useSearchAsYouType', () => {
  it('writes the query in the URL once typed', async () => {
    const { router } = theListAndASlowOrganization()
    render(<RouterProvider router={router} />)

    await userEvent.type(await screen.findByRole('searchbox'), 'Babbage')
    await waitLongerThanTheDebounce()

    expect(router.state.location.search).toEqual({ q: 'Babbage' })
  })

  it('does not bring the list back when a link was followed before the query was written', async () => {
    const { router, organizationDataArrive } = theListAndASlowOrganization()
    render(<RouterProvider router={router} />)

    await userEvent.type(await screen.findByRole('searchbox'), 'Babbage')
    await userEvent.click(screen.getByRole('link', { name: 'Babbage & Co' }))
    await waitLongerThanTheDebounce()
    organizationDataArrive()

    expect(await screen.findByRole('heading', { name: 'Babbage & Co' })).toBeInTheDocument()
    expect(router.state.location.pathname).toBe(`/organizations/${BABBAGE}`)
  })
})
