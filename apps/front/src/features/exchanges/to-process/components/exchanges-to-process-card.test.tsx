import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Suspense } from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { queryClient as theAppQueryClient } from '@/app/query-client'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import { UNDO_DELAY_MS } from '../../mutations'
import type { ExchangeDetail } from '../../queries'
import {
  type ExchangesToProcess,
  type ExchangeToProcess,
  exchangesToProcessQuery,
  type ToDoToProcess,
} from '../queries'
import { ExchangesToProcessCard } from './exchanges-to-process-card'
import { ToProcessCount } from './to-process-count'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

/** The toaster follows the system's theme, which jsdom does not provide. */
vi.hoisted(() => {
  window.matchMedia = (query: string) =>
    ({
      matches: false,
      media: query,
      addEventListener: () => undefined,
      removeEventListener: () => undefined,
    }) as unknown as MediaQueryList
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

/** Friday 13 November 2026, 10:00 in the browser's time zone. */
const NOW = new Date(2026, 10, 13, 10, 0)
const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ACME = '0190a1c0-0000-7000-8000-00000000ac00'
const TO_PROCESS = '/api/me/exchanges-to-process'

let lastId = 0
const nextId = () => `0190a1c0-0000-7000-8000-${String(++lastId).padStart(12, '0')}`

/** An ended exchange planned with ACME's Ada Lovelace, handled from the list as the API offers it. */
function ended(channel: ExchangeToProcess['exchange']['channel'], occurredAt: Date): ExchangeToProcess {
  const id = nextId()
  return {
    exchange: {
      id,
      channel,
      direction: 'outbound',
      occurredAt: occurredAt.toISOString(),
      status: 'planned',
      durationInMinutes: 30,
      textFlagged: false,
      textToRedact: false,
      toRedact: false,
      organizationIds: [ACME],
      attachmentCount: 0,
      contactToAttach: false,
      bookedByLink: false,
      createdByAnAI: false,
      _links: {
        self: { href: `/api/exchanges/${id}`, method: 'GET' },
        move: { href: `/api/exchanges/${id}/moment`, method: 'PATCH' },
        markDone: { href: `/api/exchanges/${id}/done`, method: 'POST' },
        markMissed: { href: `/api/exchanges/${id}/missed`, method: 'POST' },
        cancel: { href: `/api/exchanges/${id}/cancellation`, method: 'POST' },
      },
    },
    interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
    organization: {
      id: ACME,
      name: 'ACME',
      organization: { href: `/api/organizations/${ACME}`, method: 'GET' },
    },
  }
}

const theDetail = (item: ExchangeToProcess): ExchangeDetail => ({
  revision: 1,
  ...item.exchange,
  interlocutor: item.interlocutor,
  canceller: null,
  linkAwaited: false,
  text: null,
  noted: { at: NOW.toISOString(), by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  redacted: null,
  attachments: [],
  hiddenAttachments: 0,
  _links: { ...item.exchange._links, interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' } },
})

const toProcess = (
  items: ExchangeToProcess[],
  toDos: ExchangesToProcess['toDos'] = [],
  contactsToAttach: ExchangesToProcess['contactsToAttach'] = [],
): ExchangesToProcess => ({
  items,
  toDos,
  contactsToAttach,
  _links: { self: { href: TO_PROCESS, method: 'GET' } },
})

/** Each exchange's detail, by its path. The list read again, as `readAgain` gives it. An empty timeline for anything else. */
function theApiAnswers(details: Record<string, ExchangeDetail> = {}, readAgain: ExchangeToProcess[] = []) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    if (pathname === TO_PROCESS) return Response.json(toProcess(readAgain))
    return Response.json(details[pathname] ?? { items: [], _links: {} })
  })
}

const sent = (method: string) =>
  theApi.mock.calls
    .map(([request]) => request as Request)
    .filter((request) => request.method === method)
    .map((request) => new URL(request.url).pathname)

/** Grace Hopper, who had a meeting with me and waits to be attached. */
function aContactToAttach(): ExchangesToProcess['contactsToAttach'][number] {
  return {
    id: ADA,
    name: { firstName: 'Grace', lastName: 'Hopper' },
    email: 'grace@acme.fr',
    lastMeetingAt: NOW.toISOString(),
    erasableFrom: new Date(2026, 11, 13).toISOString(),
    _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
  }
}

/** A to-do of mine for Ada Lovelace, to tick off, `daysOverdue` days after its day. */
function aToDoToProcess(text: string, dueOn: string, daysOverdue: number): ToDoToProcess {
  const id = nextId()
  return {
    toDo: {
      id,
      text,
      dueOn,
      dueAt: null,
      doneAt: null,
      heldBy: { name: { firstName: 'Grace', lastName: 'Hopper' } },
      contact: { id: ADA, name: { firstName: 'Ada', lastName: 'Lovelace' } },
      _links: {
        self: { href: `/api/to-dos/${id}`, method: 'GET' },
        tickOff: { href: `/api/to-dos/${id}/done`, method: 'POST' },
      },
    },
    daysOverdue,
  }
}

function aQueryClientWith(
  items: ExchangeToProcess[],
  toDos: ToDoToProcess[] = [],
  contactsToAttach: ExchangesToProcess['contactsToAttach'] = [],
) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY, retry: false } },
    mutationCache: new MutationCache(theAppQueryClient.getMutationCache().config),
  })
  queryClient.setQueryData(exchangesToProcessQuery().queryKey, toProcess(items, toDos, contactsToAttach))
  return queryClient
}

