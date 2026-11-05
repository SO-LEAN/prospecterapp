import { act, render, screen } from '@testing-library/react'
import { toast } from 'sonner'
import { describe, expect, it, vi } from 'vitest'
import { Toaster } from './sonner'

vi.mock('@/shared/stores/preferences', () => ({ useResolvedTheme: () => 'light' }))

describe('Toaster', () => {
  it('names the cross of a notification « Fermer », in the language of the page', async () => {
    render(<Toaster closeButton />)

    act(() => {
      toast.success('Modification enregistrée')
    })

    expect(await screen.findByRole('button', { name: 'Fermer' })).toBeInTheDocument()
  })
})
