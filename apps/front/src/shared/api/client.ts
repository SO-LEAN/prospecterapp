import createFetchClient, { type Middleware } from 'openapi-fetch'
import createQueryClient from 'openapi-react-query'
import { session } from '@/shared/auth/session'
import { signInPage } from '@/shared/auth/sign-in-page'
import { env } from '@/shared/config/env'
import type { paths } from './schema'

const retried = new WeakSet<Request>()

/** Adds the access token. On a 401, it renews the token once before it sends the member to sign in again. */
const authentication: Middleware = {
  async onRequest({ request }) {
    const token = await session.accessToken()
    if (token) {
      request.headers.set('Authorization', `Bearer ${token}`)
    }
    request.headers.set('Accept', 'application/json')
    return request
  },
  async onResponse({ request, response }) {
    if (response.status !== 401 || retried.has(request)) {
      return response
    }
    const token = await session.renewedAccessToken()
    if (!token) {
      window.location.assign(signInPage(window.location.pathname + window.location.search))
      return response
    }
    const retry = new Request(request, { headers: new Headers(request.headers) })
    retry.headers.set('Authorization', `Bearer ${token}`)
    retried.add(retry)
    return fetch(retry)
  },
}

/** The typed fetch client, generated from the API's OpenAPI contract (`pnpm api:types`). */
export const fetchClient = createFetchClient<paths>({ baseUrl: env.apiUrl })
fetchClient.use(authentication)

/** TanStack Query bindings: `api.useQuery('get', '/api/me')`, `api.queryOptions(...)`, `api.useMutation(...)`. */
export const api = createQueryClient(fetchClient)
