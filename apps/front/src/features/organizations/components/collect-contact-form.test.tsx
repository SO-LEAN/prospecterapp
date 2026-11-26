import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { CollectContactForm } from './collect-contact-form'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-00000000a000'

afterEach(() => {
  theApi.mockReset()
})

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
      title: 'x',
      jobTitle: 'Directrice commerciale',
      contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
      organizationIds: ['0190a1c0-0000-7000-8000-000000000001'],
    })
  })

  it('sends the civility chosen beside the name', async () => {
    const { onSubmit, user } = renderForm()

    await user.click(screen.getByRole('combobox', { name: /^Civilité/ }))
    await user.click(screen.getByRole('option', { name: 'Madame' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ title: 'ms' }))
  })

  it('sends the links as pasted, the API recognizing them', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText(/^Ajouter un lien/), 'linkedin.com/in/ada-lovelace')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        contactDetails: {
          email: null,
          phoneNumber: null,
          language: null,
          links: ['linkedin.com/in/ada-lovelace'],
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
        detail: 'This e-mail address is already in use',
        violations: [{ propertyPath: 'email', title: 'This e-mail address is already in use' }],
        type: 'about:blank',
      }),
    )

    await user.type(screen.getByLabelText('E-mail'), 'ada@acme.test')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('This e-mail address is already in use')
  })
})

/** The form of the contacts list, which chooses the organization among those the API finds. */
async function renderTheFormWithoutItsOrganization() {
  theApi.mockImplementation(async () =>
    Response.json({
      items: [{ id: ACME, name: 'ACME', possibleDuplicates: [] }],
      _links: { self: { href: '/api/organizations', method: 'GET' } },
    }),
  )
  const onSubmit = vi.fn().mockResolvedValue(undefined)
  await renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <CollectContactForm onSubmit={onSubmit} onCancel={vi.fn()} />
    </QueryClientProvider>,
  )
  return { onSubmit, user: userEvent.setup() }
}

describe('CollectContactForm without its organization', () => {
  it('chooses the organization first, then sends the contact for it', async () => {
    const { onSubmit, user } = await renderTheFormWithoutItsOrganization()

    await user.type(screen.getByRole('searchbox', { name: 'Organisation' }), 'AC')
    await user.click(await screen.findByRole('button', { name: 'ACME' }))
    await user.type(screen.getByLabelText('Prénom'), 'Grace')
    await user.type(screen.getByLabelText('Nom'), 'Hopper')
    await user.type(screen.getByLabelText(/Fonction/), 'Directrice technique')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(onSubmit).toHaveBeenCalledWith({
      name: { firstName: 'Grace', lastName: 'Hopper' },
      title: 'x',
      jobTitle: 'Directrice technique',
      contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
      organizationIds: [ACME],
    })
  })

  it('sends nothing before the organization is chosen, and says it must be', async () => {
    const { onSubmit, user } = await renderTheFormWithoutItsOrganization()

    await user.type(screen.getByLabelText('Prénom'), 'Grace')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(screen.getByRole('searchbox', { name: 'Organisation' })).toHaveAccessibleDescription(
      'Choisissez une organisation.',
    )
    expect(onSubmit).not.toHaveBeenCalled()
  })
})
