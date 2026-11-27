import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import type { Channel } from './channel'
import { EventPill } from './event-pill'
import type { ExchangeStatus } from './status-mark'

const thePill = () => screen.getByText('11h00').closest('[data-slot="event-pill"]')

function renderThePill(channel: Channel, status: ExchangeStatus, late = false) {
  render(
    <EventPill channel={channel} status={status} late={late}>
      11h00
    </EventPill>,
  )
}

describe('EventPill', () => {
  it('fills an upcoming call with the calls tint, its text white', () => {
    renderThePill('call', 'planned')

    expect(thePill()).toHaveClass('bg-event-call', 'text-primary-foreground')
    expect(screen.getByRole('img', { name: 'À venir' }).querySelector('.lucide-clock')).toBeInTheDocument()
  })

  it('gives a done video call the video calls soft tint, with a check', () => {
    renderThePill('videoCall', 'done')

    expect(thePill()).toHaveClass('bg-event-video-soft', 'text-event-video')
    expect(screen.getByRole('img', { name: 'Fait' }).querySelector('.lucide-check')).toBeInTheDocument()
  })

  it('gives a done e-mail the written channels soft tint, with a check', () => {
    renderThePill('email', 'done')

    expect(thePill()).toHaveClass('bg-event-written-soft', 'text-event-written')
    expect(thePill()?.querySelector('.lucide-mail')).toBeInTheDocument()
    expect(screen.getByRole('img', { name: 'Fait' })).toBeInTheDocument()
  })

  it('hatches a missed meeting in red, with a red rule, on the meetings tint', () => {
    renderThePill('meeting', 'missed')

    expect(thePill()).toHaveClass('missed-hatching', 'border-l-2', 'border-destructive', 'text-event-meeting')
    expect(screen.getByRole('img', { name: 'Manqué' }).querySelector('.lucide-x')).toBeInTheDocument()
  })

  it('outlines a cancelled call in dashes, its text grey and struck through', () => {
    renderThePill('call', 'cancelled')

    expect(thePill()).toHaveClass('border', 'border-dashed', 'text-muted-foreground', 'line-through')
    expect(thePill()).not.toHaveClass('bg-event-call')
    expect(screen.getByRole('img', { name: 'Annulé' }).querySelector('.lucide-ban')).toBeInTheDocument()
  })

  it('edges a late exchange in red, and says it is late', () => {
    renderThePill('call', 'planned', true)

    expect(thePill()).toHaveClass('bg-event-call', 'border-l-4', 'border-destructive')
    expect(
      screen.getByRole('img', { name: 'En retard' }).querySelector('.lucide-triangle-alert'),
    ).toBeInTheDocument()
  })
})
