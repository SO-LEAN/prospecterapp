import { act, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from './sonner'
import { UNDO_TOAST_DURATION_MS, useUndoToast } from './undo-toast'

vi.mock('@/shared/stores/preferences', () => ({ useResolvedTheme: () => 'light' }))

/** How long sonner lets a toast leave before it takes it out of the page. */
const LEAVING = 400

function Gesture({ undo }: { undo?: () => void }) {
  const showTheUndoToast = useUndoToast()
  return (
    <button type="button" onClick={() => showTheUndoToast('Action faite', undo)}>
      Cocher
    </button>
  )
}

async function aGestureJustDone(undo?: () => void) {
  render(
    <>
      <Gesture undo={undo} />
      <Toaster closeButton />
    </>,
  )
  await userEvent.click(screen.getByRole('button', { name: 'Cocher' }))
  return screen.findByText('Action faite')
}

describe('the undo toast', () => {
  /** Sonner keeps its toasts from one test to the next until they are closed and gone. */
  afterEach(async () => {
    vi.useRealTimers()
    act(() => {
      toast.dismiss()
    })
    await waitFor(() => expect(document.querySelector('[data-sonner-toast]')).toBeNull())
  })

  it('offers « Annuler », which undoes the gesture', async () => {
    const undo = vi.fn()
    await aGestureJustDone(undo)

    await userEvent.click(screen.getByRole('button', { name: 'Annuler' }))

    expect(undo).toHaveBeenCalledOnce()
  })

  it('stays 6 seconds', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    await aGestureJustDone(vi.fn())

    await act(() => vi.advanceTimersByTimeAsync(UNDO_TOAST_DURATION_MS - 100))
    expect(screen.getByText('Action faite')).toBeInTheDocument()

    await act(() => vi.advanceTimersByTimeAsync(100 + LEAVING))
    expect(screen.queryByText('Action faite')).not.toBeInTheDocument()
    expect(UNDO_TOAST_DURATION_MS).toBe(6000)
  })

  it('closes on Escape, without undoing', async () => {
    const undo = vi.fn()
    await aGestureJustDone(undo)

    await userEvent.keyboard('{Escape}')

    await vi.waitFor(() => expect(screen.queryByText('Action faite')).not.toBeInTheDocument())
    expect(undo).not.toHaveBeenCalled()
  })

  it('closes by its cross, without undoing', async () => {
    const undo = vi.fn()
    await aGestureJustDone(undo)

    await userEvent.click(screen.getByRole('button', { name: 'Fermer' }))

    await vi.waitFor(() => expect(screen.queryByText('Action faite')).not.toBeInTheDocument())
    expect(undo).not.toHaveBeenCalled()
  })

  it('offers nothing to undo when the gesture cannot be undone', async () => {
    await aGestureJustDone()

    expect(screen.queryByRole('button', { name: 'Annuler' })).not.toBeInTheDocument()
  })
})
