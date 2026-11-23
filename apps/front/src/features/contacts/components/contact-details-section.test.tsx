import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Contact } from '../queries'
import { ContactDetailsSection, JobTitleSection } from './contact-sections'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const A_COMPANY_PAGE = 'https://www.linkedin.com/company/acme'
const HER_PROFILE = {
  source: 'linkedInProfile',
  identifier: 'ada-lovelace',
  address: 'https://www.linkedin.com/in/ada-lovelace',
}

const ada: Contact = {
  id: ADA,
  revision: 1,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  possibleDuplicates: [],
  createdByAnAI: false,
  title: 'x',
  contactDetails: { email: 'ada@acme.test', phoneNumber: null, language: null, links: [HER_PROFILE] },
  masked: false,
  _links: {
    self: { href: `/api/contacts/${ADA}`, method: 'GET' },
    changeContactDetails: { href: `/api/contacts/${ADA}/contact-details`, method: 'PATCH' },
  },
}

const aPageOfAnotherKind = {
  title: 'Validation Failed',
  status: 422,
  violations: [
    {
      propertyPath: 'links',
      title: 'A page of linkedInCompany designates an organization',
      reason: 'referenceOfAnotherKind',
      context: { source: 'linkedInCompany', designates: 'organization' },
    },
  ],
}

const GRACE = '0190a1c0-0000-7000-8000-000000000002'

const grace: Contact = {
  ...ada,
  id: GRACE,
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  _links: {
    self: { href: `/api/contacts/${GRACE}`, method: 'GET' },
    changeContactDetails: { href: `/api/contacts/${GRACE}/contact-details`, method: 'PATCH' },
  },
}

const aLinkHeldBy = (holder: string) => ({
  title: 'Validation Failed',
  status: 422,
  reason: 'referenceAlreadyUsed',
  violations: [
    {
      propertyPath: 'links',
      title: `${holder} already has this link`,
      reason: 'referenceAlreadyUsed',
      context: { holder, holderId: ADA },
    },
  ],
})

function sendingTo(sent: unknown[], answer: Response) {
  return async (input: RequestInfo | URL) => {
    sent.push(await (input as Request).clone().json())
    return answer
  }
}

async function edit(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('button', { name: /^Modifier\s:\sCoordonnées$/ }))
}

afterEach(() => theApi.mockReset())

function revisionsSent(revisions: Array<string | null>, answer: Contact) {
  return async (input: RequestInfo | URL) => {
    revisions.push(new URL((input as Request).url).searchParams.get('revision'))
    return Response.json(answer)
  }
}

describe('the contact details of a contact', () => {
  it('are saved on the revision the contact was read at', async () => {
    const user = userEvent.setup()
    const revisions: Array<string | null> = []
    theApi.mockImplementation(revisionsSent(revisions, { ...ada, revision: 8 }))
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={{ ...ada, revision: 7 }} />
      </QueryClientProvider>,
    )

    await edit(user)
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() => expect(revisions).toEqual(['7']))
  })

  it('show each link with the mark of its source', () => {
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={ada} />
      </QueryClientProvider>,
    )

    const link = screen.getByRole('link', { name: /linkedin\.com\/in\/ada-lovelace$/ })
    expect(link).toHaveAttribute('href', HER_PROFILE.address)
    expect(within(link).getByRole('img', { name: 'LinkedIn' })).toBeInTheDocument()
  })

  it('add a link pasted to those kept, a page of another kind refused on the links', async () => {
    const user = userEvent.setup()
    const sent: unknown[] = []
    theApi.mockImplementation(sendingTo(sent, Response.json(aPageOfAnotherKind, { status: 422 })))
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={ada} />
      </QueryClientProvider>,
    )

    await edit(user)
    await user.type(screen.getByLabelText(/^Ajouter un lien/), `${A_COMPANY_PAGE}{Enter}`)
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(
      await screen.findByText('Une page entreprise LinkedIn désigne une organisation, pas une personne.'),
    ).toBeInTheDocument()
    expect(sent).toEqual([
      {
        email: 'ada@acme.test',
        phoneNumber: null,
        language: null,
        links: [HER_PROFILE.address, A_COMPANY_PAGE],
      },
    ])
  })

  it('list a link pasted twice once', async () => {
    const user = userEvent.setup()
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={grace} />
      </QueryClientProvider>,
    )

    await edit(user)
    await user.type(screen.getByLabelText(/^Ajouter un lien/), `${A_COMPANY_PAGE}{Enter}`)
    await user.type(screen.getByLabelText(/^Ajouter un lien/), `${A_COMPANY_PAGE}{Enter}`)

    expect(screen.getAllByRole('button', { name: 'Retirer linkedin.com/company/acme' })).toHaveLength(1)
  })

  it('take a link off', async () => {
    const user = userEvent.setup()
    const sent: unknown[] = []
    theApi.mockImplementation(sendingTo(sent, Response.json(ada)))
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={ada} />
      </QueryClientProvider>,
    )

    await edit(user)
    await user.click(screen.getByRole('button', { name: 'Retirer linkedin.com/in/ada-lovelace' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(sent).toEqual([{ email: 'ada@acme.test', phoneNumber: null, language: null, links: [] }])
  })

  it('refuse a link another contact holds, naming them and linking their sheet', async () => {
    const user = userEvent.setup()
    theApi.mockResolvedValue(Response.json(aLinkHeldBy('Ada Lovelace'), { status: 422 }))
    await renderInARouter(
      <QueryClientProvider client={new QueryClient()}>
        <ContactDetailsSection contact={grace} />
      </QueryClientProvider>,
    )

    await edit(user)
    await user.type(screen.getByLabelText(/^Ajouter un lien/), HER_PROFILE.address)
    await user.click(screen.getByRole('button', { name: 'Ajouter' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Ce lien est déjà celui d’Ada Lovelace.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Voir la fiche d’Ada Lovelace' })).toHaveAttribute(
      'href',
      `/contacts/${ADA}`,
    )
  })
})

describe('the job title of a contact', () => {
  it('is saved on the revision the contact was read at', async () => {
    const user = userEvent.setup()
    const revisions: Array<string | null> = []
    theApi.mockImplementation(revisionsSent(revisions, { ...ada, revision: 8 }))
    const withItsJobTitle: Contact = {
      ...ada,
      revision: 7,
      _links: { ...ada._links, changeJobTitle: { href: `/api/contacts/${ADA}/job-title`, method: 'PATCH' } },
    }
    render(
      <QueryClientProvider client={new QueryClient()}>
        <JobTitleSection contact={withItsJobTitle} />
      </QueryClientProvider>,
    )

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sFonction$/ }))
    await user.type(screen.getByLabelText('Fonction'), 'CTO')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() => expect(revisions).toEqual(['7']))
  })
})
