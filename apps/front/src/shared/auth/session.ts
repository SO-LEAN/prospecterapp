import { Amplify } from 'aws-amplify'
import { AuthError, fetchAuthSession, signIn, signOut } from 'aws-amplify/auth'
import { cognitoUserPoolsTokenProvider } from 'aws-amplify/auth/cognito'
import { sessionStorage } from 'aws-amplify/utils'
import createFetchClient from 'openapi-fetch'
import type { paths } from '@/shared/api/schema'
import { env } from '@/shared/config/env'

/**
 * The members sign in with the Cognito user pool that the API names (ADR 0052). They sign in by SRP, so the password
 * never leaves the browser. The tokens live in sessionStorage: each tab has its own, and they go when the tab closes.
 * Once the access token expires, the refresh token renews it.
 */
export interface Session {
  signIn(email: string, password: string): Promise<void>
  /** Signs out of the app alone. It revokes the app's refresh token, and the member's AIs stay signed in. */
  signOut(): Promise<void>
  /** Signs out everywhere. It revokes all the member's refresh tokens, their AIs' tokens included. */
  signOutEverywhere(): Promise<void>
  /** The access token for API calls, renewed once expired. It is null when nobody is signed in. */
  accessToken(): Promise<string | null>
  /** A new access token, after the API refused the current one. It is null when the token cannot be renewed. */
  renewedAccessToken(): Promise<string | null>
}

/** A Cognito refusal. Its code gives the reason (`NotAuthorizedException`, `TooManyRequestsException`, and so on). */
export type SignInRefusal = { code: string; message: string }

const NEW_PASSWORD_REQUIRED: SignInRefusal = {
  code: 'NewPasswordRequired',
  message: 'A new password is required',
}

const whereToSignIn = createFetchClient<paths>({ baseUrl: env.apiUrl })

let configured: Promise<void> | null = null

/** Configures Amplify once with the user pool that the API names. Amplify keeps its tokens in the tab's storage. */
function onTheUserPool(): Promise<void> {
  configured ??= whereToSignIn.GET('/api/user-pool').then(({ data, error }) => {
    if (!data) {
      configured = null
      throw error
    }
    Amplify.configure({
      Auth: {
        Cognito: {
          userPoolId: data.userPoolId,
          userPoolClientId: data.clientId,
          ...(data.endpoint ? { userPoolEndpoint: data.endpoint } : {}),
        },
      },
    })
    cognitoUserPoolsTokenProvider.setKeyValueStorage(sessionStorage)
  })
  return configured
}

const refusalOf = (error: unknown): unknown =>
  error instanceof AuthError ? ({ code: error.name, message: error.message } satisfies SignInRefusal) : error

async function accessTokenOfTheTab(forceRefresh: boolean): Promise<string | null> {
  await onTheUserPool()
  try {
    const { tokens } = await fetchAuthSession({ forceRefresh })
    return tokens?.accessToken.toString() ?? null
  } catch {
    return null
  }
}

export const session: Session = {
  async signIn(email, password) {
    await onTheUserPool()
    await signOut()
    const { nextStep } = await signIn({
      username: email,
      password,
      options: { authFlowType: 'USER_SRP_AUTH' },
    }).catch((error: unknown) => {
      throw refusalOf(error)
    })
    if (nextStep.signInStep === 'CONFIRM_SIGN_IN_WITH_NEW_PASSWORD_REQUIRED') {
      throw NEW_PASSWORD_REQUIRED
    }
    if (nextStep.signInStep !== 'DONE') {
      throw {
        code: nextStep.signInStep,
        message: 'Another step is required to sign in',
      } satisfies SignInRefusal
    }
  },

  async signOut() {
    await onTheUserPool()
    await signOut({ global: false })
  },

  async signOutEverywhere() {
    await onTheUserPool()
    await signOut({ global: true })
  },

  accessToken: () => accessTokenOfTheTab(false),

  renewedAccessToken: () => accessTokenOfTheTab(true),
}
