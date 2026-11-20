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
        contactDetails: { email: null, phoneNumber: null, language: null, links: [], website: null },
        address: expect.objectContaining({ city: 'Paris', street: null }),
        observations: null,
      }),
    )
  })

  it('sends the links pasted and the website typed', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME')
    await user.type(screen.getByLabelText(/^Ajouter un lien/), 'linkedin.com/company/acme-corp{Enter}')
    await user.type(screen.getByLabelText(/^Ajouter un lien/), 'https://annuaire-xyz.com/acme')
    await user.type(screen.getByLabelText(/^Site web/), 'acme.fr')
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        contactDetails: expect.objectContaining({
          links: ['linkedin.com/company/acme-corp', 'https://annuaire-xyz.com/acme'],
          website: 'acme.fr',
        }),
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

  it('shows a refusal of the links and of the website on their fields', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue({
        title: 'Validation Failed',
        status: 422,
        detail: 'Invalid',
        violations: [
          { propertyPath: 'contactDetails.links', title: 'Pas une page.' },
          { propertyPath: 'contactDetails.website', title: 'Pas un site.' },
        ],
      }),
    )

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME')
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(await screen.findByText('Pas une page.')).toBeInTheDocument()
    expect(screen.getByLabelText(/^Ajouter un lien/)).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByLabelText(/^Site web/)).toHaveAttribute('aria-invalid', 'true')
  })
})
