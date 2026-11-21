import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { organizationTimelineQuery, timelineQuery } from '../queries'
import { CatchUpGmail } from './catch-up-gmail'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const GLOBEX = '0190a1c0-0000-7000-8000-000000000002'

function renderTheButton(correspondent: { contactId: string } | { organizationId: string }) {
  const queryClient = new QueryClient()
  const invalidated = vi.spyOn(queryClient, 'invalidateQueries')
  render(
    <QueryClientProvider client={queryClient}>
      <CatchUpGmail {...correspondent} />
    </QueryClientProvider>,
  )

  return { user: userEvent.setup(), invalidated }
}

const sent = () => {
  const request = theApi.mock.calls[0]?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method }
}

afterEach(() => {
  theApi.mockReset()
  vi.mocked(toast.success).mockReset()
})

describe('CatchUpGmail', () => {
  it('catches up the e-mails exchanged with the contact, says it started and reads the timeline again', async () => {
    theApi.mockResolvedValue(new Response(null, { status: 202 }))
    const { user, invalidated } = renderTheButton({ contactId: ADA })

    await user.click(screen.getByRole('button', { name: 'Rattraper Gmail' }))

    expect(sent()).toEqual({ path: `/api/contacts/${ADA}/gmail-reimports`, method: 'POST' })
    expect(toast.success).toHaveBeenCalledWith('Rattrapage lancé : les e-mails arrivent dans la timeline.')
    expect(invalidated).toHaveBeenCalledWith(
      expect.objectContaining({ queryKey: timelineQuery(ADA).queryKey }),
      expect.anything(),
    )
  })

  it('catches up the e-mails exchanged with the organization itself into its timeline', async () => {
    theApi.mockResolvedValue(new Response(null, { status: 202 }))
    const { user, invalidated } = renderTheButton({ organizationId: GLOBEX })

    await user.click(screen.getByRole('button', { name: 'Rattraper Gmail' }))

    expect(sent()).toEqual({ path: `/api/organizations/${GLOBEX}/gmail-reimports`, method: 'POST' })
    expect(invalidated).toHaveBeenCalledWith(
      expect.objectContaining({ queryKey: organizationTimelineQuery(GLOBEX).queryKey }),
      expect.anything(),
    )
  })

  it('shows that Gmail is being read while the catch-up is asked', async () => {
    theApi.mockReturnValue(new Promise(() => {}))
    const { user } = renderTheButton({ contactId: ADA })

    await user.click(screen.getByRole('button', { name: 'Rattraper Gmail' }))

    expect(await screen.findByRole('button', { name: 'Lecture de Gmail…' })).toBeDisabled()
  })
})
