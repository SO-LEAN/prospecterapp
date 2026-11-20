import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import type { Account } from '../mutations'
import { ContactDetailsSection } from './account-sections'

function myAccount(contactDetails: Account['contactDetails']): Account {
  return {
    name: { firstName: 'Ada', lastName: 'Lovelace' },
    contactDetails,
    organization: { legalIdentity: { corporateName: 'SO-LEAN' } },
    _links: {
      self: { href: '/api/me', method: 'GET' },
      changeMyContactDetails: { href: '/api/me/contact-details', method: 'PATCH' },
    },
  }
}

function renderTheSection(account: Account) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <ContactDetailsSection account={account} />
    </QueryClientProvider>,
  )
}

describe('my contact details', () => {
  it('open my links in a new tab', () => {
    renderTheSection(
      myAccount({
        email: 'ada@solean-it.test',
        links: [{ source: 'linkedin', identifier: 'ada', address: 'https://www.linkedin.com/in/ada' }],
      }),
    )

    const profile = screen.getByRole('link', { name: /linkedin\.com\/in\/ada$/ })
    expect(profile).toHaveAttribute('href', 'https://www.linkedin.com/in/ada')
    expect(profile).toHaveAttribute('target', '_blank')
  })

  it('let me note my links, never required', async () => {
    const user = userEvent.setup()
    renderTheSection(myAccount({ email: 'ada@solean-it.test', links: [] }))

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sCoordonnées$/ }))

    expect(screen.getByLabelText(/^Ajouter un lien\s?\(facultatif\)$/)).toHaveValue('')
  })
})
