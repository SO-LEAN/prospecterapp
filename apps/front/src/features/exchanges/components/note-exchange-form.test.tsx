import { fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { NoteExchangeForm } from './note-exchange-form'

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined)) {
  render(<NoteExchangeForm onSubmit={onSubmit} onCancel={vi.fn()} />)
  return { onSubmit, user: userEvent.setup() }
}

const save = () => screen.getByRole('button', { name: 'Enregistrer' })

describe('NoteExchangeForm', () => {
  it('notes a received exchange, now, once its channel is chosen', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByLabelText('SMS'))
    await user.click(save())

    expect(onSubmit).toHaveBeenCalledWith({
      channel: 'sms',
      direction: 'inbound',
      occurredAt: expect.any(String),
      text: null,
      missed: false,
    })
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
    )
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
})
