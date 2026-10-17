import { createMemoryHistory, createRootRoute, createRouter, RouterProvider } from '@tanstack/react-router'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { PageError } from './page-error'

/** A page whose loading fails the first time, as when the api cannot be reached, then succeeds. */
function renderAPageFailingOnce() {
  let attempts = 0
  const rootRoute = createRootRoute({
    beforeLoad: () => {
      attempts += 1
      if (attempts === 1) {
        throw new TypeError('Failed to fetch')
      }
    },
    component: () => <p>Page loaded</p>,
    errorComponent: ({ error }) => <PageError error={error} />,
  })
  const router = createRouter({ routeTree: rootRoute, history: createMemoryHistory() })
  render(<RouterProvider router={router} />)

  return userEvent.setup()
}

describe('PageError', () => {
  it('says the server cannot be reached and loads the page again on retry', async () => {
    const user = renderAPageFailingOnce()

    expect(
      await screen.findByText('Impossible de joindre le serveur. Vérifiez votre connexion.'),
    ).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Réessayer' }))

    expect(await screen.findByText('Page loaded')).toBeInTheDocument()
  })
})
