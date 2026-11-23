import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Input } from '@/shared/ui/input'
import { Field } from './field'

/** jsdom computes no layout. The class that packs the grid's rows at its top is what keeps them there. */
const ROWS_PACKED_AT_THE_TOP = 'content-start'
/** Otherwise the optional mention's smaller text would bring its taller line, and the label would sit two pixels higher. */
const LINE_AS_HIGH_AS_THE_LABEL = 'leading-none'

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

  it('keeps its control at the height of the one beside it when it is optional', () => {
    render(
      <div className="grid sm:grid-cols-2">
        <Field label="E-mail">
          <Input />
        </Field>
        <Field label="Téléphone" optional="facultatif">
          <Input />
        </Field>
      </div>,
    )

    expect(screen.getByText('(facultatif)')).toHaveClass(LINE_AS_HIGH_AS_THE_LABEL)
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
