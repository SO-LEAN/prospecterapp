import { render, screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { EventCatalogue } from './event-catalogue'

describe('EventCatalogue', () => {
  it('shows each channel in each status', () => {
    render(<EventCatalogue />)

    const pills = within(screen.getByRole('table', { name: 'Canal et statut' }))
    expect(pills.getAllByRole('img', { name: 'À venir' })).toHaveLength(8)
    expect(pills.getAllByRole('img', { name: 'Fait' })).toHaveLength(8)
    expect(pills.getAllByRole('img', { name: 'Manqué' })).toHaveLength(8)
    expect(pills.getAllByRole('img', { name: 'Annulé' })).toHaveLength(8)
    expect(pills.getAllByRole('img', { name: 'En retard' })).toHaveLength(8)
  })

  it('heads its columns with the statuses and its rows with the channels', () => {
    render(<EventCatalogue />)

    const pills = within(screen.getByRole('table', { name: 'Canal et statut' }))
    expect(pills.getAllByRole('columnheader').map((header) => header.textContent)).toEqual([
      'Canal',
      'À venir',
      'Fait',
      'Manqué',
      'Annulé',
      'En retard',
    ])
    expect(pills.getAllByRole('rowheader').map((header) => header.textContent)).toEqual([
      'Appel',
      'SMS',
      'E-mail',
      'LinkedIn',
      'Malt',
      'Rendez-vous',
      'Visio',
      'Autre',
    ])
    expect(pills.getAllByText('11h00')).toHaveLength(40)
  })

  it('shows each channel tile, both ways', () => {
    render(<EventCatalogue />)

    const tiles = within(screen.getByRole('region', { name: 'Tuiles de type' }))
    expect(tiles.getByRole('img', { name: 'Appel, entrant' })).toBeInTheDocument()
    expect(tiles.getByRole('img', { name: 'Autre, sortant' })).toBeInTheDocument()
    expect(tiles.getAllByRole('img')).toHaveLength(16)
  })

  it('shows a to-do to do, done and late', () => {
    render(<EventCatalogue />)

    const toDos = within(screen.getByRole('region', { name: 'Actions à faire' }))
    expect(toDos.getByRole('img', { name: 'À faire' })).toBeInTheDocument()
    expect(toDos.getByRole('img', { name: 'Faite' })).toBeInTheDocument()
    expect(toDos.getByRole('img', { name: 'En retard' })).toBeInTheDocument()
    expect(toDos.getAllByText('Relancer')).toHaveLength(3)
  })

  it('shows the late to-do still to do, not struck through', () => {
    render(<EventCatalogue />)

    const toDos = within(screen.getByRole('region', { name: 'Actions à faire' }))
    const late = toDos.getByRole('img', { name: 'En retard' }).closest('[data-slot="to-do-pill"]')
    expect(late).toHaveClass('border-event-todo')
    expect(late).not.toHaveClass('line-through')
  })

  it('shows every marker', () => {
    render(<EventCatalogue />)

    const markers = within(screen.getByRole('region', { name: 'Marqueurs' }))
    expect(markers.getByText('IA')).toBeInTheDocument()
    expect(markers.getByRole('img', { name: 'Importé de Gmail' })).toBeInTheDocument()
    expect(markers.getByText('Campagne : Salon')).toBeInTheDocument()
    expect(markers.getByText('Campagne effacée')).toBeInTheDocument()
    expect(markers.getByRole('img', { name: 'Réservé par lien' })).toBeInTheDocument()
    expect(markers.getByText('À rattacher')).toBeInTheDocument()
    expect(markers.getByText('À mettre en conformité')).toBeInTheDocument()
    expect(markers.getByText('En retard')).toBeInTheDocument()
    expect(markers.getByRole('img', { name: /^Synchronisé avec Google/ })).toBeInTheDocument()
    const pending = markers.getByRole('img', { name: 'En attente de synchronisation avec Google.' })
    expect(pending.querySelector('.lucide-clock')).toBeInTheDocument()
    expect(markers.getByRole('img', { name: /^Google a refusé/ })).toBeInTheDocument()
  })
})
