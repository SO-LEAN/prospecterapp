import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The page a public link of a booking leads to, opened by anyone, without signing in (ADR 0148, 0151, 0153). */

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

const theVisitor = vi.hoisted(() => ({ timeZone: 'Europe/Paris' }))

vi.mock('@/shared/auth/session', () => ({
  session: {
    accessToken: async () => null,
    renewedAccessToken: async () => null,
    signOut: async () => {},
    signOutEverywhere: async () => {},
  },
}))

vi.mock('@/shared/lib/time-zone', () => ({ readerTimeZone: () => theVisitor.timeZone }))

const CODE = 'AbCdEfGhIjKlMnOpQrStUv'

const answer = (status: number, body: unknown) =>
  Response.json(body, {
    status,
    headers: status >= 400 ? { 'Content-Type': 'application/problem+json' } : {},
  })

const theApiAnswers = (status: number, body: unknown) =>
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    expect(new URL(request.url).pathname).toBe(`/api/booking-pages/${CODE}`)
    return answer(status, body)
  })

/** The page, then the booking: what the booker sent kept, the API answering as told. */
const theApiBooks = (status: number, body: unknown, page = anOpenPage()) => {
  const sent: unknown[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    if (request.method === 'POST') {
      expect(new URL(request.url).pathname).toBe(`/api/booking-pages/${CODE}/appointments`)
      sent.push(await request.json())
      return answer(status, body)
    }
    return answer(200, page)
  })
  return sent
}

/** A person takes some seconds to fill the form: the clock moves on meanwhile, the page sending when it showed it. */
async function iFillTheFormFor(channel: 'Appel' | 'Visio', slot: string) {
  const user = userEvent.setup()
  await renderTheSpaAt(`/booking/${CODE}`)
  await user.click(await screen.findByRole('radio', { name: channel }))
  await user.click(screen.getByRole('button', { name: slot }))
  await user.type(screen.getByLabelText('Prénom'), 'Ada')
  await user.type(screen.getByLabelText('Nom'), 'Lovelace')
  await user.type(screen.getByLabelText(/^Téléphone/), '06 12 34 56 78')
  await user.type(screen.getByLabelText('E-mail'), 'ada@acme.fr')
  return user
}

/** The phone typed alone in the form of a call, on an open page. */
async function iTypeThePhone(number: string) {
  theApiBooks(201, {})
  const user = userEvent.setup()
  await renderTheSpaAt(`/booking/${CODE}`)
  await user.click(await screen.findByRole('radio', { name: 'Appel' }))
  await user.click(screen.getByRole('button', { name: '09:00' }))
  const phone = screen.getByLabelText(/^Téléphone/)
  await user.type(phone, number)
  return phone
}

const anOpenPage = (channels: string[] = ['call', 'videoCall']) => ({
  open: true,
  host: {
    firstName: 'Camille',
    lastName: 'Martin',
    picture: {
      url: 'https://static.test/camille.jpg',
      smallUrl: 'https://static.test/small/camille.jpg',
      mediumUrl: 'https://static.test/medium/camille.jpg',
    },
    organization: 'SOLEAN IT',
  },
  channels,
  timeZone: 'Europe/Paris',
  days: [
    { day: '2026-11-24', slots: ['2026-11-24T09:00:00+01:00', '2026-11-24T09:15:00+01:00'] },
    { day: '2026-11-25', slots: [] },
  ],
})

afterEach(() => {
  theApi.mockReset()
  theVisitor.timeZone = 'Europe/Paris'
})

