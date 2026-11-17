import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { ContactInList } from '../queries'
import { ContactCard } from './contact-card'

const ADA = '0190a1c0-0000-7000-8000-000000000002'

const ada = (tags: string[]): ContactInList => ({
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  jobTitle: null,
  contactDetails: { email: 'ada@acme.test' },
  masked: false,
  organizations: [],
  tags,
  contact: { href: `/api/contacts/${ADA}`, method: 'GET' },
})

describe('a contact of the list', () => {
  it('shows every tag it carries, in small', async () => {
    await renderInARouter(<ContactCard contact={ada(['#EMAIL', '#ESN', '#SMS'])} />)

    const tags = screen.getByRole('list', { name: 'Tags de Ada Lovelace' })
    expect(within(tags).getAllByRole('listitem').map((tag) => tag.textContent)).toEqual(['#EMAIL', '#ESN', '#SMS'])
  })

  it('shows no list of tags when it carries none', async () => {
    await renderInARouter(<ContactCard contact={ada([])} />)

    expect(screen.queryByRole('list', { name: 'Tags de Ada Lovelace' })).not.toBeInTheDocument()
  })
})