async function showTheCard(items: ExchangeToProcess[], toDos: ToDoToProcess[] = []) {
  await renderInARouter(
    <QueryClientProvider client={aQueryClientWith(items, toDos)}>
      <Suspense>
        <ExchangesToProcessCard
          contactSummary={(id) => <p>{`Résumé du contact ${id}`}</p>}
          toDoLine={({ toDo, daysOverdue }, onTickedOff) => (
            <button type="button" onClick={onTickedOff}>
              {`${toDo.text}, en retard de ${daysOverdue}`}
            </button>
          )}
        />
      </Suspense>
      <Toaster />
    </QueryClientProvider>,
  )
}

/** Lets time run so that the toasts close. The user waits for them. */
function letTimeRun() {
  vi.useFakeTimers({ shouldAdvanceTime: true })
  vi.setSystemTime(NOW)
  return userEvent.setup({ advanceTimers: (delay) => vi.advanceTimersByTime(delay) })
}

const theCard = () => screen.getByText('À traiter').closest('[data-slot="card"]') as HTMLElement

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(NOW)
  theApiAnswers()
})

afterEach(() => {
  vi.useRealTimers()
  vi.restoreAllMocks()
  theApi.mockReset()
})

describe('the exchanges to process on the dashboard', () => {
  it('lists the planned exchanges whose end has passed, the oldest first, with their moment, channel and contact', async () => {
    await showTheCard([
      ended('videoCall', new Date(2026, 10, 10, 14, 0)),
      ended('call', new Date(2026, 10, 13, 9, 0)),
    ])

    const lines = within(theCard()).getAllByRole('listitem')
    expect(lines.map((line) => line.textContent)).toEqual([
      expect.stringMatching(/10 nov\. 2026, 14:00.*Visio.*Ada Lovelace.*ACME/),
      expect.stringMatching(/13 nov\. 2026, 09:00.*Appel.*Ada Lovelace.*ACME/),
    ])
    expect(
      within(lines[0] as HTMLElement)
        .getAllByRole('button')
        .map((button) => button.textContent?.trim()),
    ).toEqual(expect.arrayContaining(['Fait', 'Manqué', 'Annulé', 'Déplacer']))
  })

  it('is not shown when nothing is to process', async () => {
    await showTheCard([])

    expect(screen.queryByText('À traiter')).not.toBeInTheDocument()
  })

  it('marks one done from its report', async () => {
    const call = ended('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}`]: theDetail(call) })
    await showTheCard([call])

    await userEvent.click(screen.getByRole('button', { name: 'Fait' }))

    expect(await within(await screen.findByRole('dialog')).findByText('Compte rendu')).toBeInTheDocument()
  })

  it('moves one from the form of its move', async () => {
    const call = ended('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}`]: theDetail(call) })
    await showTheCard([call])

    await userEvent.click(screen.getByRole('button', { name: 'Déplacer' }))

    expect(
      await within(await screen.findByRole('dialog')).findByText('Déplacer l’échange'),
    ).toBeInTheDocument()
  })

  it('takes a line away at once when marked missed, the gesture sent once its toast no longer takes it back', async () => {
    const user = letTimeRun()
    const call = ended('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}/missed`]: theDetail(call) })
    await showTheCard([call])

    await user.click(screen.getByRole('button', { name: 'Manqué' }))

    expect(await screen.findByText('Échange marqué manqué')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Ada Lovelace/ })).not.toBeInTheDocument()
    expect(sent('POST')).toEqual([])
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))
    await waitFor(() => expect(sent('POST')).toEqual([`/api/exchanges/${call.exchange.id}/missed`]))
  })

  it('cancels one once its toast no longer takes it back', async () => {
    const user = letTimeRun()
    const call = ended('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}/cancellation`]: theDetail(call) })
    await showTheCard([call])

    await user.click(screen.getByRole('button', { name: 'Annulé' }))

    expect(await screen.findByText('Échange annulé')).toBeInTheDocument()
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))
    await waitFor(() => expect(sent('POST')).toEqual([`/api/exchanges/${call.exchange.id}/cancellation`]))
  })

  it('gives a line back when its toast takes the gesture back', async () => {
    const user = letTimeRun()
    await showTheCard([ended('call', new Date(2026, 10, 13, 9, 0))])

    await user.click(screen.getByRole('button', { name: 'Manqué' }))
    /** The earlier tests' toasts are still leaving the toaster that all the tests share. The newest comes first. */
    const toasts = await screen.findAllByText('Échange marqué manqué')
    const toast = toasts[0]?.closest('[data-sonner-toast]') as HTMLElement
    await user.click(within(toast).getByRole('button', { name: 'Annuler' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(screen.getByRole('button', { name: /Ada Lovelace/ })).toBeInTheDocument()
    expect(sent('POST')).toEqual([])
  })

  it('gives a line back when its gesture is refused', async () => {
    const user = letTimeRun()
    const call = ended('call', new Date(2026, 10, 13, 9, 0))
    theApi.mockImplementation(async () =>
      Response.json(
        { type: 'about:blank', title: 'Refused', status: 409, reason: 'notPossibleInItsState' },
        { status: 409, headers: { 'Content-Type': 'application/problem+json' } },
      ),
    )
    await showTheCard([call])

    await user.click(screen.getByRole('button', { name: 'Manqué' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    await waitFor(() => expect(sent('POST')).toEqual([`/api/exchanges/${call.exchange.id}/missed`]))
    expect(await screen.findByRole('button', { name: /Ada Lovelace/ })).toBeInTheDocument()
  })

  it('tells that everything is up to date once the last one is processed', async () => {
    const user = letTimeRun()
    const call = ended('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}/missed`]: theDetail(call) }, [])
    await showTheCard([call])

    await user.click(screen.getByRole('button', { name: 'Manqué' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(await screen.findByText('Tout est à jour 🎉')).toBeInTheDocument()
    await waitFor(() => expect(screen.queryByText('À traiter')).not.toBeInTheDocument())
  })
})

describe('my to-dos to process', () => {
  it('are listed, the most overdue first, each by the line the page gives it, told since when it waits', async () => {
    await showTheCard(
      [],
      [
        aToDoToProcess('Relancer', '2026-11-10', 3),
        aToDoToProcess('Envoyer mon CV', '2026-11-12', 1),
        aToDoToProcess('Préparer la proposition', '2026-11-13', 0),
      ],
    )

    const toDos = within(screen.getByRole('region', { name: 'Actions à faire' })).getAllByRole('listitem')
    expect(toDos.map((toDo) => toDo.textContent)).toEqual([
      'Relancer, en retard de 3',
      'Envoyer mon CV, en retard de 1',
      'Préparer la proposition, en retard de 0',
    ])
  })
})

describe('the count of the exchanges to process in the menu', () => {
  async function showTheCount(items: ExchangeToProcess[], toDos: ToDoToProcess[] = []) {
    await renderInARouter(
      <QueryClientProvider client={aQueryClientWith(items, toDos)}>
        <ToProcessCount />
      </QueryClientProvider>,
    )
  }

  it('counts the contacts to attach among what is left to process (ADR 0146)', async () => {
    await renderInARouter(
      <QueryClientProvider client={aQueryClientWith([], [], [aContactToAttach()])}>
        <ToProcessCount />
        <Suspense>
          <ExchangesToProcessCard
            contactSummary={() => null}
            toDoLine={() => null}
            contactsToAttach={(contacts) => <p>{`${contacts.length} contact à rattacher`}</p>}
          />
        </Suspense>
      </QueryClientProvider>,
    )

    expect(screen.getByText('1 élément à traiter')).toHaveClass('sr-only')
    expect(screen.getByText('1 contact à rattacher')).toBeInTheDocument()
  })

  it('tells how many are left to process', async () => {
    await showTheCount([
      ended('call', new Date(2026, 10, 13, 8, 0)),
      ended('call', new Date(2026, 10, 13, 9, 0)),
    ])

    expect(screen.getByText('2')).toBeInTheDocument()
    expect(screen.getByText('2 éléments à traiter')).toHaveClass('sr-only')
  })

  it('counts the to-dos too', async () => {
    await showTheCount(
      [ended('call', new Date(2026, 10, 13, 8, 0))],
      [aToDoToProcess('Relancer', '2026-11-13', 0)],
    )

    expect(screen.getByText('2 éléments à traiter')).toHaveClass('sr-only')
  })

  it('tells nothing when none is left', async () => {
    await showTheCount([])

    expect(screen.queryByText(/à traiter/)).not.toBeInTheDocument()
  })

  it('reads them again every five minutes', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    vi.setSystemTime(NOW)
    theApiAnswers({}, [ended('call', new Date(2026, 10, 13, 8, 0))])
    await showTheCount([])

    await act(() => vi.advanceTimersByTimeAsync(5 * 60_000))

    expect(await screen.findByText('1 élément à traiter')).toBeInTheDocument()
  })
})
