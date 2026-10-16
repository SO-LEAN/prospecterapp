import {
  AuthenticationDetails,
  CognitoUser,
  CognitoUserPool,
  type CognitoUserSession,
} from 'amazon-cognito-identity-js'
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

const whereToSignIn = createFetchClient<paths>({ baseUrl: env.apiUrl })

let userPool: Promise<CognitoUserPool> | null = null

function theUserPool(): Promise<CognitoUserPool> {
  userPool ??= whereToSignIn.GET('/api/user-pool').then(({ data, error }) => {
    if (!data) {
      userPool = null
      throw error
    }
    return new CognitoUserPool({
      UserPoolId: data.userPoolId,
      ClientId: data.clientId,
      endpoint: data.endpoint ?? undefined,
      Storage: window.sessionStorage,
    })
  })
  return userPool
}

const sessionOf = (user: CognitoUser) =>
  new Promise<CognitoUserSession | null>((resolve) => {
    user.getSession((error: Error | null, session: CognitoUserSession | null) =>
      resolve(error ? null : session),
    )
  })

const renewed = (user: CognitoUser, session: CognitoUserSession) =>
  new Promise<CognitoUserSession | null>((resolve) => {
    user.refreshSession(session.getRefreshToken(), (error: Error | null, fresh: CognitoUserSession | null) =>
      resolve(error ? null : fresh),
    )
  })

async function signedInUser(): Promise<CognitoUser | null> {
  return (await theUserPool()).getCurrentUser()
}

export const session: Session = {
  async signIn(email, password) {
    const user = new CognitoUser({
      Username: email,
      Pool: await theUserPool(),
      Storage: window.sessionStorage,
    })
    await new Promise<void>((resolve, reject) => {
      user.authenticateUser(new AuthenticationDetails({ Username: email, Password: password }), {
        onSuccess: () => resolve(),
        onFailure: (refusal: SignInRefusal) => reject(refusal),
        newPasswordRequired: () =>
          reject({
            code: 'NewPasswordRequired',
            message: 'A new password is required',
          } satisfies SignInRefusal),
      })
    })
  },

  async signOut() {
    const user = await signedInUser()
    if (!user) {
      return
    }
    await new Promise<void>((resolve) => {
      user.globalSignOut({ onSuccess: () => resolve(), onFailure: () => resolve() })
    })
    user.signOut()
  },

  async accessToken() {
    const user = await signedInUser()
    const current = user ? await sessionOf(user) : null
    return current?.getAccessToken().getJwtToken() ?? null
  },

  async renewedAccessToken() {
    const user = await signedInUser()
    const current = user ? await sessionOf(user) : null
    const fresh = user && current ? await renewed(user, current) : null
    return fresh?.getAccessToken().getJwtToken() ?? null
  },
}
