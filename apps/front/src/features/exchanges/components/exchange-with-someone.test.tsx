import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Timeline } from '../queries'
import { ExchangeWithSomeone, type Gesture } from './exchange-with-someone'
import type { Chosen, InterlocutorSearch } from './with-the-chosen'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ACME = '0190a1c0-0000-7000-8000-000000000002'
const NOTED = '0190a1c0-0000-7000-8000-000000000010'

const ada: Chosen = { kind: 'person', id: ADA, name: 'Ada Lovelace' }
const acme: Chosen = { kind: 'organization', id: ACME, name: 'ACME' }

/** The search the page gives: here, one button per interlocutor it finds. */
const aSearchFinding =
  (...found: Chosen[]): InterlocutorSearch =>
  (onChoose) => (
    <ul>
      {found.map((chosen) => (
        <li key={chosen.id}>
          <button type="button" onClick={() => onChoose(chosen)}>
            {chosen.name}
          </button>
        </li>
      ))}
    </ul>
  )

type Links = Timeline['_links']

/** A timeline with no exchange, which offers the gestures given. */
const aTimelineOffering = (path: string, links: Omit<Links, 'self'>) => ({
  items: [],
  toRedact: 0,
  _links: { self: { href: path, method: 'GET' }, ...links },
})

const OFFERS_ALL = (path: string) => ({
  noteExchange: { href: path, method: 'POST' },
  planExchange: { href: path, method: 'POST' },
})

type Sent = { path: string; body: unknown }

/** The timelines offer what is given, the API logs and plans. Any other reading returns nothing. */
function theApiAnswers(offered: (path: string) => Omit<Links, 'self'> = OFFERS_ALL) {
  const sent: Sent[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const path = new URL(request.url).pathname
    if (request.method === 'POST') {
      sent.push({ path, body: await request.json() })
      return Response.json(aTimelineOffering(path, offered(path)), {
        status: 201,
        headers: { Location: `/api/exchanges/${NOTED}` },
      })
    }
    if (path.endsWith('/exchanges')) return Response.json(aTimelineOffering(path, offered(path)))
    if (path.endsWith('/meeting-place')) return Response.json({ address: null })
    return Response.json({ items: [], _links: { self: { href: path, method: 'GET' } } })
  })
  return sent
}

async function open(gesture: Gesture, onClose = () => {}) {
  await renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <ExchangeWithSomeone
        gesture={gesture}
        onClose={onClose}
        interlocutorSearch={aSearchFinding(ada, acme)}
      />
    </QueryClientProvider>,
  )
  return screen.findByRole('dialog')
}

afterEach(() => theApi.mockReset())

describe('noting an exchange with someone chosen first', () => {
  it('logs it with the organization itself once it is chosen', async () => {
    const sent = theApiAnswers()
    const onClose = vi.fn()
    const dialog = await open('note', onClose)

    await userEvent.click(within(dialog).getByRole('button', { name: 'ACME' }))
    await userEvent.click(await within(dialog).findByRole('radio', { name: 'Appel' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(onClose).toHaveBeenCalled())
    expect(sent).toEqual([
      {
        path: `/api/organizations/${ACME}/exchanges`,
        body: expect.objectContaining({
          personId: null,
          exchange: expect.objectContaining({ channel: 'call' }),
        }),
      },
    ])
  })

  it('logs it with the contact once they are chosen', async () => {
    const sent = theApiAnswers()
    const onClose = vi.fn()
    const dialog = await open('note', onClose)

    await userEvent.click(within(dialog).getByRole('button', { name: 'Ada Lovelace' }))
    await userEvent.click(await within(dialog).findByRole('radio', { name: 'Appel' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(onClose).toHaveBeenCalled())
    expect(sent).toEqual([
      { path: `/api/contacts/${ADA}/exchanges`, body: expect.objectContaining({ channel: 'call' }) },
    ])
  })

  it.each([ada, acme])(
    'says when $name may not be noted with, and goes back to the search',
    async ({ name }) => {
      theApiAnswers((path) => ({ planExchange: { href: path, method: 'POST' } }))
      const dialog = await open('note')

      await userEvent.click(within(dialog).getByRole('button', { name }))

      expect(
        await within(dialog).findByText(`Vous ne pouvez pas noter d’échange avec ${name}.`),
      ).toBeInTheDocument()
      expect(within(dialog).queryByRole('button', { name: 'Enregistrer' })).not.toBeInTheDocument()
      await userEvent.click(within(dialog).getByRole('button', { name: 'Retour' }))
      expect(within(dialog).getByRole('button', { name })).toBeInTheDocument()
    },
  )
})

describe('planning an exchange with someone chosen first', () => {
  it.each([
    [ada, `/api/contacts/${ADA}/upcoming-exchanges`],
    [acme, `/api/organizations/${ACME}/upcoming-exchanges`],
  ])('plans it with $name once chosen', async ({ name }, path) => {
    const sent = theApiAnswers()
    const onClose = vi.fn()
    const dialog = await open('plan', onClose)

    expect(within(dialog).getByRole('heading', { name: 'Planifier un échange' })).toBeInTheDocument()
    await userEvent.click(within(dialog).getByRole('button', { name }))
    await userEvent.click(await within(dialog).findByRole('radio', { name: 'Appel' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Demain 9h' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(onClose).toHaveBeenCalled())
    expect(sent).toEqual([{ path, body: expect.anything() }])
  })
})
