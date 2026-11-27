import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Input } from './input'
import { Select, SelectTrigger, SelectValue } from './select'
import { Textarea } from './textarea'

describe('the fields', () => {
  it('rounds an input by the field radius', () => {
    render(<Input aria-label="Nom" />)

    expect(screen.getByRole('textbox', { name: 'Nom' })).toHaveClass('rounded-field')
  })

  it('rounds a text area by the field radius', () => {
    render(<Textarea aria-label="Notes" />)

    expect(screen.getByRole('textbox', { name: 'Notes' })).toHaveClass('rounded-field')
  })

  it('rounds a select by the field radius', () => {
    render(
      <Select>
        <SelectTrigger aria-label="Pays">
          <SelectValue />
        </SelectTrigger>
      </Select>,
    )

    expect(screen.getByRole('combobox', { name: 'Pays' })).toHaveClass('rounded-field')
  })
})
