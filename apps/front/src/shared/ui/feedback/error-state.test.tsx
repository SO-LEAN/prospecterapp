import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { ErrorState } from './error-state'

const forbidden = {
  title: 'Forbidden',
  status: 403,
  detail: 'Your role does not allow this action.',
  reason: 'roleNotAllowed',
}

describe('ErrorState', () => {
  it('tells a refusal of my role in French, once, without offering to retry what cannot succeed', () => {
    render(<ErrorState error={forbidden} reset={() => {}} />)

    expect(
      screen.getByRole('heading', { name: 'Votre rôle ne vous permet pas d’ouvrir cette page.' }),
    ).toBeInTheDocument()
    expect(screen.queryByText('Your role does not allow this action.')).not.toBeInTheDocument()
    expect(screen.queryByText('Votre rôle ne vous permet pas cette action.')).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Réessayer' })).not.toBeInTheDocument()
  })

  it('tells the right my role lacks when the refusal names it', () => {
    render(<ErrorState error={{ ...forbidden, reason: 'personalDataManagersOnly' }} reset={() => {}} />)

    expect(screen.getByText('Il faut le droit de gérer les données personnelles.')).toBeInTheDocument()
  })

  it('offers to retry a failure that may pass', () => {
    render(<ErrorState error={new TypeError('Failed to fetch')} reset={() => {}} />)

    expect(screen.getByRole('heading', { name: 'Une erreur est survenue' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Réessayer' })).toBeInTheDocument()
  })
})
