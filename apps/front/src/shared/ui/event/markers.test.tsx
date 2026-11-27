import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { ExchangeMarkers, type Marked, ToAttachMarker } from './markers'

const CAMPAIGN = '0190a1c0-0000-7000-8000-00000000ca01'

const NOTHING_TO_MARK: Marked = {
  createdByAnAI: false,
  importedFrom: null,
  campaign: null,
  bookedByLink: false,
  contactToAttach: false,
  toRedact: false,
}

describe('ExchangeMarkers', () => {
  it('badges an exchange an AI logged « IA »', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, createdByAnAI: true }} />)

    expect(screen.getByText('IA')).toHaveAccessibleDescription('Créé par une IA')
  })

  it('keeps its markers small, never squeezed', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, createdByAnAI: true }} />)

    expect(screen.getByText('IA')).toHaveClass('shrink-0', 'px-1', 'text-[0.65rem]')
  })

  it('marks nothing when the API gives no marker', () => {
    const { container } = render(<ExchangeMarkers exchange={NOTHING_TO_MARK} />)

    expect(container).toBeEmptyDOMElement()
  })

  it('marks an exchange imported from Gmail with its logo', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, importedFrom: 'google' }} />)

    expect(screen.getByRole('img', { name: 'Importé de Gmail' })).toBeInTheDocument()
  })

  it('marks an exchange booked through the public link with a link', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, bookedByLink: true }} />)

    const marker = screen.getByRole('img', { name: 'Réservé par lien' })
    expect(marker.querySelector('.lucide-link')).toBeInTheDocument()
    expect(marker).toHaveAttribute('aria-label', 'Réservé par lien')
    expect(marker).toHaveAttribute('title', 'Réservé par lien')
  })

  it('marks an exchange whose contact waits to be attached « À rattacher »', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, contactToAttach: true }} />)

    expect(screen.getByText('À rattacher')).toHaveClass('border-warning')
  })

  it('marks nothing « À rattacher » once the contact is attached, wherever the marker shows', () => {
    const { container } = render(<ToAttachMarker exchange={{ contactToAttach: false }} />)

    expect(container).toBeEmptyDOMElement()
  })

  it('marks « À rattacher » wherever the marker shows, when the API says so', () => {
    render(<ToAttachMarker exchange={{ contactToAttach: true }} />)

    expect(screen.getByText('À rattacher')).toHaveClass('border-warning')
  })

  it('outlines in red an exchange whose personal data are to redact', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, toRedact: true }} />)

    expect(screen.getByText('À mettre en conformité')).toHaveClass('border-destructive', 'text-destructive')
  })

  it('marks an exchange logged in a campaign with a megaphone and its name', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, campaign: { id: CAMPAIGN, name: 'Salon' } }} />)

    const marker = screen.getByText('Campagne : Salon')
    expect(marker.querySelector('.lucide-megaphone')).toBeInTheDocument()
  })

  it('says the campaign is erased once the operations erased it', () => {
    render(<ExchangeMarkers exchange={{ ...NOTHING_TO_MARK, campaign: { id: CAMPAIGN, name: null } }} />)

    expect(screen.getByText('Campagne effacée')).toBeInTheDocument()
  })

  it('marks an exchange synced with Google with a check, and since when', () => {
    render(
      <ExchangeMarkers
        exchange={NOTHING_TO_MARK}
        sync={{ provider: 'google', state: 'synced', at: '2026-11-16T09:00:00+00:00', reason: null }}
      />,
    )

    const marker = screen.getByRole('img', { name: /^Synchronisé avec Google le lun\. 16 nov\. · / })
    expect(marker.querySelector('.lucide-circle-check')).toBeInTheDocument()
    expect(marker).toHaveClass('flex', 'items-center', 'shrink-0')
  })

  it('marks an exchange waiting for Google with a clock', () => {
    render(
      <ExchangeMarkers
        exchange={NOTHING_TO_MARK}
        sync={{ provider: 'google', state: 'pending', at: null, reason: null }}
      />,
    )

    const marker = screen.getByRole('img', { name: 'En attente de synchronisation avec Google.' })
    expect(marker.querySelector('.lucide-clock')).toBeInTheDocument()
  })

  it('says an exchange waits for Google because the member is to reconnect', () => {
    render(
      <ExchangeMarkers
        exchange={NOTHING_TO_MARK}
        sync={{ provider: 'google', state: 'pending', at: null, reason: 'toReconnect' }}
      />,
    )

    expect(
      screen.getByRole('img', {
        name: 'En attente de synchronisation avec Google\u00a0: votre compte Google est à reconnecter.',
      }),
    ).toBeInTheDocument()
  })

  it('marks an exchange Google refused with a warning, and since when', () => {
    render(
      <ExchangeMarkers
        exchange={NOTHING_TO_MARK}
        sync={{
          provider: 'google',
          state: 'failed',
          at: '2026-11-16T09:00:00+00:00',
          reason: 'calendarRefusedThePush',
        }}
      />,
    )

    const marker = screen.getByRole('img', { name: /^Google a refusé l’événement le lun\. 16 nov\. · / })
    expect(marker.querySelector('.lucide-circle-alert')).toBeInTheDocument()
  })

  it('edges a late exchange in red, and says it is late', () => {
    render(<ExchangeMarkers exchange={NOTHING_TO_MARK} late />)

    expect(screen.getByText('En retard')).toHaveClass('border-l-4', 'border-destructive')
  })
})
