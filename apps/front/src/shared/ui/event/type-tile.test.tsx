import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import type { Channel } from './channel'
import { TypeTile } from './type-tile'

describe('TypeTile', () => {
  it('tints a call in the calls family, and says it', () => {
    render(<TypeTile channel="call" direction="outbound" />)

    const tile = screen.getByRole('img', { name: 'Appel, sortant' })
    expect(tile).toHaveClass('bg-event-call-soft', 'text-event-call')
  })

  it.each<[Channel, string, string]>([
    ['meeting', 'Rendez-vous', 'meeting'],
    ['videoCall', 'Visio', 'video'],
    ['email', 'E-mail', 'written'],
    ['sms', 'SMS', 'written'],
    ['linkedIn', 'LinkedIn', 'written'],
    ['malt', 'Malt', 'written'],
    ['other', 'Autre', 'written'],
  ])('tints a %s in the %s family', (channel, name, family) => {
    render(<TypeTile channel={channel} direction="inbound" />)

    const tile = screen.getByRole('img', { name: `${name}, entrant` })
    expect(tile).toHaveClass(`bg-event-${family}-soft`, `text-event-${family}`)
  })

  it('points its arrow out when the account reached out', () => {
    render(<TypeTile channel="call" direction="outbound" />)

    expect(screen.getByRole('img').querySelector('.lucide-arrow-up-right')).toBeInTheDocument()
  })

  it('points its arrow in when the contact reached out', () => {
    render(<TypeTile channel="call" direction="inbound" />)

    expect(screen.getByRole('img').querySelector('.lucide-arrow-down-left')).toBeInTheDocument()
  })
})
