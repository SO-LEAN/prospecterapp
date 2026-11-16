import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { contactQuery, organizationsOfContactQuery } from '../queries'
import { ContactSummary } from './contact-summary'

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ACME = '0190a1c0-0000-7000-8000-00000000b000'

describe('ContactSummary', () => {
  it('says who the contact is and where, called or written to in a click', async () => {
    const queryClient = new QueryClient({
      defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
    })
    queryClient.setQueryData(contactQuery(ADA).queryKey, {
      id: ADA,
      name: { firstName: 'Ada', lastName: 'Lovelace' },
      jobTitle: 'CTO',
      contactDetails: { email: 'ada@acme.test', phoneNumber: '+33 3 88 11 22 33' },
      masked: false,
      _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
    })
    queryClient.setQueryData(organizationsOfContactQuery(ADA).queryKey, {
      items: [
        { id: ACME, name: 'ACME', organization: { href: `/api/organizations/${ACME}`, method: 'GET' } },
      ],
    })

    await renderInARouter(
      <QueryClientProvider client={queryClient}>
        <ContactSummary contactId={ADA} />
      </QueryClientProvider>,
    )

    expect(screen.getByText('Ada Lovelace')).toBeInTheDocument()
    expect(screen.getByText('AL')).toBeInTheDocument()
    expect(screen.getByText('CTO')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'ACME' })).toHaveAttribute('href', `/organizations/${ACME}`)
    expect(screen.getByRole('link', { name: '+33 3 88 11 22 33' })).toHaveAttribute(
      'href',
      'tel:+33388112233',
    )
    expect(screen.getByRole('link', { name: 'ada@acme.test' })).toHaveAttribute(
      'href',
      'mailto:ada@acme.test',
    )
  })

  it('names a contact read without the right to their personal data, nothing else of them', async () => {
    const queryClient = new QueryClient({
      defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
    })
    queryClient.setQueryData(contactQuery(ADA).queryKey, {
      id: ADA,
      name: { firstName: 'Ada', lastName: 'Lovelace' },
      jobTitle: 'C***O',
      contactDetails: { email: 'a***@***', phoneNumber: '******33' },
      masked: true,
      _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
    })
    queryClient.setQueryData(organizationsOfContactQuery(ADA).queryKey, { items: [] })

    await renderInARouter(
      <QueryClientProvider client={queryClient}>
        <ContactSummary contactId={ADA} />
      </QueryClientProvider>,
    )

    expect(screen.getByText('Ada Lovelace')).toBeInTheDocument()
    expect(screen.queryByText('C***O')).not.toBeInTheDocument()
    expect(screen.queryByText('******33')).not.toBeInTheDocument()
    expect(screen.queryByText('a***@***')).not.toBeInTheDocument()
  })
})
