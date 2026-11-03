import { render, screen } from '@testing-library/react'
import { type FieldErrors, useForm } from 'react-hook-form'
import { describe, expect, it } from 'vitest'
import { AddressFields, type AddressValues, toAddressValues } from './address'
import { ContactDetailsFields, type ContactDetailsValues } from './contact-details'

function AnAddress({ errors }: { errors?: FieldErrors<AddressValues> }) {
  const form = useForm<AddressValues>({ defaultValues: toAddressValues(null), errors })
  return <AddressFields form={form} />
}

function SomeContactDetails() {
  const form = useForm<ContactDetailsValues>({
    defaultValues: { email: '', phoneNumber: '', language: null },
  })
  return <ContactDetailsFields form={form} />
}

describe('the list of the countries', () => {
  it('is named after the country of an address, its label being the one of the list', () => {
    render(<AnAddress />)

    expect(screen.getByLabelText('Pays')).toHaveRole('combobox')
  })

  it('is named after the language of a contact', () => {
    render(<SomeContactDetails />)

    expect(screen.getByRole('combobox', { name: /^Pays de la langue ?\(facultatif\)$/ })).toBeInTheDocument()
  })

  it('tells the error of its field', () => {
    render(<AnAddress errors={{ country: { type: 'server', message: 'Pays inconnu.' } }} />)

    const theCountries = screen.getByRole('combobox', { name: 'Pays' })
    expect(theCountries).toBeInvalid()
    expect(theCountries).toHaveAccessibleDescription('Pays inconnu.')
  })
})
