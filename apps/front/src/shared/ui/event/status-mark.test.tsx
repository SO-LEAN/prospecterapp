import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { type ExchangeStatus, StatusMark } from './status-mark'

describe('StatusMark', () => {
  it.each<[ExchangeStatus, string, string]>([
    ['planned', 'À venir', 'clock'],
    ['done', 'Fait', 'check'],
    ['missed', 'Manqué', 'x'],
    ['cancelled', 'Annulé', 'ban'],
  ])('says an exchange %s as « %s », with its icon', (status, name, icon) => {
    render(<StatusMark status={status} />)

    expect(screen.getByRole('img', { name }).querySelector(`.lucide-${icon}`)).toBeInTheDocument()
  })

  it('says a late exchange is late, with a warning, whatever its status', () => {
    render(<StatusMark status="planned" late />)

    expect(
      screen.getByRole('img', { name: 'En retard' }).querySelector('.lucide-triangle-alert'),
    ).toBeInTheDocument()
  })
})