describe('the public page of a booking', () => {
  it('says whom the booker meets, for which organization, for how long', async () => {
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('heading', { name: 'Camille Martin' })).toBeInTheDocument()
    expect(screen.getByText('SOLEAN IT')).toBeInTheDocument()
    expect(screen.getByText('Rendez-vous de 15 minutes')).toBeInTheDocument()
  })

  it('shows the free slots of each day on the clock of Paris, a day without slot greyed', async () => {
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    const tuesday = await screen.findByRole('group', { name: 'mar. 24 nov.' })
    expect(
      within(tuesday)
        .getAllByRole('button')
        .map((slot) => slot.textContent),
    ).toEqual(['09:00', '09:15'])
    expect(
      within(screen.getByRole('group', { name: 'mer. 25 nov.' })).getByText('Aucun créneau'),
    ).toBeInTheDocument()
    expect(screen.getByText('Heures de Paris')).toBeInTheDocument()
  })

  it('converts the slots to the clock of a visitor elsewhere', async () => {
    theVisitor.timeZone = 'Europe/London'
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    const tuesday = await screen.findByRole('group', { name: 'mar. 24 nov.' })
    expect(
      within(tuesday)
        .getAllByRole('button')
        .map((slot) => slot.textContent),
    ).toEqual(['08:00', '08:15'])
    expect(screen.getByText('Heures de London, converties depuis l’heure de Paris')).toBeInTheDocument()
  })

  it('offers the channels of its host only, then a slot of the channel chosen', async () => {
    theApiAnswers(200, anOpenPage(['videoCall']))

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('radio', { name: 'Visio' })).toBeChecked()
    expect(screen.queryByRole('radio', { name: 'Appel' })).not.toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: '09:15' }))
    expect(screen.getByRole('button', { name: '09:15' })).toHaveAttribute('aria-pressed', 'true')
    expect(screen.getByText('Visio, mardi 24 novembre à 9h15, 15 min')).toBeInTheDocument()
  })

  it('waits for a channel before a slot when its host offers several', async () => {
    theApiAnswers(200, anOpenPage())

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByRole('button', { name: '09:00' })).toBeDisabled()
    await userEvent.click(screen.getByRole('radio', { name: 'Appel' }))
    expect(screen.getByRole('button', { name: '09:00' })).toBeEnabled()
  })

  it('says no slot is available when it offers none, without saying why', async () => {
    theApiAnswers(200, { open: false, host: null, channels: [], timeZone: null, days: [] })

    await renderTheSpaAt(`/booking/${CODE}`)

    expect(await screen.findByText('Aucun créneau n’est disponible pour le moment.')).toBeInTheDocument()
  })

  it('recalls the slot chosen above the form, and why the booker gives their details', async () => {
    theApiAnswers(200, anOpenPage(['videoCall']))

    await renderTheSpaAt(`/booking/${CODE}`)
    await userEvent.click(await screen.findByRole('button', { name: '09:00' }))

    expect(screen.getByText('Visio, mardi 24 novembre à 9h00, 15 min')).toBeInTheDocument()
    expect(
      screen.getByText(
        /^Vos coordonnées servent à organiser ce rendez-vous\s: votre e-mail est confié à Google, dont l’agenda vous invite\. Elles sont effacées sous 30 jours si nous ne poursuivons pas\.$/,
      ),
    ).toBeInTheDocument()
    expect(screen.getByLabelText(/^Objet/)).not.toBeRequired()
  })

  it('books the slot with what the booker said, then says the invitation and the link of the video call come', async () => {
    const sent = theApiBooks(201, {
      slot: '2026-11-24T09:00:00+01:00',
      channel: 'videoCall',
      email: 'ada@acme.fr',
    })

    const user = await iFillTheFormFor('Visio', '09:00')
    await user.type(screen.getByLabelText(/^Objet/), 'Mission data en mars')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(await screen.findByText('C’est réservé !')).toBeInTheDocument()
    expect(screen.getByText('Vous allez recevoir une invitation à ada@acme.fr.')).toBeInTheDocument()
    expect(screen.getByText('Le lien de la visio est dans l’invitation.')).toBeInTheDocument()
    expect(sent).toEqual([
      {
        slot: '2026-11-24T09:00:00+01:00',
        channel: 'videoCall',
        firstName: 'Ada',
        lastName: 'Lovelace',
        phone: '06 12 34 56 78',
        email: 'ada@acme.fr',
        subject: 'Mission data en mars',
        website: '',
        formShownAt: expect.any(String),
      },
    ])
  })

  it('says its host calls the booker of a call at the number they gave, in the international format', async () => {
    theApiBooks(201, { slot: '2026-11-24T09:15:00+01:00', channel: 'call', email: 'ada@acme.fr' })

    const user = await iFillTheFormFor('Appel', '09:15')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(await screen.findByText('Camille vous appellera au +33 6 12 34 56 78.')).toBeInTheDocument()
  })

  it('says a number the page cannot read as the booker typed it', async () => {
    theApiBooks(201, { slot: '2026-11-24T09:15:00+01:00', channel: 'call', email: 'ada@acme.fr' })

    const user = await iFillTheFormFor('Appel', '09:15')
    const phone = screen.getByLabelText(/^Téléphone/)
    await user.clear(phone)
    await user.type(phone, '+000000')
    expect(phone).toHaveValue('+000000')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(await screen.findByText('Camille vous appellera au +000000.')).toBeInTheDocument()
  })

  it('tells a booker who has an appointment already when it is, and to write or call its host for another', async () => {
    theApiBooks(422, {
      title: 'Validation Failed',
      status: 422,
      detail: 'You already have an appointment',
      reason: 'alreadyBooked',
      violations: [
        {
          propertyPath: 'email',
          title: 'You already have an appointment',
          reason: 'alreadyBooked',
          context: { appointment: '2026-11-24T09:00:00+01:00' },
        },
      ],
    })

    const user = await iFillTheFormFor('Appel', '09:15')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(
      await screen.findByText(
        'Vous avez déjà un rendez-vous le mardi 24 novembre à 9h00. Pour un autre rendez-vous, écrivez ou téléphonez à Camille.',
      ),
    ).toBeInTheDocument()
  })

  it('sends the booker back to the slots when theirs was just taken, read again', async () => {
    theApiBooks(422, {
      title: 'Validation Failed',
      status: 422,
      detail: 'This slot was just taken',
      reason: 'slotNotFree',
      violations: [
        { propertyPath: 'slot', title: 'This slot was just taken', reason: 'slotNotFree', context: {} },
      ],
    })

    const user = await iFillTheFormFor('Appel', '09:15')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(
      await screen.findByText('Ce créneau vient d’être pris : choisissez-en un autre.'),
    ).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Réserver' })).not.toBeInTheDocument()
    expect(theApi.mock.calls.filter(([request]) => (request as Request).method === 'GET')).toHaveLength(2)
  })

  it('tells the booker in their words a refusal they cannot correct, never as the API worded it', async () => {
    theApiBooks(422, {
      title: 'Validation Failed',
      status: 422,
      detail: 'formShownAt: This value is not a valid datetime.',
      violations: [{ propertyPath: 'formShownAt', title: 'This value is not a valid datetime.' }],
    })

    const user = await iFillTheFormFor('Visio', '09:00')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(
      await screen.findByText('Votre réservation n’a pas abouti : réessayez dans un instant.'),
    ).toBeInTheDocument()
    expect(screen.queryByText('This value is not a valid datetime.')).not.toBeInTheDocument()
  })

  it('tells the booker a refusal of the whole booking in the words it has for it', async () => {
    theApiBooks(422, {
      title: 'Validation Failed',
      status: 422,
      detail: 'This channel is not offered',
      reason: 'channelNotOffered',
      violations: [{ propertyPath: '', title: 'This channel is not offered', reason: 'channelNotOffered' }],
    })

    const user = await iFillTheFormFor('Visio', '09:00')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(await screen.findByText('Ce canal n’est pas proposé.')).toBeInTheDocument()
  })

  it('formats a French phone as the booker types it', async () => {
    const phone = await iTypeThePhone('0612345678')

    expect(phone).toHaveValue('06 12 34 56 78')
  })

  it('formats an international phone as its country writes it', async () => {
    const phone = await iTypeThePhone('+32470123456')

    expect(phone).toHaveValue('+32 470 12 34 56')
  })

  it('refuses to send a form without its phone', async () => {
    const sent = theApiBooks(201, {})

    const user = userEvent.setup()
    await renderTheSpaAt(`/booking/${CODE}`)
    await user.click(await screen.findByRole('radio', { name: 'Appel' }))
    await user.click(screen.getByRole('button', { name: '09:00' }))
    await user.type(screen.getByLabelText('Prénom'), 'Ada')
    await user.type(screen.getByLabelText('Nom'), 'Lovelace')
    await user.type(screen.getByLabelText('E-mail'), 'ada@acme.fr')
    await user.click(screen.getByRole('button', { name: 'Réserver' }))

    expect(await screen.findByText('Ce champ est obligatoire.')).toBeInTheDocument()
    expect(sent).toEqual([])
  })
})
