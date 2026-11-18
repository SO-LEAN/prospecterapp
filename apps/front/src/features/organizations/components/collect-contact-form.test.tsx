import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { CollectContactForm } from './collect-contact-form'

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined)) {
  render(
    <CollectContactForm
      organizationId="0190a1c0-0000-7000-8000-000000000001"
      onSubmit={onSubmit}
      onCancel={vi.fn()}
    />,
  )
  return { onSubmit, user: userEvent.setup() }
}

describe('CollectContactForm', () => {
  it('sends what is known, empty fields as null, for the organization', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Prénom'), 'Ada')
    await user.type(screen.getByLabelText('Nom'), 'Lovelace')
    await user.type(screen.getByLabelText(/Fonction/), 'Directrice commerciale')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(onSubmit).toHaveBeenCalledWith({
      name: { firstName: 'Ada', lastName: 'Lovelace' },
      civility: 'x',
      jobTitle: 'Directrice commerciale',
      contactDetails: { email: null, phoneNumber: null, language: null, linkedInProfile: null },
      organizationIds: ['0190a1c0-0000-7000-8000-000000000001'],
    })
  })

  it('sends the civility chosen beside the name', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByRole('combobox', { name: /^Civilité/ }))
    await user.click(screen.getByRole('option', { name: 'Madame' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ civility: 'ms' }))
  })

  it('sends the LinkedIn profile as typed, the API keeping it in one form', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText(/^Profil LinkedIn/), 'linkedin.com/in/ada-lovelace')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        contactDetails: {
          email: null,
          phoneNumber: null,
          language: null,
          linkedInProfile: 'linkedin.com/in/ada-lovelace',
        },
      }),
    )
  })

  it('checks the email before sending', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('E-mail'), 'not an email')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(await screen.findByText('Adresse e-mail invalide.')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('shows a refusal of the API that names no field of the form', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue({
        title: 'Validation Failed',
        status: 422,
        detail: 'Email already used',
        violations: [{ propertyPath: 'email', title: 'Email already used' }],
        type: 'about:blank',
      }),
    )

    await user.type(screen.getByLabelText('E-mail'), 'ada@acme.test')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Email already used')
  })
})
