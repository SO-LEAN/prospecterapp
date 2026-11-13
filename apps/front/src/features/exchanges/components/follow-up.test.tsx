import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { NoteThenFollowUp } from './follow-up'

/** The plan form reads the exchanges it overlaps: none. */
vi.hoisted(() => {
  globalThis.fetch = async () => Response.json({ items: [], _links: { self: { href: '/', method: 'GET' } } })
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

type Plan = Parameters<typeof NoteThenFollowUp>[0]['plan']

async function renderTheNoting(plan: Plan) {
  const note = vi.fn(async () => undefined)
  const close = vi.fn()
  await renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <NoteThenFollowUp note={note} plan={plan} close={close} />
    </QueryClientProvider>,
  )
  const user = userEvent.setup()
  await user.click(screen.getByLabelText('Rendez-vous'))
  await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

  return { user, note, close }
}

describe('NoteThenFollowUp', () => {
  it('offers to plan what follows once the exchange is noted, its channel proposed again', async () => {
    const { user, note, close } = await renderTheNoting(vi.fn(async () => undefined))

    expect(note).toHaveBeenCalledOnce()
    expect(await screen.findByText(/Planifier la suite avec ce même interlocuteur/)).toBeInTheDocument()
    expect(close).not.toHaveBeenCalled()
    await user.click(screen.getByRole('button', { name: 'Planifier la suite' }))
    expect(screen.getByLabelText('Rendez-vous')).toBeChecked()
  })

  it('plans what follows with the same interlocutor, then closes', async () => {
    const plan = vi.fn(async () => undefined)
    const { user, close } = await renderTheNoting(plan)

    await user.click(await screen.findByRole('button', { name: 'Planifier la suite' }))
    await user.click(screen.getByRole('button', { name: 'Demain 9h' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(plan).toHaveBeenCalledWith(expect.objectContaining({ channel: 'meeting' }), null)
    expect(close).toHaveBeenCalledOnce()
  })

  it('closes once the exchange is noted when planning is not offered', async () => {
    const { close } = await renderTheNoting(undefined)

    expect(close).toHaveBeenCalledOnce()
    expect(screen.queryByText(/Planifier la suite/)).not.toBeInTheDocument()
  })

  it('closes when what follows is left for later', async () => {
    const plan = vi.fn(async () => undefined)
    const { user, close } = await renderTheNoting(plan)

    await user.click(await screen.findByRole('button', { name: 'Plus tard' }))

    expect(close).toHaveBeenCalledOnce()
    expect(plan).not.toHaveBeenCalled()
  })
})
