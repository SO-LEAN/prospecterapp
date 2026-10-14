import createFetchClient, { type Middleware } from 'openapi-fetch'
import createQueryClient from 'openapi-react-query'
import { userManager } from '@/app/auth'
import { env } from '@/app/env'
import type { paths } from './schema'

const retried = new WeakSet<Request>()

/** Adds the bearer token, and on a 401 renews it once with the refresh token before giving up. */
const authentication: Middleware = {
  async onRequest({ request }) {
    const user = await userManager.getUser()
    if (user?.access_token) {
      request.headers.set('Authorization', `Bearer ${user.access_token}`)
    }
    request.headers.set('Accept', 'application/json')
    return request
  },
  async onResponse({ request, response }) {
    if (response.status !== 401 || retried.has(request)) {
      return response
    }
    const user = await userManager.signinSilent().catch(() => null)
    if (!user) {
      await userManager.signinRedirect({ state: { returnTo: window.location.href } })
      return response
    }
    const retry = new Request(request, { headers: new Headers(request.headers) })
    retry.headers.set('Authorization', `Bearer ${user.access_token}`)
    retried.add(retry)
    return fetch(retry)
  },
}

/** Typed fetch client generated from the API OpenAPI contract (`pnpm api:types`). */
export const fetchClient = createFetchClient<paths>({ baseUrl: env.apiUrl })
fetchClient.use(authentication)

/** TanStack Query bindings: `api.useQuery('get', '/api/me')`, `api.queryOptions(...)`, `api.useMutation(...)`. */
export const api = createQueryClient(fetchClient)
