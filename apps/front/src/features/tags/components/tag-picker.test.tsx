import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import type { SuggestedTags } from '../queries'
import { TagPicker } from './tag-picker'

const suggested: SuggestedTags = {
  families: [
    { family: 'organizationType', tags: ['#APPORTEUR', '#ESN', '#EDITEUR'] },
    { family: 'relationship', tags: ['#CLIENT', '#DECIDEUR'] },
    { family: 'technology', tags: ['#PHP', '#SYMFONY'] },
  ],
  own: ['#SALON_2026'],
  automatic: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN', '#67', '#GRAND_EST', '#STRASBOURG'],
}

function renderThePicker(carried: string[] = [], onPick = vi.fn()) {
  render(<TagPicker suggested={suggested} carried={carried} onPick={onPick} />)
  return onPick
}

const optionsOf = (group: string) =>
  within(screen.getByRole('group', { name: group }))
    .getAllByRole('option')
    .map((option) => option.textContent)

describe('the picker of a tag', () => {
  it('proposes the predefined tags by family, then those of my account, without those already carried', () => {
    renderThePicker(['#ESN'])

    expect(optionsOf('Type d’organisation')).toEqual(['#APPORTEUR', '#EDITEUR'])
    expect(optionsOf('Relation avec le contact')).toEqual(['#CLIENT', '#DECIDEUR'])
    expect(optionsOf('Technos')).toEqual(['#PHP', '#SYMFONY'])
    expect(optionsOf('Mes tags')).toEqual(['#SALON_2026'])
  })

  it('never proposes an automatic tag to put by hand', () => {
    renderThePicker()

    expect(screen.queryByRole('group', { name: 'Automatiques' })).not.toBeInTheDocument()
  })

  it('proposes the automatic tags last to search the contacts, and creates none', async () => {
    render(<TagPicker searching suggested={suggested} carried={['#ESN']} onPick={vi.fn()} />)

    expect(optionsOf('Automatiques')).toEqual([
      '#EMAIL',
      '#PHONE',
      '#SMS',
      '#LINKEDIN',
      '#67',
      '#GRAND_EST',
      '#STRASBOURG',
    ])
    await userEvent.type(screen.getByRole('combobox', { name: 'Tag' }), 'salon pro')
    expect(screen.queryByRole('option', { name: /Créer/ })).not.toBeInTheDocument()
  })

  it('keeps the tags matching what is typed, whatever its case, accents and mark', async () => {
    renderThePicker()

    await userEvent.type(screen.getByRole('combobox', { name: 'Tag' }), '#édi')

    expect(screen.getAllByRole('option').map((option) => option.textContent)).toEqual([
      '#EDITEUR',
      'Créer « #édi »',
    ])
  })

  it('picks a tag proposed', async () => {
    const onPick = renderThePicker()

    await userEvent.click(screen.getByRole('option', { name: '#SALON_2026' }))

    expect(onPick).toHaveBeenCalledWith('#SALON_2026')
  })

  it('creates the tag typed, by its option or by Enter', async () => {
    const onPick = renderThePicker()

    await userEvent.type(screen.getByRole('combobox', { name: 'Tag' }), 'salon pro')
    await userEvent.click(screen.getByRole('option', { name: 'Créer « salon pro »' }))
    await userEvent.type(screen.getByRole('combobox', { name: 'Tag' }), 'salon pro{Enter}')

    expect(onPick.mock.calls).toEqual([['salon pro'], ['salon pro']])
  })

  it('offers no creation of a tag already proposed', async () => {
    renderThePicker()

    await userEvent.type(screen.getByRole('combobox', { name: 'Tag' }), 'esn')

    expect(screen.getAllByRole('option').map((option) => option.textContent)).toEqual(['#ESN'])
  })
})
