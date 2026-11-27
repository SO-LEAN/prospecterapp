import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { ToDo } from '../queries'
import { ToDoToProcessLine } from './to-do-to-process-line'

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

const aToDo = (toDo: Partial<ToDo> = {}): ToDo => ({
  id: ID,
  text: 'Envoyer le CV à Nicolas Bonnet',
  dueOn: '2026-11-18',
  dueAt: '11:00',
  doneAt: null,
  heldBy: { name: { firstName: 'Michel', lastName: 'Maier' }, email: null },
  contact: { id: 'c', name: { firstName: 'Nicolas', lastName: 'Bonnet' } },
  _links: {
    self: link(`/api/to-dos/${ID}`),
    revise: link(`/api/to-dos/${ID}`, 'PATCH'),
    tickOff: link(`/api/to-dos/${ID}/done`, 'POST'),
  },
  ...toDo,
})

async function showTheLine(toDo: ToDo, daysOverdue = 0) {
  await renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <ToDoToProcessLine toDo={toDo} daysOverdue={daysOverdue} />
      <Toaster />
    </QueryClientProvider>,
  )
}

/** The to-do once ticked off, which the API answers. It carries the link that unticks it, unless told otherwise. */
const done = (toDo: ToDo, untickable = true): ToDo => ({
  ...toDo,
  doneAt: '2026-11-18T10:00:00+00:00',
  _links: {
    self: link(`/api/to-dos/${ID}`),
    ...(untickable && { untick: link(`/api/to-dos/${ID}/done`, 'DELETE') }),
  },
})

const sent = () =>
  theApi.mock.calls.map(([request]) => [
    (request as Request).method,
    new URL((request as Request).url).pathname,
  ])

const theToast = async () =>
  (await screen.findByText('Action faite')).closest('[data-sonner-toast]') as HTMLElement

const theLine = () =>
  screen.getByText('Envoyer le CV à Nicolas Bonnet').closest('[data-to-do]') as HTMLElement

/** What folds the line away once it is ticked off. */
const theFold = () => theLine().closest('[data-fold]')

/** Sonner removes a closed toast after its own timer. Once the test is over, that timer would find no window. */
afterEach(async () => {
  act(() => {
    toast.dismiss()
  })
  await waitFor(() => expect(document.querySelector('[data-sonner-toast]')).toBeNull())
  theApi.mockReset()
})

describe('a to-do in « À traiter »', () => {
  it('shows its text, its due moment and its contact, then a pill saying it is for today', async () => {
    await showTheLine(aToDo())

    expect(theLine()).toHaveTextContent(
      /^Envoyer le CV à Nicolas Bonnetmer\. 18 nov\. · 11h00 · Nicolas BonnetPour aujourd’hui/,
    )
    expect(theLine()).not.toHaveAttribute('data-late')
  })

  it('names no one for a to-do for the organization itself', async () => {
    await showTheLine(aToDo({ contact: null }))

    expect(theLine()).toHaveTextContent(
      /^Envoyer le CV à Nicolas Bonnetmer\. 18 nov\. · 11h00Pour aujourd’hui/,
    )
  })

  it('says since when it is late, set apart by a stripe', async () => {
    await showTheLine(aToDo({ dueOn: '2026-11-09', dueAt: null }), 4)

    expect(theLine()).toHaveTextContent(/lun\. 9 nov\. · Nicolas BonnetEn retard de 4 jours/)
    expect(theLine()).toHaveAttribute('data-late')
    expect(theLine()).toHaveClass('border-l-4')
  })

  it('is ticked off in place, and a toast says it is done, offering to take the tick back', async () => {
    theApi.mockResolvedValue(Response.json(done(aToDo())))
    await showTheLine(aToDo())

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher.+Envoyer le CV à Nicolas Bonnet/ }))

    expect(sent()).toEqual([['POST', `/api/to-dos/${ID}/done`]])
    const told = await theToast()
    expect(within(told).getByRole('button', { name: 'Annuler' })).toBeInTheDocument()
  })

  it('is struck through, then folds, while its tick is on its way', async () => {
    theApi.mockReturnValue(new Promise(() => {}))
    await showTheLine(aToDo())

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    expect(screen.getByText('Envoyer le CV à Nicolas Bonnet')).toHaveClass('line-through')
    expect(theFold()).toHaveClass('grid-rows-[0fr]')
  })

  it('only fades out once ticked, when the system asks for reduced motion', async () => {
    theApi.mockReturnValue(new Promise(() => {}))
    await showTheLine(aToDo())

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    expect(theFold()).toHaveClass('opacity-0', 'motion-reduce:grid-rows-[1fr]')
  })

  it('is unticked by « Annuler » in its toast, by that request alone', async () => {
    theApi
      .mockResolvedValueOnce(Response.json(done(aToDo())))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
    await showTheLine(aToDo())
    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    await userEvent.click(within(await theToast()).getByRole('button', { name: 'Annuler' }))

    await waitFor(() =>
      expect(sent()).toEqual([
        ['POST', `/api/to-dos/${ID}/done`],
        ['DELETE', `/api/to-dos/${ID}/done`],
      ]),
    )
  })

  it('offers no « Annuler » when the done to-do does not carry the link that unticks it', async () => {
    theApi.mockResolvedValue(Response.json(done(aToDo(), false)))
    await showTheLine(aToDo())

    await userEvent.click(screen.getByRole('checkbox', { name: /^Cocher/ }))

    expect(within(await theToast()).queryByRole('button', { name: 'Annuler' })).not.toBeInTheDocument()
  })

  it('is postponed to tomorrow by « Reporter », its revision the one request sent', async () => {
    theApi.mockResolvedValue(Response.json(aToDo({ dueOn: '2026-11-19' })))
    await showTheLine(aToDo())

    await userEvent.click(screen.getByRole('button', { name: 'Reporter' }))
    const revision = await screen.findByRole('dialog')
    await userEvent.click(within(revision).getByRole('button', { name: 'Demain' }))
    await userEvent.click(within(revision).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
    expect(sent()).toEqual([['PATCH', `/api/to-dos/${ID}`]])
    const [[revised]] = theApi.mock.calls as [[Request]]
    expect(await revised.json()).toEqual({
      text: 'Envoyer le CV à Nicolas Bonnet',
      dueOn: '2026-11-19',
      dueAt: '11:00',
    })
  })

  it('offers no « Reporter » when the to-do does not carry the link that revises it', async () => {
    const { revise: _, ...links } = aToDo()._links
    await showTheLine(aToDo({ _links: links }))

    expect(screen.getByRole('checkbox', { name: /^Cocher/ })).toBeEnabled()
    expect(screen.queryByRole('button', { name: 'Reporter' })).not.toBeInTheDocument()
  })
})
