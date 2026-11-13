import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { ToDo, ToDoInput, ToDoList } from '../queries'
import { ToDosCard } from './to-dos-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ID = '0190a1c0-0000-7000-8000-000000000001'
const link = (href: string, method = 'GET') => ({ href, method })

const aToDo = (toDo: Partial<ToDo>): ToDo => ({
  id: ID,
  text: 'Envoyer mon CV',
  dueOn: '2099-11-14',
  dueAt: null,
  doneAt: null,
  heldBy: { name: { firstName: 'Michel', lastName: 'Maier' }, email: null },
  contact: null,
  _links: {
    self: link(`/api/to-dos/${toDo.id ?? ID}`),
    revise: link(`/api/to-dos/${toDo.id ?? ID}`, 'PATCH'),
    tickOff: link(`/api/to-dos/${toDo.id ?? ID}/done`, 'POST'),
    erase: link(`/api/to-dos/${toDo.id ?? ID}`, 'DELETE'),
  },
  ...toDo,
})

const listOf = (toDos: ToDo[], mayNote = true): ToDoList => ({
  toDos,
  _links: {
    self: link('/api/contacts/x/to-dos'),
    ...(mayNote && { noteToDo: link('/api/contacts/x/to-dos', 'POST') }),
  },
})

async function renderTheCard(
  list: ToDoList,
  onNote: (body: ToDoInput) => Promise<unknown> = async () => undefined,
  withTheirContact = false,
  onTickedOff?: (toDo: ToDo) => void,
  queryClient = new QueryClient(),
) {
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ToDosCard list={list} onNote={onNote} withTheirContact={withTheirContact} onTickedOff={onTickedOff} />
    </QueryClientProvider>,
  )
}

afterEach(() => {
  vi.restoreAllMocks()
  theApi.mockReset()
})

describe('ToDosCard', () => {
  it('shows what is to do with its day and who holds it, those done folded under their count', async () => {
    await renderTheCard(
      listOf([
        aToDo({ id: 'a', text: 'Envoyer mon CV', dueOn: '2099-11-14', dueAt: '08:30' }),
        aToDo({ id: 'b', text: 'Préparer la démo', doneAt: '2026-11-13T09:00:00+00:00' }),
      ]),
    )

    const [toDo] = within(screen.getByRole('list', { name: 'À faire' })).getAllByRole('listitem')
    expect(toDo).toHaveTextContent('Envoyer mon CV')
    expect(toDo).toHaveTextContent('14 nov. 2099, 8h30')
    expect(toDo).toHaveTextContent('par Michel Maier')
    expect(screen.getByText('1 faite')).toBeInTheDocument()
    expect(screen.getByRole('checkbox', { name: 'Décocher « Préparer la démo »' })).toBeChecked()
  })

  it('shows in red a to-do past its day', async () => {
    await renderTheCard(listOf([aToDo({ dueOn: '2020-01-02' })]))

    const overdue = screen.getByText(/^En retard/)
    expect(overdue).toHaveTextContent(/2\sjanv\.\s2020/)
    expect(overdue).toHaveClass('text-destructive')
  })

  it('names the contact of a to-do on the card of an organization', async () => {
    await renderTheCard(
      listOf([aToDo({ contact: { id: 'p', name: { firstName: 'Ada', lastName: 'Lovelace' } } })]),
      undefined,
      true,
    )

    expect(screen.getByRole('listitem')).toHaveTextContent('Ada Lovelace')
  })

  it('notes a to-do in one line, due on the day of a shortcut', async () => {
    const onNote = vi.fn(async () => undefined)
    await renderTheCard(listOf([]), onNote)
    expect(screen.getByText('Rien à faire pour le moment.')).toBeInTheDocument()

    await userEvent.type(screen.getByLabelText('Ce qu’il y a à faire'), 'Relancer pour le devis')
    await userEvent.click(screen.getByRole('button', { name: 'Aujourd’hui' }))
    await userEvent.click(screen.getByRole('button', { name: 'Ajouter' }))

    const today = new Date()
    const day = [today.getFullYear(), today.getMonth() + 1, today.getDate()]
      .map((part) => String(part).padStart(2, '0'))
      .join('-')
    expect(onNote).toHaveBeenCalledWith({ text: 'Relancer pour le devis', dueOn: day, dueAt: null })
  })

  it('refuses a to-do without a text', async () => {
    const onNote = vi.fn(async () => undefined)
    await renderTheCard(listOf([]), onNote)

    await userEvent.click(screen.getByRole('button', { name: 'Ajouter' }))

    expect(onNote).not.toHaveBeenCalled()
    expect(screen.getByText('Ce champ est obligatoire.')).toBeInTheDocument()
  })

  it('offers neither the quick entry nor the menu without their links', async () => {
    await renderTheCard(listOf([aToDo({ _links: { self: link(`/api/to-dos/${ID}`) } })], false))

    expect(screen.queryByRole('button', { name: 'Ajouter' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Actions sur/ })).not.toBeInTheDocument()
    expect(screen.getByRole('checkbox', { name: 'Cocher « Envoyer mon CV »' })).toBeDisabled()
  })

  it('ticks a to-do off, the lists read again, then tells the page, for it to propose noting the exchange', async () => {
    const toDo = aToDo({})
    theApi.mockResolvedValue(Response.json({ ...toDo, doneAt: '2026-11-13T10:00:00+00:00' }))
    const queryClient = new QueryClient()
    const invalidated = vi.spyOn(queryClient, 'invalidateQueries')
    const onTickedOff = vi.fn()
    await renderTheCard(listOf([toDo]), undefined, false, onTickedOff, queryClient)

    await userEvent.click(screen.getByRole('checkbox', { name: 'Cocher « Envoyer mon CV »' }))

    expect(onTickedOff).toHaveBeenCalledWith(toDo)
    const request = theApi.mock.calls[0]?.[0] as Request
    expect([request.method, new URL(request.url).pathname]).toEqual(['POST', `/api/to-dos/${ID}/done`])
    expect(invalidated.mock.calls.map(([filters]) => filters?.queryKey)).toEqual([
      ['get', '/api/contacts/{id}/to-dos'],
      ['get', '/api/organizations/{id}/to-dos'],
      ['get', '/api/me/to-dos'],
    ])
  })

  it('erases a to-do from its menu', async () => {
    theApi.mockResolvedValue(new Response(null, { status: 204 }))
    await renderTheCard(listOf([aToDo({})]))

    await userEvent.click(screen.getByRole('button', { name: 'Actions sur « Envoyer mon CV »' }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Supprimer' }))

    const request = theApi.mock.calls[0]?.[0] as Request
    expect([request.method, new URL(request.url).pathname]).toEqual(['DELETE', `/api/to-dos/${ID}`])
  })
})
