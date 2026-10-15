import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { EditableSection } from '@/shared/ui/editable-section'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
} from './contact-details'
import { SectionForm } from './section-form'

function renderSection(save = vi.fn().mockResolvedValue(undefined)) {
  render(
    <EditableSection
      title="Coordonnées"
      editor={(close) => (
        <SectionForm
          schema={contactDetailsSchema}
          defaultValues={{ email: 'contact@acme.test', phoneNumber: '', language: null }}
          fields={contactDetailsFields}
          save={(values) => save(toContactDetails(values))}
          onDone={close}
        >
          {(form) => <ContactDetailsFields form={form} />}
        </SectionForm>
      )}
    >
      contact@acme.test
    </EditableSection>,
  )
  return { save, user: userEvent.setup() }
}

describe('an editable section', () => {
  it('sends only its own part, then shows it again', async () => {
    const { save, user } = renderSection()

    await user.click(screen.getByRole('button', { name: 'Modifier : Coordonnées' }))
    await user.type(screen.getByLabelText(/^Téléphone/), '0388000000')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(save).toHaveBeenCalledWith({
      email: 'contact@acme.test',
      phoneNumber: '0388000000',
      language: null,
    })
    expect(await screen.findByRole('button', { name: 'Modifier : Coordonnées' })).toBeInTheDocument()
  })

  it('stays open with the API violations on its fields', async () => {
    const { user } = renderSection(
      vi.fn().mockRejectedValue({
        title: 'Validation Failed',
        status: 422,
        detail: 'Email already used',
        violations: [{ propertyPath: 'email', title: 'Email already used' }],
      }),
    )

    await user.click(screen.getByRole('button', { name: 'Modifier : Coordonnées' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Email already used')).toBeInTheDocument()
    expect(screen.getByLabelText('E-mail')).toHaveAttribute('aria-invalid', 'true')
  })

  it('can be left without saving', async () => {
    const { save, user } = renderSection()

    await user.click(screen.getByRole('button', { name: 'Modifier : Coordonnées' }))
    await user.click(screen.getByRole('button', { name: 'Annuler' }))

    expect(save).not.toHaveBeenCalled()
    expect(screen.getByText('contact@acme.test')).toBeInTheDocument()
  })
})
