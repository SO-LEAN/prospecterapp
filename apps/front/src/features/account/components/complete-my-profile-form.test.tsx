import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '../mutations'
import { accountQuery } from '../queries'
import { CompleteMyProfileForm } from './complete-my-profile-form'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const LOGIN_EMAIL = 'ada@babbage.test'

const anEmptyProfile: Account = {
  name: { firstName: null, lastName: null },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const completed = (firstName: string, lastName: string, email: string): Account => ({
  ...anEmptyProfile,
  name: { firstName, lastName },
  contactDetails: { ...anEmptyProfile.contactDetails, email },
})

const emailAlreadyUsed = {
  type: 'about:blank',
  title: 'Email already used',
  status: 409,
  reason: 'emailAlreadyUsed',
  violations: [{ propertyPath: 'email', title: 'Email already used', reason: 'emailAlreadyUsed' }],
}

function renderTheForm() {
  const queryClient = new QueryClient()
  const onCompleted = vi.fn()
  render(
    <QueryClientProvider client={queryClient}>
      <CompleteMyProfileForm account={anEmptyProfile} loginEmail={LOGIN_EMAIL} onCompleted={onCompleted} />
    </QueryClientProvider>,
  )

  return { user: userEvent.setup(), queryClient, onCompleted }
}

async function fillInMyName(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByLabelText('Prénom'), 'Ada')
  await user.type(screen.getByLabelText('Nom'), 'Lovelace')
}

const sent = (call: number) => {
  const request = theApi.mock.calls[call]?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method, body: request.clone().json() }
}

afterEach(() => {
  theApi.mockReset()
})

describe('CompleteMyProfileForm', () => {
  it('proposes the email the member signs in with for their profile', () => {
    renderTheForm()

    expect(screen.getByLabelText('E-mail')).toHaveValue(LOGIN_EMAIL)
    expect(screen.getByLabelText('Prénom')).toHaveValue('')
    expect(screen.getByLabelText('Nom')).toHaveValue('')
  })

  it('asks for the first name and the name', async () => {
    const { user } = renderTheForm()

    await user.click(screen.getByRole('button', { name: 'Continuer' }))

    expect(screen.getByLabelText('Prénom')).toHaveAccessibleDescription('Ce champ est obligatoire.')
    expect(screen.getByLabelText('Nom')).toHaveAccessibleDescription('Ce champ est obligatoire.')
    expect(theApi).not.toHaveBeenCalled()
  })

  it('gives the profile the email proposed, then the name, and goes on', async () => {
    theApi
      .mockResolvedValueOnce(Response.json(completed('', '', LOGIN_EMAIL)))
      .mockResolvedValueOnce(Response.json(completed('Ada', 'Lovelace', LOGIN_EMAIL)))
    const { user, queryClient, onCompleted } = renderTheForm()

    await fillInMyName(user)
    await user.click(screen.getByRole('button', { name: 'Continuer' }))

    await waitFor(() => expect(onCompleted).toHaveBeenCalled())
    expect(sent(0)).toMatchObject({ path: '/api/me/contact-details', method: 'PATCH' })
    await expect(sent(0).body).resolves.toEqual({ email: LOGIN_EMAIL, phoneNumber: null, language: null })
    expect(sent(1)).toMatchObject({ path: '/api/me/name', method: 'PATCH' })
    await expect(sent(1).body).resolves.toEqual({ firstName: 'Ada', lastName: 'Lovelace' })
    expect(queryClient.getQueryData<Account>(accountQuery().queryKey)?.name).toEqual({
      firstName: 'Ada',
      lastName: 'Lovelace',
    })
  })

  it('gives the profile another email when the member changes it', async () => {
    theApi
      .mockResolvedValueOnce(Response.json(completed('', '', 'ada.lovelace@babbage.test')))
      .mockResolvedValueOnce(Response.json(completed('Ada', 'Lovelace', 'ada.lovelace@babbage.test')))
    const { user, onCompleted } = renderTheForm()

    await fillInMyName(user)
    await user.clear(screen.getByLabelText('E-mail'))
    await user.type(screen.getByLabelText('E-mail'), 'ada.lovelace@babbage.test')
    await user.click(screen.getByRole('button', { name: 'Continuer' }))

    await waitFor(() => expect(onCompleted).toHaveBeenCalled())
    await expect(sent(0).body).resolves.toMatchObject({ email: 'ada.lovelace@babbage.test' })
  })

  it('shows on the email that it is another member’s, and gives no name', async () => {
    theApi.mockResolvedValueOnce(Response.json(emailAlreadyUsed, { status: 409 }))
    const { user, onCompleted } = renderTheForm()

    await fillInMyName(user)
    await user.click(screen.getByRole('button', { name: 'Continuer' }))

    await waitFor(() =>
      expect(screen.getByLabelText('E-mail')).toHaveAccessibleDescription(
        'Cette adresse e-mail est déjà utilisée.',
      ),
    )
    expect(theApi).toHaveBeenCalledTimes(1)
    expect(sent(0).path).toBe('/api/me/contact-details')
    expect(onCompleted).not.toHaveBeenCalled()
  })
})
