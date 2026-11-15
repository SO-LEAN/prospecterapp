/**
 * The addresses of the stack under test: on 443 for the stack of the main checkout, on the port of its own for that
 * of a worktree (`PUBLIC_PORT`, ADR 0116), the same in the network of the stack as from the host.
 */
const PUBLIC_PORT = process.env.PUBLIC_PORT ?? '443'
const PORT_SUFFIX = PUBLIC_PORT === '443' ? '' : `:${PUBLIC_PORT}`

/** The host of the SPA, the API and the static under it: names in `.localhost`, the loopback without /etc/hosts. */
export const PUBLIC_HOST = 'prospecter.localhost'
export const SPA_URL = `https://${PUBLIC_HOST}${PORT_SUFFIX}`
export const API_URL = `https://api.${PUBLIC_HOST}${PORT_SUFFIX}`
export const STATIC_URL = `https://static.${PUBLIC_HOST}${PORT_SUFFIX}`

/** The journeys running together, each in a worker of its own (ADR 0117): E2E_WORKERS, two by default. */
export const WORKERS = Number(process.env.E2E_WORKERS || 2)
