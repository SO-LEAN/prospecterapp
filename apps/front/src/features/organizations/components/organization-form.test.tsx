import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { toFormValues } from '../schema'
import { OrganizationForm } from './organization-form'

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined)) {
  render(
    <OrganizationForm
      defaultValues={toFormValues()}
      submitLabel="Créer"
      onSubmit={onSubmit}
      onCancel={vi.fn()}
    />,
  )
  return { onSubmit, user: userEvent.setup() }
}

describe('OrganizationForm', () => {
  it('sends the typed values, empty fields as null', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME')
    await user.type(screen.getByLabelText('Ville'), 'Paris')
    await user.click(screen.getByRole('button', { name: 'Créer' }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ corporateName: 'ACME', city: 'Paris', email: null }),
    )
  })

  it('explains the business rule before sending', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Ville'), 'Paris')
    await user.click(screen.getByRole('button', { name: 'Créer' }))

    expect(
      await screen.findByText('Renseignez au moins une raison sociale ou un e-mail.'),
    ).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('shows the API validation errors on the fields', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue({
        title: 'Validation Failed',
        status: 422,
        detail: 'Invalid',
        violations: [
          { propertyPath: 'email', title: 'Cet e-mail est déjà utilisé.' },
          { propertyPath: '', title: 'Organisation refusée.' },
        ],
      }),
    )

    await user.type(screen.getByLabelText('E-mail'), 'taken@acme.test')
    await user.click(screen.getByRole('button', { name: 'Créer' }))

    expect(await screen.findByText('Cet e-mail est déjà utilisé.')).toBeInTheDocument()
    expect(screen.getByLabelText('E-mail')).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByRole('alert')).toHaveTextContent('Organisation refusée.')
  })
})
