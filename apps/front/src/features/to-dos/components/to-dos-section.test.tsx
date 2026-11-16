import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen, waitFor, within } from '@testing-library/react'
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

const inDays = (days: number) => {
  const day = new Date()
  day.setDate(day.getDate() + days)
  return day
}

const dayOf = (date: Date) =>
  [date.getFullYear(), date.getMonth() + 1, date.getDate()]
    .map((part) => String(part).padStart(2, '0'))
    .join('-')

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

  it('says there is nothing to do, the button adding one in its middle, no form shown', async () => {
    await renderTheCard(listOf([]))

    expect(screen.getByText('Aucune action à faire')).toBeInTheDocument()
    expect(screen.getAllByRole('button', { name: 'Ajouter' })).toHaveLength(1)
    expect(screen.queryByLabelText('Ce qu’il y a à faire')).not.toBeInTheDocument()
  })

  it('adds a to-do from the button of its header, in a dialog the text focused, due on the day of a shortcut, by Enter', async () => {
    const onNote = vi.fn(async () => undefined)
    await renderTheCard(listOf([aToDo({})]), onNote)
    expect(screen.queryByLabelText('Ce qu’il y a à faire')).not.toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Ajouter' }))
    const dialog = await screen.findByRole('dialog', { name: 'Nouvelle action à faire' })
    const text = within(dialog).getByLabelText('Ce qu’il y a à faire')
    expect(text).toHaveFocus()
    expect(within(dialog).getByLabelText(/^Pour/)).toHaveValue(dayOf(inDays(1)))
    await userEvent.type(text, 'Relancer pour le devis')
    await userEvent.click(within(dialog).getByRole('button', { name: 'Aujourd’hui' }))
    expect(text).toHaveFocus()
    await userEvent.keyboard('{Enter}')

    expect(onNote).toHaveBeenCalledWith({
      text: 'Relancer pour le devis',
      dueOn: dayOf(inDays(0)),
      dueAt: null,
    })
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
  })

  it('closes the dialog on Escape, adding nothing', async () => {
    const onNote = vi.fn(async () => undefined)
    await renderTheCard(listOf([]), onNote)

    await userEvent.click(screen.getByRole('button', { name: 'Ajouter' }))
    await userEvent.type(await screen.findByLabelText('Ce qu’il y a à faire'), 'Relancer')
    await userEvent.keyboard('{Escape}')

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
    expect(onNote).not.toHaveBeenCalled()
  })

  it('refuses a to-do without a text', async () => {
    const onNote = vi.fn(async () => undefined)
    await renderTheCard(listOf([]), onNote)

    await userEvent.click(screen.getByRole('button', { name: 'Ajouter' }))
    await userEvent.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Ajouter' }))

    expect(onNote).not.toHaveBeenCalled()
    expect(screen.getByText('Ce champ est obligatoire.')).toBeInTheDocument()
  })

  it('adds one and notes another, the dialog kept open, emptied, focused for the next one, noted as shown', async () => {
    const onNote = vi.fn(async () => undefined)
    await renderTheCard(listOf([]), onNote)
    await userEvent.click(screen.getByRole('button', { name: 'Ajouter' }))
    const dialog = await screen.findByRole('dialog')
    const text = () => within(dialog).getByLabelText('Ce qu’il y a à faire')
    const day = () => within(dialog).getByLabelText(/^Pour/)
    const hour = () => within(dialog).getByLabelText(/^À\s*\(facultatif/)
    const addAnother = () => within(dialog).getByRole('button', { name: 'Ajouter et en noter une autre' })

    await userEvent.type(text(), 'Envoyer mon CV')
    await userEvent.type(hour(), '08:30')
    await userEvent.click(addAnother())
    expect(onNote).toHaveBeenLastCalledWith({
      text: 'Envoyer mon CV',
      dueOn: dayOf(inDays(1)),
      dueAt: '08:30',
    })
    expect(screen.getByRole('dialog')).toBeInTheDocument()
    expect(text()).toHaveValue('')
    expect(hour()).toHaveValue('')
    expect(text()).toHaveFocus()

    await userEvent.type(text(), 'Relancer par e-mail')
    await userEvent.click(within(dialog).getByRole('button', { name: 'Dans 1 semaine' }))
    await userEvent.click(addAnother())
    expect(onNote).toHaveBeenLastCalledWith(
      expect.objectContaining({ text: 'Relancer par e-mail', dueOn: dayOf(inDays(7)) }),
    )
    expect(day()).toHaveValue(dayOf(inDays(1)))

    await userEvent.type(text(), 'Envoyer la plaquette{Enter}')
    expect(screen.queryByText('Ce champ est obligatoire.')).not.toBeInTheDocument()
    expect(onNote).toHaveBeenLastCalledWith({
      text: 'Envoyer la plaquette',
      dueOn: dayOf(inDays(1)),
      dueAt: null,
    })
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
  })

  it('offers neither the adding nor the menu without their links', async () => {
    await renderTheCard(listOf([aToDo({ _links: { self: link(`/api/to-dos/${ID}`) } })], false))

    expect(screen.queryByRole('button', { name: 'Ajouter' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Actions sur/ })).not.toBeInTheDocument()
    expect(screen.getByRole('checkbox', { name: 'Cocher « Envoyer mon CV »' })).toBeDisabled()
  })

  it('ticks a to-do off, the lists, the calendar and what is to process read again, then tells the page, for it to propose noting the exchange', async () => {
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
      ['get', '/api/me/calendar'],
      ['get', '/api/me/exchanges-to-process'],
    ])
  })

  it('revises a to-do from its menu, from what it was', async () => {
    theApi.mockResolvedValue(Response.json(aToDo({ text: 'Envoyer mon CV à jour' })))
    await renderTheCard(listOf([aToDo({})]))

    await userEvent.click(screen.getByRole('button', { name: 'Actions sur « Envoyer mon CV »' }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Modifier' }))
    const revision = await screen.findByRole('dialog')
    const text = within(revision).getByLabelText('Ce qu’il y a à faire')
    expect(text).toHaveValue('Envoyer mon CV')
    await userEvent.clear(text)
    await userEvent.type(text, 'Envoyer mon CV à jour')
    await userEvent.click(within(revision).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
    const request = theApi.mock.calls[0]?.[0] as Request
    expect([request.method, new URL(request.url).pathname]).toEqual(['PATCH', `/api/to-dos/${ID}`])
    expect(await request.json()).toMatchObject({ text: 'Envoyer mon CV à jour', dueOn: '2099-11-14' })
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
