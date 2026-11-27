import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { OrganizationAvatar } from './organization-avatar'

describe('OrganizationAvatar', () => {
  it('shows the initials of the name, letters only', () => {
    render(<OrganizationAvatar name="[TEST] Filon Audit SARL" />)

    expect(screen.getByText('FA')).toBeInTheDocument()
  })
})
