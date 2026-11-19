import { Amplify } from 'aws-amplify'
import { AuthError, fetchAuthSession, signIn, signOut } from 'aws-amplify/auth'
import { cognitoUserPoolsTokenProvider } from 'aws-amplify/auth/cognito'
import { sessionStorage } from 'aws-amplify/utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('aws-amplify', () => ({ Amplify: { configure: vi.fn() } }))
vi.mock('aws-amplify/auth', async (original) => ({
  AuthError: (await original<typeof import('aws-amplify/auth')>()).AuthError,
  fetchAuthSession: vi.fn(),
  signIn: vi.fn(),
  signOut: vi.fn(),
}))
vi.mock('aws-amplify/auth/cognito', () => ({
  cognitoUserPoolsTokenProvider: { setKeyValueStorage: vi.fn() },
}))

const THE_USER_POOL = {
  region: 'eu-west-3',
  userPoolId: 'eu-west-3_pool',
  clientId: 'client-of-the-spa',
  endpoint: 'https://api.prospecter.localhost/cognito/',
}

const signedInWith = (accessToken: string) =>
  vi.mocked(fetchAuthSession).mockResolvedValue({
    tokens: { accessToken: { toString: () => accessToken, payload: {} } },
  })

async function theSession() {
  vi.resetModules()
  return (await import('./session')).session
}

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => Response.json(THE_USER_POOL)),
  )
  vi.mocked(signIn).mockResolvedValue({ isSignedIn: true, nextStep: { signInStep: 'DONE' } })
})

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe('session', () => {
  it('signs in on the user pool the API names, its tokens kept in the storage of the tab', async () => {
    const session = await theSession()

    await session.signIn('prospector@solean-it.io', 'password')

    expect(Amplify.configure).toHaveBeenCalledWith({
      Auth: {
        Cognito: {
          userPoolId: 'eu-west-3_pool',
          userPoolClientId: 'client-of-the-spa',
          userPoolEndpoint: 'https://api.prospecter.localhost/cognito/',
        },
      },
    })
    expect(cognitoUserPoolsTokenProvider.setKeyValueStorage).toHaveBeenCalledWith(sessionStorage)
  })

  it('reaches the Cognito of AWS when the API names no endpoint', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => Response.json({ ...THE_USER_POOL, endpoint: null })),
    )
    const session = await theSession()

    await session.signIn('prospector@solean-it.io', 'password')

    expect(Amplify.configure).toHaveBeenCalledWith({
      Auth: { Cognito: { userPoolId: 'eu-west-3_pool', userPoolClientId: 'client-of-the-spa' } },
    })
  })

  it('signs in by SRP, the password never leaving the browser', async () => {
    const session = await theSession()

    await session.signIn('prospector@solean-it.io', 'password')

    expect(signIn).toHaveBeenCalledWith({
      username: 'prospector@solean-it.io',
      password: 'password',
      options: { authFlowType: 'USER_SRP_AUTH' },
    })
  })

  it('ends the session of the tab before signing in again', async () => {
    const session = await theSession()

    await session.signIn('prospector@solean-it.io', 'password')

    expect(signOut).toHaveBeenCalledBefore(vi.mocked(signIn))
  })

  it('tells the refusal of Cognito by its code', async () => {
    vi.mocked(signIn).mockRejectedValue(
      new AuthError({ name: 'NotAuthorizedException', message: 'Password attempts exceeded' }),
    )
    const session = await theSession()

    await expect(session.signIn('prospector@solean-it.io', 'wrong')).rejects.toEqual({
      code: 'NotAuthorizedException',
      message: 'Password attempts exceeded',
    })
  })

  it('refuses to sign in a member who must choose a new password', async () => {
    vi.mocked(signIn).mockResolvedValue({
      isSignedIn: false,
      nextStep: { signInStep: 'CONFIRM_SIGN_IN_WITH_NEW_PASSWORD_REQUIRED' },
    })
    const session = await theSession()

    await expect(session.signIn('prospector@solean-it.io', 'password')).rejects.toEqual({
      code: 'NewPasswordRequired',
      message: 'A new password is required',
    })
  })

  it('refuses to sign in a member Cognito asks another step of', async () => {
    vi.mocked(signIn).mockResolvedValue({
      isSignedIn: false,
      nextStep: { signInStep: 'RESET_PASSWORD' },
    })
    const session = await theSession()

    await expect(session.signIn('prospector@solean-it.io', 'password')).rejects.toMatchObject({
      code: 'RESET_PASSWORD',
    })
  })

  it('asks the user pool once', async () => {
    const session = await theSession()

    await session.signIn('prospector@solean-it.io', 'password')
    await session.accessToken()

    expect(fetch).toHaveBeenCalledOnce()
    expect(Amplify.configure).toHaveBeenCalledOnce()
  })

  it('gives the access token of the tab, renewed by the library once expired', async () => {
    signedInWith('the access token')
    const session = await theSession()

    expect(await session.accessToken()).toBe('the access token')
    expect(fetchAuthSession).toHaveBeenCalledWith({ forceRefresh: false })
  })

  it('gives no access token when nobody is signed in', async () => {
    vi.mocked(fetchAuthSession).mockResolvedValue({})
    const session = await theSession()

    expect(await session.accessToken()).toBeNull()
  })

  it('gives no access token when it cannot be read', async () => {
    vi.mocked(fetchAuthSession).mockRejectedValue(new Error('Network error'))
    const session = await theSession()

    expect(await session.accessToken()).toBeNull()
  })

  it('renews the access token the API refused with the refresh token', async () => {
    signedInWith('a renewed access token')
    const session = await theSession()

    expect(await session.renewedAccessToken()).toBe('a renewed access token')
    expect(fetchAuthSession).toHaveBeenCalledWith({ forceRefresh: true })
  })

  it('signs out of the app alone, its own refresh token revoked, the AIs of the member still signed in', async () => {
    const session = await theSession()

    await session.signOut()

    expect(signOut).toHaveBeenCalledWith({ global: false })
  })

  it('signs out everywhere, the AIs of the member included', async () => {
    const session = await theSession()

    await session.signOutEverywhere()

    expect(signOut).toHaveBeenCalledWith({ global: true })
  })
})
