import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'
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

/** The form the API signs to send a file to the storage. */
const AN_UPLOAD = {
  upload: 'an-upload',
  url: 'https://storage.test/upload',
  fields: {},
  heaviest: 10_000_000,
}

/** The timelines offer what is given, the API logs, plans and signs uploads, the storage takes the files. Any other reading returns nothing. */
function theApiAnswers(offered: (path: string) => Omit<Links, 'self'> = OFFERS_ALL) {
  const sent: Sent[] = []
  theApi.mockImplementation(async (input) => {
    if (!(input instanceof Request)) return new Response(null, { status: 204 })
    const request = input
    const path = new URL(request.url).pathname
    if (path === '/api/uploads') return Response.json(AN_UPLOAD)
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

/** The page that opens the dialog for a gesture, as « Nouveau » does, and is told when it closes. */
function APage({ onClose }: { onClose: () => void }) {
  const [gesture, setGesture] = useState<Gesture | null>(null)

  return (
    <>
      <button type="button" onClick={() => setGesture('note')}>
        Ouvrir pour noter
      </button>
      <button type="button" onClick={() => setGesture('plan')}>
        Ouvrir pour planifier
      </button>
      <ExchangeWithSomeone
        gesture={gesture}
        onClose={() => {
          setGesture(null)
          onClose()
        }}
        interlocutorSearch={aSearchFinding(ada, acme)}
      />
    </>
  )
}

const OPENERS = { note: 'Ouvrir pour noter', plan: 'Ouvrir pour planifier' } as const

async function open(gesture: Gesture, onClose = () => {}) {
  await renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <APage onClose={onClose} />
    </QueryClientProvider>,
  )
  return openAgain(gesture)
}

async function openAgain(gesture: Gesture) {
  await userEvent.click(screen.getByRole('button', { name: OPENERS[gesture] }))
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

  it.each([ada, acme])('attaches the files chosen to the exchange logged with $name', async ({ name }) => {
    const sent = theApiAnswers()
    const dialog = await open('note')

    await userEvent.click(within(dialog).getByRole('button', { name }))
    await userEvent.click(await within(dialog).findByRole('radio', { name: 'Appel' }))
    await userEvent.upload(
      within(dialog).getByLabelText('choisissez-les'),
      new File(['devis'], 'devis.pdf', { type: 'application/pdf' }),
    )
    await userEvent.click(within(dialog).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() =>
      expect(sent).toContainEqual({
        path: `/api/exchanges/${NOTED}/attachments`,
        body: { upload: 'an-upload', name: 'devis.pdf' },
      }),
    )
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

/** A call planned with « Demain 9h », the day after the moment of the tests. */
const aCallTomorrowAtNine = () =>
  expect.objectContaining({ channel: 'call', occurredAt: new Date(2026, 10, 19, 9, 0).toISOString() })

describe('planning an exchange with someone chosen first', () => {
  it.each([
    [ada, `/api/contacts/${ADA}/upcoming-exchanges`, aCallTomorrowAtNine],
    [
      acme,
      `/api/organizations/${ACME}/upcoming-exchanges`,
      () => ({ personId: null, exchange: aCallTomorrowAtNine() }),
    ],
  ])('plans it with $name once chosen', async ({ name }, path, planned) => {
    const sent = theApiAnswers()
    const onClose = vi.fn()
    const dialog = await open('plan', onClose)

    expect(within(dialog).getByRole('heading', { name: 'Planifier un échange' })).toBeInTheDocument()
    await userEvent.click(within(dialog).getByRole('button', { name }))
    await userEvent.click(await within(dialog).findByRole('radio', { name: 'Appel' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Demain 9h' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(onClose).toHaveBeenCalled())
    expect(sent).toEqual([{ path, body: planned() }])
  })
})

describe('the dialog of an exchange with someone', () => {
  it('says that the member first chooses who the exchange is with', async () => {
    theApiAnswers()
    const dialog = await open('note')

    expect(within(dialog).getByRole('heading', { name: 'Noter un échange' })).toBeInTheDocument()
    expect(
      within(dialog).getByText('Avec un contact ou une organisation, à choisir d’abord.'),
    ).toBeInTheDocument()
  })

  it('leaves the search once someone is chosen', async () => {
    theApiAnswers()
    const dialog = await open('note')

    await userEvent.click(within(dialog).getByRole('button', { name: 'Ada Lovelace' }))

    expect(await within(dialog).findByRole('radio', { name: 'Appel' })).toBeInTheDocument()
    expect(within(dialog).queryByRole('button', { name: 'ACME' })).not.toBeInTheDocument()
  })

  it('goes back to the search when the planning is cancelled', async () => {
    theApiAnswers()
    const dialog = await open('plan')

    await userEvent.click(within(dialog).getByRole('button', { name: 'ACME' }))
    await userEvent.click(await within(dialog).findByRole('button', { name: 'Annuler' }))

    expect(within(dialog).getByRole('button', { name: 'ACME' })).toBeInTheDocument()
  })

  it('closes on Escape, and opens again on the search', async () => {
    theApiAnswers()
    const onClose = vi.fn()
    const dialog = await open('plan', onClose)
    await userEvent.click(within(dialog).getByRole('button', { name: 'Ada Lovelace' }))
    await within(dialog).findByRole('button', { name: 'Annuler' })

    await userEvent.keyboard('{Escape}')

    expect(onClose).toHaveBeenCalledOnce()
    const again = await openAgain('plan')
    expect(within(again).getByRole('button', { name: 'Ada Lovelace' })).toBeInTheDocument()
  })
})
