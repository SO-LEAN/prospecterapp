import { render, screen, within } from '@testing-library/react'
import { Building2 } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { describe, expect, it } from 'vitest'
import { Choice, Choices } from './choices'

function AChoice({ error }: { error?: string }) {
  const form = useForm<{ channel: string }>({ defaultValues: { channel: '' } })
  return (
    <Choices legend="Canal" error={error}>
      <Choice icon={Building2} label="Appel" value="call" {...form.register('channel')} />
    </Choices>
  )
}

describe('the choices', () => {
  it('say their field is invalid, and why', () => {
    render(<AChoice error="Choisissez un canal." />)

    expect(screen.getByRole('group', { name: 'Canal' })).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByText('Choisissez un canal.')).toBeInTheDocument()
  })

  it('say nothing of their field while it is valid', () => {
    render(<AChoice />)

    expect(screen.getByRole('group', { name: 'Canal' })).not.toHaveAttribute('aria-invalid')
    expect(
      within(screen.getByRole('group', { name: 'Canal' })).queryByRole('paragraph'),
    ).not.toBeInTheDocument()
  })
})
