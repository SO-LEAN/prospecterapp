import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
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

function showTheBox(toDo: ToDo, undoable = false) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <ToDoBox toDo={toDo} undoable={undoable} />
      <Toaster />
    </QueryClientProvider>,
  )
}

const SIX_SECONDS = 6000

const UNTICK = { href: `/api/to-dos/${ID}/done`, method: 'DELETE' }

/** The API ticks the to-do off, and returns it done with the links it then carries. */
function theApiTicksItOff(
  links: ToDo['_links'] = { self: { href: `/api/to-dos/${ID}`, method: 'GET' }, untick: UNTICK },
) {
  theApi.mockImplementation(async (input) =>
    (input as Request).method === 'POST'
      ? Response.json(aToDo({ doneAt: '2026-11-13T09:00:00+01:00', _links: links }))
      : new Response(null, { status: 204 }),
  )
}

const theToast = async () =>
  (await screen.findByText('Action faite')).closest('[data-sonner-toast]') as HTMLElement

const sent = () =>
  theApi.mock.calls.map(([request]) => [
    (request as Request).method,
    new URL((request as Request).url).pathname,
  ])

/** Sonner removes a closed toast after its own timer. Once the test is over, that timer would find no window. */
afterEach(async () => {
  act(() => {
    toast.dismiss()
  })
  await waitFor(() => expect(document.querySelector('[data-sonner-toast]')).toBeNull())
  vi.useRealTimers()
  theApi.mockReset()
})

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

  it('offers to take the tick back, when it is undoable', async () => {
    theApiTicksItOff()
    showTheBox(aToDo(), true)

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    expect(within(await theToast()).getByRole('button', { name: 'Annuler' })).toBeInTheDocument()
  })

  it('unticks it from its toast, by that request alone', async () => {
    theApiTicksItOff()
    showTheBox(aToDo(), true)

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))
    await userEvent.click(within(await theToast()).getByRole('button', { name: 'Annuler' }))

    await waitFor(() =>
      expect(sent()).toEqual([
        ['POST', `/api/to-dos/${ID}/done`],
        ['DELETE', `/api/to-dos/${ID}/done`],
      ]),
    )
  })

  it('leaves its toast alone for six seconds, then the to-do stays done', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    const user = userEvent.setup({ advanceTimers: (delay) => vi.advanceTimersByTime(delay) })
    theApiTicksItOff()
    showTheBox(aToDo(), true)

    await user.click(screen.getByRole('checkbox', { name: /^Cocher/ }))
    await theToast()
    await act(() => vi.advanceTimersByTimeAsync(SIX_SECONDS - 500))
    expect(screen.getByText('Action faite')).toBeInTheDocument()
    await act(() => vi.advanceTimersByTimeAsync(1000))

    await waitFor(() => expect(screen.queryByText('Action faite')).not.toBeInTheDocument())
    expect(sent()).toEqual([['POST', `/api/to-dos/${ID}/done`]])
  })

  it('offers no « Annuler » when the to-do done does not carry the link that unticks it', async () => {
    theApiTicksItOff({ self: { href: `/api/to-dos/${ID}`, method: 'GET' } })
    showTheBox(aToDo(), true)

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    expect(within(await theToast()).queryByRole('button', { name: 'Annuler' })).not.toBeInTheDocument()
  })

  it('ticks it off without a toast, when it is not undoable', async () => {
    theApiTicksItOff()
    showTheBox(aToDo())

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    await waitFor(() => expect(sent()).toEqual([['POST', `/api/to-dos/${ID}/done`]]))
    await waitFor(() => expect(screen.getByRole('checkbox')).toBeEnabled())
    expect(screen.queryByText('Action faite')).not.toBeInTheDocument()
  })
})
