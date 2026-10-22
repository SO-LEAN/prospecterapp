import { fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import type { ExchangeValues } from '../schema'
import { ExchangeForm, type Interlocutors } from './exchange-form'

const ROBERT = '0190a1c0-0000-7000-8000-000000000002'

type Options = { interlocutors?: Interlocutors; defaultValues?: ExchangeValues; withFiles?: boolean }

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined), options: Options = {}) {
  render(<ExchangeForm onSubmit={onSubmit} onCancel={vi.fn()} {...options} />)
  return { onSubmit, user: userEvent.setup() }
}

const save = () => screen.getByRole('button', { name: 'Enregistrer' })

describe('ExchangeForm', () => {
  it('notes a received exchange, now, once its channel is chosen', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('SMS'))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(
      {
        channel: 'sms',
        direction: 'inbound',
        occurredAt: expect.any(String),
        text: null,
        missed: false,
      },
      null,
      [],
    )
    expect(screen.queryByText('Interlocuteur')).not.toBeInTheDocument()
    const occurredAt = new Date(onSubmit.mock.calls[0]?.[0].occurredAt).getTime()
    expect(Date.now() - occurredAt).toBeLessThan(60_000)
  })

  it('asks for the channel before sending', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(save())

    expect(await screen.findByText('Choisissez le canal.')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('offers to say a call did not go through, and only a call', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('E-mail'))
    expect(screen.queryByLabelText('L’appel n’a pas abouti')).not.toBeInTheDocument()

    await user.click(screen.getByLabelText('Appel'))
    await user.click(screen.getByLabelText('Envoyé'))
    await user.click(screen.getByLabelText('L’appel n’a pas abouti'))
    await user.type(screen.getByLabelText(/Texte/), 'Messagerie')
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ channel: 'call', direction: 'outbound', missed: true, text: 'Messagerie' }),
      null,
      [],
    )
  })

  it('notes from an organization with the organization itself by default', async () => {
    const { onSubmit, user } = renderForm(undefined, {
      interlocutors: { organization: 'ACME', contacts: [{ id: ROBERT, name: 'Robert Noyce' }] },
    })

    expect(screen.getByLabelText('ACME')).toBeChecked()
    await user.click(screen.getByLabelText('Appel'))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ channel: 'call' }), null, [])
  })

  it('notes from an organization with one of its contacts', async () => {
    const { onSubmit, user } = renderForm(undefined, {
      interlocutors: { organization: 'ACME', contacts: [{ id: ROBERT, name: 'Robert Noyce' }] },
    })

    await user.click(screen.getByLabelText('Robert Noyce'))
    await user.click(screen.getByLabelText('E-mail'))
    await user.click(screen.getByLabelText('Envoyé'))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ channel: 'email', direction: 'outbound' }),
      ROBERT,
      [],
    )
  })

  it('joins the files chosen with a new exchange, one removed before saving', async () => {
    const { onSubmit, user } = renderForm(undefined, { withFiles: true })
    const devis = new File(['devis'], 'devis.pdf', { type: 'application/pdf' })
    const photo = new File(['photo'], 'photo.png', { type: 'image/png' })

    await user.click(screen.getByLabelText('E-mail'))
    await user.upload(screen.getByLabelText('choisissez-les'), [devis, photo])
    await user.click(screen.getByRole('button', { name: 'Retirer photo.png' }))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ channel: 'email' }), null, [devis])
  })

  it('joins no file to a correction', () => {
    renderForm()

    expect(screen.queryByText('Pièces jointes')).not.toBeInTheDocument()
  })

  it('refuses a moment in the future', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('LinkedIn'))
    fireEvent.change(screen.getByLabelText('Moment'), { target: { value: '2099-01-01T09:00' } })
    await user.click(save())

    expect(await screen.findByText('Un échange ne peut pas être dans le futur.')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('shows the refusal of the API on its field', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue({
        title: 'Validation Failed',
        status: 422,
        detail: 'An exchange is never in the future',
        violations: [
          {
            propertyPath: 'occurredAt',
            title: 'An exchange is never in the future',
            reason: 'exchangeInTheFuture',
          },
        ],
        type: 'about:blank',
      }),
    )

    await user.click(screen.getByLabelText('Rendez-vous'))
    await user.click(save())

    expect(await screen.findByText('Un échange ne peut pas être dans le futur.')).toBeInTheDocument()
  })

  it('corrects an exchange from what it was', async () => {
    const { onSubmit, user } = renderForm(undefined, {
      defaultValues: {
        channel: 'linkedIn',
        direction: 'inbound',
        occurredAt: '2026-10-15T14:30',
        text: 'Intéressée',
        missed: false,
        personId: '',
      },
    })

    await user.click(screen.getByLabelText('E-mail'))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith(
      {
        channel: 'email',
        direction: 'inbound',
        occurredAt: new Date('2026-10-15T14:30').toISOString(),
        text: 'Intéressée',
        missed: false,
      },
      null,
      [],
    )
  })
})
