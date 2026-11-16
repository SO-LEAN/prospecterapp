import { Amplify } from 'aws-amplify'
import { AuthError, fetchAuthSession, signIn, signOut } from 'aws-amplify/auth'
import { cognitoUserPoolsTokenProvider } from 'aws-amplify/auth/cognito'
import { sessionStorage } from 'aws-amplify/utils'
import createFetchClient from 'openapi-fetch'
import type { paths } from '@/shared/api/schema'
import { env } from '@/shared/config/env'

/**
 * The members sign in with the Cognito user pool the API names (ADR 0052), by SRP: the password never leaves
 * the browser. The tokens live in sessionStorage (per tab, gone when the tab closes); the access token is
 * renewed with the refresh token once expired.
 */
export interface Session {
  signIn(email: string, password: string): Promise<void>
  signOut(): Promise<void>
  /** The access token to call the API with, renewed when expired; null when nobody is signed in. */
  accessToken(): Promise<string | null>
  /** A new access token, after the API refused the current one; null when it cannot be renewed. */
  renewedAccessToken(): Promise<string | null>
}

/** A refusal of Cognito: its code tells why (`NotAuthorizedException`, `TooManyRequestsException`…). */
export type SignInRefusal = { code: string; message: string }

const NEW_PASSWORD_REQUIRED: SignInRefusal = {
  code: 'NewPasswordRequired',
  message: 'A new password is required',
}

const whereToSignIn = createFetchClient<paths>({ baseUrl: env.apiUrl })

let userPool: Promise<void> | null = null

/** Amplify configured once with the user pool the API names, its tokens kept in the storage of the tab. */
function onTheUserPool(): Promise<void> {
  userPool ??= whereToSignIn.GET('/api/user-pool').then(({ data, error }) => {
    if (!data) {
      userPool = null
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
  return userPool
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
  },

  async signOut() {
    await onTheUserPool()
    await signOut({ global: true })
  },

  accessToken: () => accessTokenOfTheTab(false),

  renewedAccessToken: () => accessTokenOfTheTab(true),
}
