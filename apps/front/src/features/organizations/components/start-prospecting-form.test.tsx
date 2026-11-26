import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { StartProspectingForm } from './start-prospecting-form'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const GROUPE_ACME = '0190a1c0-0000-7000-8000-00000000a000'

/** Searching the organizations finds the holding "Groupe ACME". */
function theSearchFindsGroupeAcme() {
  theApi.mockImplementation(async () =>
    Response.json({
      items: [
        {
          id: GROUPE_ACME,
          name: 'Groupe ACME',
          postalCode: '67210',
          city: 'Obernai',
          possibleDuplicates: [],
        },
      ],
      _links: { self: { href: '/api/organizations', method: 'GET' } },
    }),
  )
}

/** Searching the organizations finds the holding "Groupe ACME", with its logo. */
function theSearchFindsGroupeAcmeWithItsLogo() {
  theApi.mockImplementation(async () =>
    Response.json({
      items: [
        {
          id: GROUPE_ACME,
          name: 'Groupe ACME',
          logo: { url: '/acme.png', smallUrl: '/acme-96.png', mediumUrl: '/acme-256.png' },
          possibleDuplicates: [],
        },
      ],
      _links: { self: { href: '/api/organizations', method: 'GET' } },
    }),
  )
}

const searched = () => theApi.mock.calls.map(([request]) => new URL((request as Request).url).search)

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined)) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <StartProspectingForm onSubmit={onSubmit} onCancel={vi.fn()} />
    </QueryClientProvider>,
  )
  return { onSubmit, user: userEvent.setup() }
}

async function chooseTheHoldingGroupeAcme(user: ReturnType<typeof userEvent.setup>) {
  theSearchFindsGroupeAcme()
  await user.type(screen.getByLabelText(/^Holding/), 'Groupe')
  await user.click(await screen.findByRole('button', { name: /^Groupe ACME/ }))
}

afterEach(() => {
  theApi.mockReset()
})

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
        holdingId: null,
      }),
    )
  })

  it('sends the holding chosen by a search among my organizations', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME Est')
    await chooseTheHoldingGroupeAcme(user)
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(searched()).toContain('?q=Groupe')
    expect(onSubmit).toHaveBeenCalledOnce()
    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        legalIdentity: expect.objectContaining({ corporateName: 'ACME Est' }),
        holdingId: GROUPE_ACME,
      }),
    )
  })

  it('invites to search the holding among my organizations', () => {
    renderForm()

    expect(screen.getByLabelText(/^Holding/)).toHaveAttribute(
      'placeholder',
      'Rechercher parmi mes organisations',
    )
  })

  it('shows each holding found with its place, to tell homonyms apart', async () => {
    const { user } = renderForm()
    theSearchFindsGroupeAcme()

    await user.type(screen.getByLabelText(/^Holding/), 'Groupe')

    expect(await screen.findByRole('button', { name: /^Groupe ACME/ })).toHaveTextContent('67210 Obernai')
  })

  it('shows each holding found with its logo', async () => {
    const { user } = renderForm()
    theSearchFindsGroupeAcmeWithItsLogo()

    await user.type(screen.getByLabelText(/^Holding/), 'Groupe')
    const found = await screen.findByRole('button', { name: /^Groupe ACME/ })

    expect(within(found).getByRole('presentation', { hidden: true })).toHaveAttribute('src', '/acme-96.png')
  })

  it('shows the holding chosen with its logo', async () => {
    const { user } = renderForm()
    theSearchFindsGroupeAcmeWithItsLogo()

    await user.type(screen.getByLabelText(/^Holding/), 'Groupe')
    await user.click(await screen.findByRole('button', { name: /^Groupe ACME/ }))

    expect(screen.getByRole('presentation')).toHaveAttribute('src', '/acme-96.png')
  })

  it('searches no holding before a text is typed', async () => {
    renderForm()

    await userEvent.setup().click(screen.getByLabelText(/^Holding/))

    expect(theApi).not.toHaveBeenCalled()
    expect(screen.queryByRole('list')).not.toBeInTheDocument()
  })

  it('shows the holding chosen, and sends none once it is removed', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME Est')
    await chooseTheHoldingGroupeAcme(user)
    expect(screen.getByText('Groupe ACME')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /^Groupe ACME/ })).not.toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Retirer la holding' }))
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(screen.queryByText('Groupe ACME')).not.toBeInTheDocument()
    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ holdingId: null }))
  })

  it('shows the refusal of the holding under its field, and keeps the rest of the form', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue({
        title: 'Not Found',
        status: 404,
        detail: `Organization ${GROUPE_ACME} not found`,
        violations: [],
        reason: 'unknownOrganization',
      }),
    )

    await user.type(screen.getByLabelText('Raison sociale'), 'ACME Est')
    await chooseTheHoldingGroupeAcme(user)
    await user.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(await screen.findByText('Organisation introuvable.')).toBeInTheDocument()
    expect(screen.getByLabelText(/^Holding/)).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByLabelText(/^Holding/)).toHaveTextContent('Groupe ACME')
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
    expect(screen.getByLabelText('Raison sociale')).toHaveValue('ACME Est')
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
