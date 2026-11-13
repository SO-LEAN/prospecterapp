import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Reminders } from '../mutations'
import { remindersQuery } from '../queries'
import { RemindersSection } from './reminders-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const links = {
  self: { href: '/api/me/reminders', method: 'GET' },
  changeMyReminders: { href: '/api/me/reminders', method: 'PATCH' },
}

const reminders = (enabled: boolean, minutesBefore: Reminders['minutesBefore']): Reminders => ({
  enabled,
  minutesBefore,
  _links: links,
})

const NOTIFICATIONS_REFUSED = 'Les notifications sont bloquées dans ce navigateur'

function renderTheSection(shown: Reminders) {
  const queryClient = new QueryClient()
  render(
    <QueryClientProvider client={queryClient}>
      <RemindersSection reminders={shown} />
      <Toaster />
    </QueryClientProvider>,
  )

  return { user: userEvent.setup(), queryClient }
}

function theBrowserNotifications(
  permission: NotificationPermission,
  answer: NotificationPermission = permission,
) {
  const notification = vi.fn()
  vi.stubGlobal(
    'Notification',
    Object.assign(notification, { permission, requestPermission: async () => answer }),
  )
  return notification
}

const sent = async () => {
  const request = theApi.mock.calls[0]?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method, body: await request.clone().json() }
}

afterEach(() => {
  theApi.mockReset()
  vi.unstubAllGlobals()
  toast.dismiss()
})

describe('RemindersSection', () => {
  it('shows whether I am reminded, and how long before', () => {
    renderTheSection(reminders(true, 10))

    expect(screen.getByRole('switch', { name: 'Me rappeler mes échanges planifiés' })).toBeChecked()
    expect(screen.getByRole('combobox', { name: 'Me prévenir' })).toHaveTextContent('10 min avant')
  })

  it('saves at once my reminders turned off, keeping their delay, and shows them as the API answers', async () => {
    theApi.mockResolvedValue(Response.json(reminders(false, 30)))
    const { user, queryClient } = renderTheSection(reminders(true, 30))

    await user.click(screen.getByRole('switch', { name: 'Me rappeler mes échanges planifiés' }))

    expect(await sent()).toEqual({
      path: '/api/me/reminders',
      method: 'PATCH',
      body: { enabled: false, minutesBefore: 30 },
    })
    await waitFor(() =>
      expect(queryClient.getQueryData(remindersQuery().queryKey)).toEqual(reminders(false, 30)),
    )
  })

  it('offers no delay to choose while the reminders are off', () => {
    renderTheSection(reminders(false, 10))

    expect(screen.getByRole('combobox', { name: 'Me prévenir' })).toBeDisabled()
  })

  it('changes nothing without the link to change my reminders', () => {
    renderTheSection({ enabled: true, minutesBefore: 10, _links: { self: links.self } } as Reminders)

    expect(screen.getByRole('switch', { name: 'Me rappeler mes échanges planifiés' })).toBeDisabled()
  })

  it('shows an example of a reminder, as a notification of the browser once allowed', async () => {
    const notification = theBrowserNotifications('default', 'granted')
    const { user } = renderTheSection(reminders(true, 10))

    await user.click(screen.getByRole('button', { name: 'Tester' }))

    expect(await screen.findByText('Rappel : appel avec Ada Lovelace')).toBeInTheDocument()
    await waitFor(() =>
      expect(notification).toHaveBeenCalledWith('Rappel : appel avec Ada Lovelace', {
        body: 'Dans 10 minutes, avec Babbage & Co.',
      }),
    )
    expect(screen.queryByText(NOTIFICATIONS_REFUSED)).not.toBeInTheDocument()
  })

  it('says how to allow the notifications the member refused', async () => {
    const notification = theBrowserNotifications('default', 'denied')
    const { user } = renderTheSection(reminders(true, 10))

    await user.click(screen.getByRole('button', { name: 'Tester' }))

    expect(await screen.findByText(NOTIFICATIONS_REFUSED)).toBeInTheDocument()
    expect(notification).not.toHaveBeenCalled()
  })

  it('says it at once when the notifications were refused before', () => {
    theBrowserNotifications('denied')

    renderTheSection(reminders(true, 10))

    expect(screen.getByText(NOTIFICATIONS_REFUSED)).toBeInTheDocument()
  })
})
