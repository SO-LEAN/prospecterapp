import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { OrganizationBadges } from './organization-badges'

const identity = { corporateName: 'ACME', form: 'SAS', type: 'ESN', siret: null }

describe('OrganizationBadges', () => {
  it('shows its legal form and its type', () => {
    render(<OrganizationBadges organization={{ legalIdentity: identity, createdByAnAI: false }} />)

    expect(screen.getByText('SAS')).toBeInTheDocument()
    expect(screen.getByText('ESN')).toBeInTheDocument()
  })

  it('badges an organization an AI started prospecting, without naming the AI', () => {
    render(<OrganizationBadges organization={{ legalIdentity: identity, createdByAnAI: true }} />)

    expect(screen.getByTitle('Créé par une IA')).toHaveTextContent('MCP')
  })

  it('does not badge an organization started by hand', () => {
    render(<OrganizationBadges organization={{ legalIdentity: identity, createdByAnAI: false }} />)

    expect(screen.queryByTitle('Créé par une IA')).not.toBeInTheDocument()
  })
})
