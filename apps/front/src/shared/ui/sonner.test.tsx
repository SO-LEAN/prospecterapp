import { act, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from './sonner'

vi.mock('@/shared/stores/preferences', () => ({ useResolvedTheme: () => 'light' }))

describe('Toaster', () => {
  /** Sonner keeps its toasts from one test to the next until they are closed and gone. */
  afterEach(async () => {
    vi.restoreAllMocks()
    act(() => {
      toast.dismiss()
    })
    await waitFor(() => expect(document.querySelector('[data-sonner-toast]')).toBeNull())
  })

  it('names the cross of a notification « Fermer », in the language of the page', async () => {
    render(<Toaster closeButton />)

    act(() => {
      toast.success('Modification enregistrée')
    })

    expect(await screen.findByRole('button', { name: 'Fermer' })).toBeInTheDocument()
  })

  it('closes its notifications on Escape', async () => {
    render(<Toaster />)
    act(() => {
      toast('Action faite')
    })
    await screen.findByText('Action faite')

    await userEvent.keyboard('{Escape}')

    await waitFor(() => expect(screen.queryByText('Action faite')).not.toBeInTheDocument())
  })

  it('leaves the Escape key alone once gone', async () => {
    const { unmount } = render(<Toaster />)
    unmount()
    const dismiss = vi.spyOn(toast, 'dismiss')

    await userEvent.keyboard('{Escape}')

    expect(dismiss).not.toHaveBeenCalled()
  })
})
