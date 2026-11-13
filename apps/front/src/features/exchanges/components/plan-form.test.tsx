import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { overlappingQuery, type PlannedExchanges } from '../queries'
import { PlanForm } from './plan-form'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ROBERT = '0190a1c0-0000-7000-8000-000000000002'

function renderForm(overlapping?: { startsAt: string; durationInMinutes: number; answer: PlannedExchanges }) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  if (overlapping) {
    queryClient.setQueryData(
      overlappingQuery(overlapping.startsAt, overlapping.durationInMinutes).queryKey,
      overlapping.answer,
    )
  }
  const onSubmit = vi.fn().mockResolvedValue(undefined)
  render(
    <QueryClientProvider client={queryClient}>
      <PlanForm
        onSubmit={onSubmit}
        onCancel={vi.fn()}
        interlocutors={{ organization: 'ACME', contacts: [{ id: ROBERT, name: 'Robert Noyce' }] }}
      />
    </QueryClientProvider>,
  )
  return { onSubmit, user: userEvent.setup() }
}

const save = () => screen.getByRole('button', { name: 'Enregistrer' })

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

/** The durations of the overlap checks the form asked the API. */
const checkedDurations = () =>
  theApi.mock.calls
    .map(([request]) => new URL((request as Request).url))
    .filter((url) => url.pathname === '/api/me/planned-exchanges')
    .map((url) => url.searchParams.get('durationInMinutes'))

const duration = () => screen.getByLabelText('Durée')

describe('PlanForm', () => {
  it('plans for tomorrow at 9 with a contact, for an hour, in a few clicks', async () => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date('2026-11-13T10:05'))
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('Robert Noyce'))
    await user.click(screen.getByLabelText('Visio'))
    await user.click(screen.getByRole('button', { name: 'Demain 9h' }))
    await user.click(screen.getByRole('button', { name: '1 h' }))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(
      {
        channel: 'videoCall',
        occurredAt: new Date('2026-11-14T09:00').toISOString(),
        durationInMinutes: 60,
        text: null,
      },
      ROBERT,
    )
  })

  it('starts at the next quarter of an hour, for half an hour, with the organization itself', async () => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date('2026-11-13T10:05'))
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('Appel'))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(
      {
        channel: 'call',
        occurredAt: new Date('2026-11-13T10:15').toISOString(),
        durationInMinutes: 30,
        text: null,
      },
      null,
    )
  })

  it('offers only a call, a meeting or a video call', () => {
    renderForm()

    expect(screen.getByLabelText('Rendez-vous')).toBeInTheDocument()
    expect(screen.queryByLabelText('SMS')).not.toBeInTheDocument()
  })

  it('refuses a moment already past', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('Appel'))
    await user.clear(screen.getByLabelText('Date'))
    await user.type(screen.getByLabelText('Date'), '2020-01-01')
    await user.click(save())

    expect(await screen.findByText('Un échange planifié est dans le futur.')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('warns of an exchange of mine the slot overlaps, without stopping the planning', async () => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date('2026-11-13T13:50'))
    const startsAt = new Date('2026-11-13T14:00').toISOString()
    const { onSubmit, user } = renderForm({
      startsAt,
      durationInMinutes: 30,
      answer: { items: [anOverlap(new Date('2026-11-13T13:45').toISOString())] },
    })

    expect(
      await screen.findByText('Vous avez déjà un rendez-vous avec Ada Lovelace de 13h45 à 14h15.'),
    ).toBeInTheDocument()
    await user.click(screen.getByLabelText('Appel'))
    await user.click(save())
    expect(onSubmit).toHaveBeenCalled()
  })

  it('checks the overlaps only once the duration is typed, not for 12 on the way to 120', async () => {
    theApi.mockImplementation(async () => Response.json({ items: [] }))
    const { user } = renderForm()
    await waitFor(() => expect(checkedDurations()).toEqual(['30']))
    theApi.mockClear()

    await user.clear(duration())
    await user.type(duration(), '120')

    await waitFor(() => expect(checkedDurations()).toEqual(['120']))
    await new Promise((settled) => setTimeout(settled, 400))
    expect(checkedDurations()).toEqual(['120'])
  })

  it('marks the quick duration chosen, none when another one is typed', async () => {
    const { user } = renderForm()
    const anHour = screen.getByRole('button', { name: '1 h' })

    expect(screen.getByRole('button', { name: '30 min' })).toHaveAttribute('aria-pressed', 'true')
    await user.click(anHour)
    expect(anHour).toHaveAttribute('aria-pressed', 'true')
    expect(screen.getByRole('button', { name: '30 min' })).toHaveAttribute('aria-pressed', 'false')

    await user.clear(duration())
    await user.type(duration(), '45')
    for (const quick of ['15 min', '30 min', '1 h', '2 h']) {
      expect(screen.getByRole('button', { name: quick })).toHaveAttribute('aria-pressed', 'false')
    }
  })
})

const anOverlap = (occurredAt: string): PlannedExchanges['items'][number] => ({
  exchange: {
    id: '0190a1c0-0000-7000-8000-000000000010',
    channel: 'meeting',
    direction: 'outbound',
    missed: false,
    occurredAt,
    status: 'planned',
    durationInMinutes: 30,
    text: null,
    textFlagged: false,
    textToMakeConform: false,
    toMakeConform: false,
    organizationIds: [],
    attachmentCount: 0,
    _links: { self: { href: '/api/exchanges/0190a1c0-0000-7000-8000-000000000010', method: 'GET' } },
  },
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
})
