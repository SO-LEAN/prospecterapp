import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Contact } from '../queries'
import { ContactDetailsSection } from './contact-sections'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const NOT_A_PROFILE = 'https://example.test/ada'

const ada: Contact = {
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  civility: 'x',
  contactDetails: { email: 'ada@acme.test', phoneNumber: null, language: null, linkedInProfile: null },
  masked: false,
  _links: {
    self: { href: `/api/contacts/${ADA}`, method: 'GET' },
    changeContactDetails: { href: `/api/contacts/${ADA}/contact-details`, method: 'PATCH' },
  },
}

const anAddressThatIsNoProfile = {
  title: 'Validation Failed',
  status: 422,
  violations: [
    {
      propertyPath: 'linkedInProfile',
      title: `LinkedIn profile "${NOT_A_PROFILE}" is not valid`,
      reason: 'invalidLinkedInProfile',
      context: { linkedInProfile: NOT_A_PROFILE },
    },
  ],
}

const GRACE = '0190a1c0-0000-7000-8000-000000000002'

const grace: Contact = {
  ...ada,
  id: GRACE,
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, linkedInProfile: null },
  _links: {
    self: { href: `/api/contacts/${GRACE}`, method: 'GET' },
    changeContactDetails: { href: `/api/contacts/${GRACE}/contact-details`, method: 'PATCH' },
  },
}

const aProfileHeldBy = (holder: string) => ({
  title: 'Validation Failed',
  status: 422,
  reason: 'linkedInProfileAlreadyUsed',
  violations: [
    {
      propertyPath: 'linkedInProfile',
      title: `LinkedIn profile already held by ${holder}`,
      reason: 'linkedInProfileAlreadyUsed',
      context: { holder, holderId: ADA },
    },
  ],
})

afterEach(() => theApi.mockReset())

describe('the contact details of a contact', () => {
  it('note the LinkedIn profile, an address that is no profile refused on its field', async () => {
    const user = userEvent.setup()
    const sent: unknown[] = []
    theApi.mockImplementation(async (input) => {
      sent.push(await (input as Request).clone().json())
      return Response.json(anAddressThatIsNoProfile, { status: 422 })
    })
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={ada} />
      </QueryClientProvider>,
    )

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sCoordonnées$/ }))
    await user.type(screen.getByLabelText(/^Profil LinkedIn/), NOT_A_PROFILE)
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(
      await screen.findByText(
        /^L’adresse «\shttps:\/\/example\.test\/ada\s» n’est pas celle d’un profil LinkedIn\.$/,
      ),
    ).toBeInTheDocument()
    expect(sent).toEqual([
      { email: 'ada@acme.test', phoneNumber: null, language: null, linkedInProfile: NOT_A_PROFILE },
    ])
  })

  it('refuse a profile another contact holds, naming them and linking their sheet', async () => {
    const user = userEvent.setup()
    theApi.mockResolvedValue(Response.json(aProfileHeldBy('Ada Lovelace'), { status: 422 }))
    await renderInARouter(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={grace} />
      </QueryClientProvider>,
    )

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sCoordonnées$/ }))
    await user.type(screen.getByLabelText(/^Profil LinkedIn/), 'https://www.linkedin.com/in/ada-lovelace')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Ce profil LinkedIn est déjà celui d’Ada Lovelace.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Voir la fiche d’Ada Lovelace' })).toHaveAttribute(
      'href',
      `/contacts/${ADA}`,
    )
  })
})
