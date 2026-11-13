import { act, renderHook } from '@testing-library/react'
import { toast } from 'sonner'
import { describe, expect, it, vi } from 'vitest'
import { useNoteTheExchangeProposal } from './note-the-exchange'
import type { ToDo } from './queries'

vi.mock('sonner', () => ({ toast: vi.fn() }))

const sent: ToDo = {
  id: '0190a1c0-0000-7000-8000-000000000001',
  text: 'Envoyer mon CV',
  dueOn: '2026-11-14',
  dueAt: null,
  doneAt: '2026-11-13T10:00:00+00:00',
  heldBy: { name: { firstName: 'Michel', lastName: 'Maier' }, email: null },
  contact: { id: '0190a1c0-0000-7000-8000-000000000002', name: { firstName: 'Ada', lastName: 'Lovelace' } },
  _links: { self: { href: '/api/to-dos/0190a1c0-0000-7000-8000-000000000001', method: 'GET' } },
}

describe('the proposal to note the exchange a to-do led to', () => {
  it('is ignored: nothing is opened', () => {
    const { result } = renderHook(useNoteTheExchangeProposal)

    act(() => result.current.propose(sent))

    expect(toast).toHaveBeenCalledWith(
      'Noter l’échange ?',
      expect.objectContaining({ description: 'Envoyer mon CV' }),
    )
    expect(result.current.accepted).toBeNull()
  })

  it('is accepted: the quick entry of an exchange opens for the to-do, until closed', () => {
    const { result } = renderHook(useNoteTheExchangeProposal)
    act(() => result.current.propose(sent))
    const action = vi.mocked(toast).mock.lastCall?.[1]?.action as unknown as { onClick: () => void }

    act(() => action.onClick())
    expect(result.current.accepted).toBe(sent)

    act(() => result.current.close())
    expect(result.current.accepted).toBeNull()
  })
})
