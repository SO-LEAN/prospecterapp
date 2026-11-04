import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { StartProspectingForm } from './start-prospecting-form'

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined)) {
  render(<StartProspectingForm onSubmit={onSubmit} onCancel={vi.fn()} />)
  return { onSubmit, user: userEvent.setup() }
}

describe('StartProspectingForm', () => {
  it('sends what is known, empty fields as null', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME')
    await user.type(screen.getByLabelText('Ville'), 'Paris')
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        legalIdentity: { corporateName: 'ACME', form: null, type: null },
        contactDetails: { email: null, phoneNumber: null, language: null },
        address: expect.objectContaining({ city: 'Paris', street: null }),
        observations: null,
      }),
    )
  })

  it('explains the business rule before sending', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Ville'), 'Paris')
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(
      await screen.findByText('Renseignez au moins une raison sociale ou un e-mail.'),
    ).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('shows the API violations on the nested fields', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue({
        title: 'Validation Failed',
        status: 422,
        detail: 'Invalid',
        violations: [
          { propertyPath: 'contactDetails.email', title: 'Cet e-mail est déjà utilisé.' },
          { propertyPath: '', title: 'Organisation refusée.' },
        ],
      }),
    )

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME')
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(await screen.findByText('Cet e-mail est déjà utilisé.')).toBeInTheDocument()
    expect(screen.getByLabelText('E-mail')).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByRole('alert')).toHaveTextContent('Organisation refusée.')
  })
})
