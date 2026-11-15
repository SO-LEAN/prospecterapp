import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Input } from '@/shared/ui/input'
import { Field } from './field'

/** jsdom computes no layout: the class packing the rows of the grid at its top is what keeps them there. */
const ROWS_PACKED_AT_THE_TOP = 'content-start'

function fieldOf(label: string): HTMLElement {
  const field = screen.getByLabelText(label).parentElement
  if (!field) throw new Error(`No field around ${label}`)
  return field
}

describe('Field', () => {
  it('keeps its control under its label when a field beside it is taller', () => {
    render(
      <div className="grid sm:grid-cols-2">
        <Field label="Identifiant du client">
          <Input />
        </Field>
        <Field label="Secret du client" hint="Le secret enregistré n'est jamais réaffiché.">
          <Input />
        </Field>
      </div>,
    )

    expect(fieldOf('Identifiant du client')).toHaveClass(ROWS_PACKED_AT_THE_TOP)
    expect(fieldOf('Secret du client')).toHaveClass(ROWS_PACKED_AT_THE_TOP)
  })

  it('keeps its hint under its control', () => {
    render(
      <Field label="Secret du client" hint="Le secret enregistré n'est jamais réaffiché.">
        <Input />
      </Field>,
    )

    expect(screen.getByLabelText('Secret du client')).toHaveAccessibleDescription(
      "Le secret enregistré n'est jamais réaffiché.",
    )
    expect(screen.getByLabelText('Secret du client').nextElementSibling).toHaveTextContent(
      "Le secret enregistré n'est jamais réaffiché.",
    )
  })
})
