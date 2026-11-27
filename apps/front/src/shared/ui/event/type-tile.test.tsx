import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import type { Channel } from './channel'
import { TypeTile } from './type-tile'

describe('TypeTile', () => {
  it('tints a call in the calls family, draws its handset, and says it', () => {
    render(<TypeTile channel="call" direction="outbound" />)

    const tile = screen.getByRole('img', { name: 'Appel, sortant' })
    expect(tile).toHaveClass('bg-event-call-soft', 'text-event-call')
    expect(tile.querySelector('.lucide-phone')).toBeInTheDocument()
  })

  it('is a 26 px rounded tile, its icon centred', () => {
    render(<TypeTile channel="call" direction="outbound" />)

    expect(screen.getByRole('img')).toHaveClass('size-6.5', 'rounded-md', 'items-center', 'justify-center')
  })

  it.each<[Channel, string, string, string]>([
    ['meeting', 'Rendez-vous', 'meeting', 'calendar-days'],
    ['videoCall', 'Visio', 'video', 'video'],
    ['email', 'E-mail', 'written', 'mail'],
    ['sms', 'SMS', 'written', 'message-square-text'],
    ['linkedIn', 'LinkedIn', 'written', 'briefcase-business'],
    ['malt', 'Malt', 'written', 'handshake'],
    ['other', 'Autre', 'written', 'message-circle'],
  ])('tints a %s in the %s family, and draws its own icon', (channel, name, family, icon) => {
    render(<TypeTile channel={channel} direction="inbound" />)

    const tile = screen.getByRole('img', { name: `${name}, entrant` })
    expect(tile).toHaveClass(`bg-event-${family}-soft`, `text-event-${family}`)
    expect(tile.querySelector(`.lucide-${icon}`)).toBeInTheDocument()
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
