import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Suspense } from 'react'
import { describe, expect, it, vi } from 'vitest'
import { TooltipProvider } from '@/shared/ui/tooltip'
import { type Organization, organizationQuery } from '../queries'
import { ContactDetailsSection } from './contact-details-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-000000000001'
const ITS_PAGE = 'https://www.linkedin.com/company/acme-corp'
const ITS_SITE = 'https://www.acme.fr'

const anOrganization = (organization: Partial<Organization> = {}): Organization => ({
  id: ACME,
  name: 'ACME',
  legalIdentity: { corporateName: 'ACME', siret: null },
  contactDetails: {},
  contactIds: [],
  possibleDuplicates: [],
  createdAt: '2026-10-15T12:30:00+00:00',
  modifiedAt: '2026-10-15T12:30:00+00:00',
  _links: {
    self: { href: `/api/organizations/${ACME}`, method: 'GET' },
    changeContactDetails: { href: `/api/organizations/${ACME}/contact-details`, method: 'PATCH' },
  },
  ...organization,
})

function TheContactDetailsOfTheOrganization() {
  const { data: organization } = useSuspenseQuery(organizationQuery(ACME))

  return <ContactDetailsSection organization={organization} />
}

function renderTheSection(organization: Organization) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(organizationQuery(ACME).queryKey, organization)
  render(
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Suspense>
          <TheContactDetailsOfTheOrganization />
        </Suspense>
      </TooltipProvider>
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

const sentTo = async (path: string) => {
  const request = theApi.mock.calls.map(([sent]) => sent as Request).find((sent) => sent.url.endsWith(path))
  return request?.json()
}

async function openTheContactDetails(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('button', { name: /^Modifier.*Coordonnées$/ }))
}

const aRefusal = (propertyPath: string, reason: string, context: Record<string, string> = {}) =>
  Response.json(
    {
      title: 'Validation Failed',
      status: 422,
      violations: [{ propertyPath, title: reason, reason, context }],
    },
    { status: 422, headers: { 'Content-Type': 'application/problem+json' } },
  )

describe('the contact details of an organization', () => {
  it('shows its LinkedIn page and its website, each opened in a new tab', () => {
    renderTheSection(anOrganization({ contactDetails: { linkedInPage: ITS_PAGE, website: ITS_SITE } }))

    expect(screen.getByRole('link', { name: 'linkedin.com/company/acme-corp' })).toHaveAttribute(
      'href',
      ITS_PAGE,
    )
    expect(screen.getByRole('link', { name: 'www.acme.fr' })).toHaveAttribute('href', ITS_SITE)
    expect(screen.getByRole('link', { name: 'www.acme.fr' })).toHaveAttribute('target', '_blank')
  })

  it('notes the LinkedIn page and the website typed with its contact details', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(
      Response.json(anOrganization({ contactDetails: { linkedInPage: ITS_PAGE, website: ITS_SITE } })),
    )

    await openTheContactDetails(user)
    await user.type(screen.getByLabelText(/^Page LinkedIn/), 'linkedin.com/company/acme-corp')
    await user.type(screen.getByLabelText(/^Site web/), 'acme.fr')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await sentTo(`/api/organizations/${ACME}/contact-details`)).toEqual({
      email: null,
      phoneNumber: null,
      language: null,
      linkedInPage: 'linkedin.com/company/acme-corp',
      website: 'acme.fr',
    })
  })

  it('shows a LinkedIn page another organization of the account has on its field, naming that organization', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(
      aRefusal('linkedInPage', 'linkedInPageAlreadyUsed', { organization: 'ACME Lyon' }),
    )

    await openTheContactDetails(user)
    await user.type(screen.getByLabelText(/^Page LinkedIn/), 'linkedin.com/company/acme-corp')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Cette page LinkedIn est déjà celle d’ACME Lyon.')).toBeInTheDocument()
    expect(screen.getByLabelText(/^Page LinkedIn/)).toHaveAttribute('aria-invalid', 'true')
  })

  it('shows a website of the domain of another organization of the account on its field, naming that organization', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(aRefusal('website', 'websiteAlreadyUsed', { organization: 'ACME Lyon' }))

    await openTheContactDetails(user)
    await user.type(screen.getByLabelText(/^Site web/), 'acme.fr/blog')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Ce site est du domaine de celui d’ACME Lyon.')).toBeInTheDocument()
    expect(screen.getByLabelText(/^Site web/)).toHaveAttribute('aria-invalid', 'true')
  })

  it('says what is no page of a company on LinkedIn', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(aRefusal('linkedInPage', 'invalidLinkedInPage'))

    await openTheContactDetails(user)
    await user.type(screen.getByLabelText(/^Page LinkedIn/), 'linkedin.com/in/ada-lovelace')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Ce n’est pas la page LinkedIn d’une entreprise.')).toBeInTheDocument()
  })

  it('says what is no address of a website', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(aRefusal('website', 'invalidWebsite'))

    await openTheContactDetails(user)
    await user.type(screen.getByLabelText(/^Site web/), 'acme')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Ce n’est pas l’adresse d’un site web.')).toBeInTheDocument()
  })
})
