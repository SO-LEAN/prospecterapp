import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { ToDo } from '../queries'
import { ToDoBox } from './to-do-box'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ID = '0190a1c0-0000-7000-8000-000000000001'

const aToDo = (toDo: Partial<ToDo> = {}): ToDo => ({
  id: ID,
  text: 'Envoyer mon CV',
  dueOn: '2026-11-13',
  dueAt: null,
  doneAt: null,
  heldBy: { name: { firstName: 'Ada', lastName: 'Lovelace' } },
  contact: null,
  _links: {
    self: { href: `/api/to-dos/${ID}`, method: 'GET' },
    tickOff: { href: `/api/to-dos/${ID}/done`, method: 'POST' },
  },
  ...toDo,
})

function showTheBox(toDo: ToDo) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <ToDoBox toDo={toDo} />
    </QueryClientProvider>,
  )
}

const sent = () =>
  theApi.mock.calls.map(([request]) => [
    (request as Request).method,
    new URL((request as Request).url).pathname,
  ])

afterEach(() => theApi.mockReset())

describe('the box of a to-do', () => {
  it('ticks it off in place', async () => {
    theApi.mockResolvedValue(Response.json({}))
    showTheBox(aToDo())

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    await waitFor(() => expect(sent()).toEqual([['POST', `/api/to-dos/${ID}/done`]]))
  })

  it('shows one done ticked, to untick', async () => {
    theApi.mockResolvedValue(new Response(null, { status: 204 }))
    showTheBox(
      aToDo({
        doneAt: '2026-11-13T09:00:00+01:00',
        _links: {
          self: { href: `/api/to-dos/${ID}`, method: 'GET' },
          untick: { href: `/api/to-dos/${ID}/done`, method: 'DELETE' },
        },
      }),
    )

    const box = screen.getByRole('checkbox', { name: /^Décocher/ })
    expect(box).toBeChecked()
    await userEvent.click(box)

    await waitFor(() => expect(sent()).toEqual([['DELETE', `/api/to-dos/${ID}/done`]]))
  })

  it('stays as it is without the link of the gesture', () => {
    showTheBox(aToDo({ _links: { self: { href: `/api/to-dos/${ID}`, method: 'GET' } } }))

    expect(screen.getByRole('checkbox')).toBeDisabled()
  })
})
